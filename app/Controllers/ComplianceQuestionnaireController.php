<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ComplianceQuestionnaireCatalog;
use App\Libraries\EamsPdf;
use App\Models\ComplianceQuestionnaireModel;
use App\Models\ComplianceQuestionnaireQuestionModel;
use App\Models\ComplianceQuestionnaireResponseAnswerModel;
use App\Models\ComplianceQuestionnaireResponseModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ComplianceQuestionnaireController extends BaseController
{
  protected ComplianceQuestionnaireModel $questionnaireModel;
  protected ComplianceQuestionnaireQuestionModel $questionModel;
  protected ComplianceQuestionnaireResponseModel $responseModel;
  protected ComplianceQuestionnaireResponseAnswerModel $answerModel;

  public function __construct()
  {
    $this->questionnaireModel = new ComplianceQuestionnaireModel();
    $this->questionModel = new ComplianceQuestionnaireQuestionModel();
    $this->responseModel = new ComplianceQuestionnaireResponseModel();
    $this->answerModel = new ComplianceQuestionnaireResponseAnswerModel();

    $this->bootstrapDefaultsIfNeeded();
  }

  public function index()
  {
    if ($guard = $this->guardRead()) {
      return $guard;
    }

    $templates = $this->questionnaireModel
      ->orderBy('sort_order', 'ASC')
      ->orderBy('title', 'ASC')
      ->findAll();

    $questionCounts = $this->aggregateCounts('compliance_questionnaire_questions', 'questionnaire_id');
    $responseCounts = $this->aggregateCounts('compliance_questionnaire_responses', 'questionnaire_id');

    foreach ($templates as &$template) {
      $templateId = (int) ($template['id'] ?? 0);
      $template['question_count'] = (int) ($questionCounts[$templateId] ?? 0);
      $template['response_count'] = (int) ($responseCounts[$templateId] ?? 0);
      $template['public_path'] = $this->publicQuestionnairePath($template);
    }
    unset($template);

    return $this->render('compliance/questionnaire/index', [
      'title' => 'Kuesioner Compliance',
      'templates' => $templates,
    ]);
  }

  public function analytics()
  {
    if ($guard = $this->guardRead()) {
      return $guard;
    }

    $templates = $this->loadQuestionnaireTemplatesWithCounts();
    $selectedQuestionnaireId = (int) ($this->request->getGet('questionnaire_id') ?? 0);

    if ($selectedQuestionnaireId <= 0 && !empty($templates)) {
      $selectedQuestionnaireId = (int) ($templates[0]['id'] ?? 0);
    }

    $selectedQuestionnaire = null;
    foreach ($templates as $template) {
      if ((int) ($template['id'] ?? 0) === $selectedQuestionnaireId) {
        $selectedQuestionnaire = $template;
        break;
      }
    }

    $viewData = [
      'templates' => $templates,
      'selectedQuestionnaireId' => $selectedQuestionnaireId,
      'selectedQuestionnaire' => $selectedQuestionnaire,
      'overviewStats' => $this->buildAnalyticsOverview($templates),
      'submissionTrend' => $this->buildSubmissionTrend($selectedQuestionnaireId > 0 ? $selectedQuestionnaireId : null),
      'recentResponses' => $this->loadRecentResponses($selectedQuestionnaireId > 0 ? $selectedQuestionnaireId : null),
      'questionAnalyses' => $selectedQuestionnaireId > 0 ? $this->buildQuestionAnalyses($selectedQuestionnaireId) : [],
    ];

    if ($this->wantsJson()) {
      return $this->response->setJSON([
        'success' => true,
        'selectedQuestionnaireId' => $selectedQuestionnaireId,
        'html' => view('compliance/questionnaire/_analytics_content', $viewData),
      ]);
    }

    return $this->render('compliance/questionnaire/analytics', $viewData + [
      'title' => 'Analitik Kuesioner',
      'backUrl' => 'compliance/questionnaires',
    ]);
  }

  public function create()
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    return $this->render('compliance/questionnaire/form', [
      'title' => 'Tambah Kuesioner',
      'backUrl' => 'compliance/questionnaires',
      'mode' => 'create',
      'questionnaire' => [
        'title' => '',
        'slug' => '',
        'subtitle' => '',
        'description' => '',
        'footer_note' => '',
        'active' => 1,
        'sort_order' => 0,
      ],
    ]);
  }

  public function store()
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $payload = $this->buildQuestionnairePayload();
    if (isset($payload['error'])) {
      return redirect()->back()->withInput()->with('error', $payload['error']);
    }

    $this->questionnaireModel->insert($payload);
    $id = (int) $this->questionnaireModel->getInsertID();

    return redirect()
      ->to(base_url('compliance/questionnaires/' . $id))
      ->with('success', 'Kuesioner berhasil dibuat. Sekarang kamu bisa tambah atau edit pertanyaannya.');
  }

  public function edit($id)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($id);
    if (!$questionnaire) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak ditemukan.');
    }

    return $this->render('compliance/questionnaire/form', [
      'title' => 'Edit Kuesioner',
      'backUrl' => 'compliance/questionnaires/' . $id,
      'mode' => 'edit',
      'questionnaire' => $questionnaire,
    ]);
  }

  public function update($id)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($id);
    if (!$questionnaire) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak ditemukan.');
    }

    $payload = $this->buildQuestionnairePayload((int) $id);
    if (isset($payload['error'])) {
      return redirect()->back()->withInput()->with('error', $payload['error']);
    }

    $this->questionnaireModel->update((int) $id, $payload);

    return redirect()->to(base_url('compliance/questionnaires/' . $id))->with('success', 'Data kuesioner berhasil diperbarui.');
  }

  public function delete($id)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($id);
    if (!$questionnaire) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak ditemukan.');
    }

    $responseExists = $this->responseModel->where('questionnaire_id', $id)->countAllResults() > 0;
    if ($responseExists) {
      return redirect()->to(base_url('compliance/questionnaires/' . $id))->with('error', 'Kuesioner yang sudah memiliki hasil tidak bisa dihapus. Nonaktifkan saja jika ingin disimpan sebagai arsip.');
    }

    $this->questionnaireModel->delete((int) $id);

    return redirect()->to(base_url('compliance/questionnaires'))->with('success', 'Kuesioner berhasil dihapus.');
  }

  public function detail($id)
  {
    if ($guard = $this->guardRead()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($id);
    if (!$questionnaire) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak ditemukan.');
    }

    $questions = $this->loadQuestions((int) $id);
    $responses = $this->responseModel
      ->where('questionnaire_id', $id)
      ->orderBy('submitted_at', 'DESC')
      ->orderBy('id', 'DESC')
      ->findAll();

    foreach ($responses as &$response) {
      $response['detail_path'] = $this->adminResponsePath((int) $response['id']);
      $response['pdf_path'] = $this->adminResponsePdfPath((int) $response['id']);
    }
    unset($response);

    return $this->render('compliance/questionnaire/detail', [
      'title' => 'Detail Kuesioner',
      'questionnaire' => $questionnaire,
      'questions' => $questions,
      'questionGroups' => $this->groupQuestionsBySection($questions),
      'sectionOptions' => $this->extractSectionOptions($questions),
      'answerTypeLabels' => $this->answerTypeLabels(),
      'openQuestionId' => null,
      'respondentFields' => $this->respondentFields($questionnaire),
      'responses' => $responses,
      'isWriteAllowed' => hasRole(['admin', 'compliance']),
      'publicPath' => $this->publicQuestionnairePath($questionnaire),
      'excelPath' => $this->adminExcelPath((int) $questionnaire['id']),
    ]);
  }

  public function storeQuestion($questionnaireId)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($questionnaireId);
    if (!$questionnaire) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak ditemukan.');
    }

    $payload = $this->buildQuestionPayload((int) $questionnaireId);
    if (isset($payload['error'])) {
      if ($this->wantsJson()) {
        return $this->response->setStatusCode(422)->setJSON([
          'success' => false,
          'message' => $payload['error'],
        ]);
      }

      return redirect()->to(base_url('compliance/questionnaires/' . $questionnaireId))->withInput()->with('error', $payload['error']);
    }

    $this->questionModel->insert($payload);
    $newQuestionId = (int) $this->questionModel->getInsertID();
    $this->resequenceQuestionOrder((int) $questionnaireId, $newQuestionId, $this->parseRequestedPosition(), true);

    if ($this->wantsJson()) {
      return $this->questionMutationResponse((int) $questionnaireId, 'Pertanyaan baru berhasil ditambahkan.', $newQuestionId);
    }

    return redirect()->to(base_url('compliance/questionnaires/' . $questionnaireId))->with('success', 'Pertanyaan baru berhasil ditambahkan.');
  }

  public function reorderQuestions($questionnaireId)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($questionnaireId);
    if (!$questionnaire) {
      if ($this->wantsJson()) {
        return $this->response->setStatusCode(404)->setJSON([
          'success' => false,
          'message' => 'Kuesioner tidak ditemukan.',
        ]);
      }

      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak ditemukan.');
    }

    $order = $this->request->getPost('order');
    $focusQuestionId = (int) ($this->request->getPost('focus_question_id') ?? 0);

    if (!is_array($order) || empty($order)) {
      return $this->response->setStatusCode(422)->setJSON([
        'success' => false,
        'message' => 'Urutan pertanyaan belum valid.',
      ]);
    }

    $order = array_values(array_unique(array_map(static fn($id): int => (int) $id, $order)));
    $existingQuestions = $this->loadQuestions((int) $questionnaireId);
    $existingIds = array_map(static fn(array $question): int => (int) ($question['id'] ?? 0), $existingQuestions);

    sort($order);
    $sortedExistingIds = $existingIds;
    sort($sortedExistingIds);

    if ($order !== $sortedExistingIds) {
      return $this->response->setStatusCode(422)->setJSON([
        'success' => false,
        'message' => 'Urutan pertanyaan tidak lengkap atau tidak sesuai.',
      ]);
    }

    $submittedOrder = array_map(static fn($id): int => (int) $id, $this->request->getPost('order') ?? []);
    $sortOrder = 10;
    foreach ($submittedOrder as $questionId) {
      $this->questionModel->update($questionId, ['sort_order' => $sortOrder]);
      $sortOrder += 10;
    }

    return $this->questionMutationResponse((int) $questionnaireId, 'Urutan pertanyaan berhasil diperbarui.', $focusQuestionId > 0 ? $focusQuestionId : null);
  }

  public function updateQuestion($questionId)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $question = $this->questionModel->find($questionId);
    if (!$question) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Pertanyaan tidak ditemukan.');
    }

    $payload = $this->buildQuestionPayload((int) $question['questionnaire_id']);
    if (isset($payload['error'])) {
      if ($this->wantsJson()) {
        return $this->response->setStatusCode(422)->setJSON([
          'success' => false,
          'message' => $payload['error'],
        ]);
      }

      return redirect()->to(base_url('compliance/questionnaires/' . $question['questionnaire_id']))->withInput()->with('error', $payload['error']);
    }

    $this->questionModel->update((int) $questionId, $payload);
    $this->resequenceQuestionOrder((int) $question['questionnaire_id'], (int) $questionId, $this->parseRequestedPosition(), false);

    if ($this->wantsJson()) {
      return $this->questionMutationResponse((int) $question['questionnaire_id'], 'Pertanyaan berhasil diperbarui.', (int) $questionId);
    }

    return redirect()->to(base_url('compliance/questionnaires/' . $question['questionnaire_id']))->with('success', 'Pertanyaan berhasil diperbarui.');
  }

  public function deleteQuestion($questionId)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $question = $this->questionModel->find($questionId);
    if (!$question) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Pertanyaan tidak ditemukan.');
    }

    $hasAnswers = $this->answerModel->where('question_id', $questionId)->countAllResults() > 0;
    if ($hasAnswers) {
      if ($this->wantsJson()) {
        return $this->response->setStatusCode(409)->setJSON([
          'success' => false,
          'message' => 'Pertanyaan yang sudah memiliki jawaban tidak bisa dihapus. Kamu masih bisa mengedit teksnya bila diperlukan.',
        ]);
      }

      return redirect()->to(base_url('compliance/questionnaires/' . $question['questionnaire_id']))->with('error', 'Pertanyaan yang sudah memiliki jawaban tidak bisa dihapus. Kamu masih bisa mengedit teksnya bila diperlukan.');
    }

    $this->questionModel->delete((int) $questionId);
    $this->normalizeQuestionOrder((int) $question['questionnaire_id']);

    if ($this->wantsJson()) {
      return $this->questionMutationResponse((int) $question['questionnaire_id'], 'Pertanyaan berhasil dihapus.');
    }

    return redirect()->to(base_url('compliance/questionnaires/' . $question['questionnaire_id']))->with('success', 'Pertanyaan berhasil dihapus.');
  }

  public function updateRespondentSettings($questionnaireId)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($questionnaireId);
    if (!$questionnaire) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak ditemukan.');
    }

    $payload = [
      'collect_name' => $this->request->getPost('collect_name') ? 1 : 0,
      'collect_phone' => $this->request->getPost('collect_phone') ? 1 : 0,
      'collect_email' => $this->request->getPost('collect_email') ? 1 : 0,
    ];

    $this->questionnaireModel->update((int) $questionnaireId, $payload);

    if ($this->wantsJson()) {
      return $this->response->setJSON([
        'success' => true,
        'message' => 'Pengaturan identitas responden berhasil diperbarui.',
        'respondentFields' => [
          'name' => (bool) $payload['collect_name'],
          'phone' => (bool) $payload['collect_phone'],
          'email' => (bool) $payload['collect_email'],
        ],
      ]);
    }

    return redirect()->to(base_url('compliance/questionnaires/' . $questionnaireId))->with('success', 'Pengaturan identitas responden berhasil diperbarui.');
  }

  public function deleteResponse($responseId)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    $response = $this->responseModel->find((int) $responseId);
    if (!$response) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Hasil kuesioner tidak ditemukan.');
    }

    $questionnaireId = (int) ($response['questionnaire_id'] ?? 0);
    $this->responseModel->delete((int) $responseId);

    return redirect()->to(base_url('compliance/questionnaires/' . $questionnaireId))->with('success', 'Respon kuesioner berhasil dihapus.');
  }

  public function moveQuestionUp($questionId)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    return $this->moveQuestion((int) $questionId, -1);
  }

  public function moveQuestionDown($questionId)
  {
    if ($guard = $this->guardWrite()) {
      return $guard;
    }

    return $this->moveQuestion((int) $questionId, 1);
  }

  public function fill($id)
  {
    if ($guard = $this->guardRead()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($id);
    if (!$questionnaire || !(int) ($questionnaire['active'] ?? 0)) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak tersedia atau sedang dinonaktifkan.');
    }

    return $this->renderFillPage($questionnaire, false);
  }

  public function publicFill($slug)
  {
    return $this->renderFillPage($this->findPublicQuestionnaire((string) $slug), true);
  }

  public function submit($id)
  {
    if ($guard = $this->guardRead()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($id);
    if (!$questionnaire || !(int) ($questionnaire['active'] ?? 0)) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak tersedia atau sedang dinonaktifkan.');
    }

    return $this->handleSubmit($questionnaire, false);
  }

  public function publicSubmit($slug)
  {
    return $this->handleSubmit($this->findPublicQuestionnaire((string) $slug), true);
  }

  public function publicThanks($slug)
  {
    $questionnaire = $this->questionnaireModel->where('slug', (string) $slug)->first();
    if (!$questionnaire) {
      throw PageNotFoundException::forPageNotFound('Kuesioner tidak ditemukan.');
    }

    $submitInfo = session()->getFlashdata('questionnaire_submit');

    return $this->render('compliance/questionnaire/thanks', [
      'title' => 'Terima Kasih',
      'questionnaire' => $questionnaire,
      'submitInfo' => is_array($submitInfo) ? $submitInfo : null,
      'backToFormPath' => $this->publicQuestionnairePath($questionnaire),
    ]);
  }

  public function responseDetail($responseId)
  {
    if ($guard = $this->guardRead()) {
      return $guard;
    }

    $detail = $this->loadResponseDetail((int) $responseId);
    if ($detail === null) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Hasil kuesioner tidak ditemukan.');
    }

    return $this->renderResponsePage($detail);
  }

  public function responsePdf($responseId)
  {
    if ($guard = $this->guardRead()) {
      return $guard;
    }

    $detail = $this->loadResponseDetail((int) $responseId);
    if ($detail === null) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Hasil kuesioner tidak ditemukan.');
    }

    $pdf = new EamsPdf();

    return $pdf->export('questionnaire_response', [
      'questionnaire' => $detail['questionnaire'],
      'response' => $detail['response'],
      'questionGroups' => $detail['questionGroups'],
      'answersMap' => $detail['answersMap'],
      'filename' => 'Kuesioner-' . preg_replace('/[^A-Za-z0-9\\-]+/', '-', (string) $detail['response']['response_code']) . '.pdf',
    ]);
  }

  public function exportExcel($id)
  {
    if ($guard = $this->guardRead()) {
      return $guard;
    }

    $questionnaire = $this->findQuestionnaire($id);
    if (!$questionnaire) {
      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Kuesioner tidak ditemukan.');
    }

    $questions = $this->loadQuestions((int) $id);
    $respondentFields = $this->respondentFields($questionnaire);
    $responses = $this->responseModel
      ->where('questionnaire_id', $id)
      ->orderBy('submitted_at', 'DESC')
      ->orderBy('id', 'DESC')
      ->findAll();

    $responseIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $responses);
    $answerRows = !empty($responseIds)
      ? $this->answerModel->whereIn('response_id', $responseIds)->findAll()
      : [];

    $answersMap = [];
    foreach ($answerRows as $answerRow) {
      $answersMap[(int) $answerRow['response_id']][(int) $answerRow['question_id']] = (string) ($answerRow['answer_value'] ?? '');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheetTitle = mb_substr((string) ($questionnaire['title'] ?? 'Kuesioner'), 0, 31);
    $sheet->setTitle($sheetTitle !== '' ? $sheetTitle : 'Kuesioner');

    $headers = [
      'Kode Respon',
      'Dikirim Pada',
    ];

    if ($respondentFields['name']) {
      $headers[] = 'Nama';
    }

    if ($respondentFields['phone']) {
      $headers[] = 'No Telepon';
    }

    if ($respondentFields['email']) {
      $headers[] = 'Email';
    }

    foreach ($questions as $question) {
      $headers[] = $this->questionColumnLabel($question);
    }

    $sheet->fromArray($headers, null, 'A1');

    $rowIndex = 2;
    foreach ($responses as $response) {
      $row = [
        (string) ($response['response_code'] ?? ''),
        (string) ($response['submitted_at'] ?? ''),
      ];

      if ($respondentFields['name']) {
        $row[] = (string) ($response['respondent_name'] ?? '');
      }

      if ($respondentFields['phone']) {
        $row[] = (string) ($response['phone'] ?? '');
      }

      if ($respondentFields['email']) {
        $row[] = (string) ($response['email'] ?? '');
      }

      foreach ($questions as $question) {
        $row[] = (string) ($answersMap[(int) $response['id']][(int) $question['id']] ?? '');
      }

      $sheet->fromArray($row, null, 'A' . $rowIndex);
      $rowIndex++;
    }

    $lastColumn = $sheet->getHighestColumn();
    $lastRow = max(1, $rowIndex - 1);

    $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
      'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
      ],
      'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1F5FBF'],
      ],
      'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
      ],
      'borders' => [
        'allBorders' => [
          'borderStyle' => Border::BORDER_THIN,
          'color' => ['rgb' => 'D9E2F1'],
        ],
      ],
    ]);

    if ($lastRow >= 2) {
      $sheet->getStyle('A2:' . $lastColumn . $lastRow)->applyFromArray([
        'alignment' => [
          'vertical' => Alignment::VERTICAL_TOP,
          'wrapText' => true,
        ],
        'borders' => [
          'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'E8EDF5'],
          ],
        ],
      ]);
    }

    $sheet->freezePane('A2');
    $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);

    for ($index = 1; $index <= count($headers); $index++) {
      $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $filenameBase = preg_replace('/[^A-Za-z0-9\\-]+/', '-', (string) ($questionnaire['slug'] ?? 'kuesioner')) ?: 'kuesioner';
    $filename = 'Hasil-' . $filenameBase . '.xlsx';

    while (ob_get_level()) {
      ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
  }

  protected function renderFillPage(array $questionnaire, bool $publicMode)
  {
    $questions = $this->loadQuestions((int) $questionnaire['id']);
    $respondentFields = $this->respondentFields($questionnaire);

    return $this->render('compliance/questionnaire/fill', [
      'title' => 'Isi Kuesioner',
      'backUrl' => $publicMode ? null : 'compliance/questionnaires',
      'questionnaire' => $questionnaire,
      'questions' => $questions,
      'questionGroups' => $this->groupQuestionsBySection($questions),
      'respondentFields' => $respondentFields,
      'oldAnswers' => old('answer') ?: [],
      'publicMode' => $publicMode,
      'submitPath' => $publicMode
        ? $this->relativePath('kuesioner/' . rawurlencode((string) $questionnaire['slug']) . '/kirim')
        : $this->relativePath('compliance/questionnaires/submit/' . $questionnaire['id']),
    ]);
  }

  protected function handleSubmit(array $questionnaire, bool $publicMode)
  {
    $questions = $this->loadQuestions((int) $questionnaire['id']);
    $respondentFields = $this->respondentFields($questionnaire);
    $answers = $this->request->getPost('answer') ?? [];
    $respondentName = $respondentFields['name']
      ? trim((string) $this->request->getPost('respondent_name'))
      : 'Anonim';
    $phone = $respondentFields['phone']
      ? trim((string) $this->request->getPost('phone'))
      : '';
    $email = $respondentFields['email']
      ? trim((string) $this->request->getPost('email'))
      : '';
    $submittedAt = date('Y-m-d H:i:s');

    if ($respondentFields['name'] && $respondentName === '') {
      return redirect()->back()->withInput()->with('error', 'Nama responden wajib diisi.');
    }

    if ($respondentFields['email'] && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return redirect()->back()->withInput()->with('error', 'Format email belum valid.');
    }

    foreach ($questions as $question) {
      if ($this->isAutoTimestampQuestion($question)) {
        continue;
      }

      if (!(int) ($question['is_required'] ?? 0)) {
        continue;
      }

      $answerValue = trim((string) ($answers[$question['id']] ?? ''));
      if ($answerValue === '') {
        $label = trim((string) ($question['question_text'] ?? ''));
        if ($label !== '') {
          $shortLabel = function_exists('mb_strimwidth')
            ? mb_strimwidth($label, 0, 60, '...')
            : substr($label, 0, 60);
          $label = 'Pertanyaan "' . $shortLabel . '"';
        } else {
          $label = 'Salah satu pertanyaan';
        }
        return redirect()->back()->withInput()->with('error', $label . ' wajib diisi.');
      }
    }

    $db = db_connect();
    $db->transStart();

    $responseData = [
      'questionnaire_id' => (int) $questionnaire['id'],
      'response_code' => $this->generateResponseCode((int) $questionnaire['id']),
      'respondent_name' => $respondentName,
      'birth_date' => null,
      'phone' => $phone !== '' ? $phone : null,
      'email' => $email !== '' ? $email : null,
      'submitted_at' => $submittedAt,
      'created_by' => $publicMode ? 'Publik' : (string) (session('name') ?? ''),
    ];

    $this->responseModel->insert($responseData);
    $responseId = (int) $this->responseModel->getInsertID();

    $answerRows = [];
    foreach ($questions as $question) {
      $answerValue = $this->isAutoTimestampQuestion($question)
        ? $submittedAt
        : trim((string) ($answers[$question['id']] ?? ''));

      $answerRows[] = [
        'response_id' => $responseId,
        'question_id' => (int) $question['id'],
        'answer_value' => $answerValue,
        'created_at' => date('Y-m-d H:i:s'),
      ];
    }

    if (!empty($answerRows)) {
      $this->answerModel->insertBatch($answerRows);
    }

    $db->transComplete();

    if (!$db->transStatus()) {
      return redirect()->back()->withInput()->with('error', 'Gagal menyimpan hasil kuesioner. Silakan coba lagi.');
    }

    if ($publicMode) {
      return redirect()
        ->to($this->relativePath('kuesioner/' . rawurlencode((string) $questionnaire['slug']) . '/selesai'))
        ->with('questionnaire_submit', [
          'response_code' => $responseData['response_code'],
          'submitted_at' => $submittedAt,
        ]);
    }

    return redirect()->to($this->adminResponsePath($responseId))->with('success', 'Hasil kuesioner berhasil disimpan.');
  }

  protected function renderResponsePage(array $detail)
  {
    return $this->render('compliance/questionnaire/response_detail', [
      'title' => 'Hasil Kuesioner',
      'backUrl' => 'compliance/questionnaires/' . $detail['questionnaire']['id'],
      'questionnaire' => $detail['questionnaire'],
      'respondentFields' => $this->respondentFields($detail['questionnaire']),
      'response' => $detail['response'],
      'questionGroups' => $detail['questionGroups'],
      'answersMap' => $detail['answersMap'],
      'pdfPath' => $this->adminResponsePdfPath((int) $detail['response']['id']),
    ]);
  }

  protected function moveQuestion(int $questionId, int $direction)
  {
    $question = $this->questionModel->find($questionId);
    if (!$question) {
      if ($this->wantsJson()) {
        return $this->response->setStatusCode(404)->setJSON([
          'success' => false,
          'message' => 'Pertanyaan tidak ditemukan.',
        ]);
      }

      return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Pertanyaan tidak ditemukan.');
    }

    $questionnaireId = (int) ($question['questionnaire_id'] ?? 0);
    $questions = $this->loadQuestions($questionnaireId);

    $currentIndex = null;
    foreach ($questions as $index => $item) {
      if ((int) ($item['id'] ?? 0) === $questionId) {
        $currentIndex = $index;
        break;
      }
    }

    if ($currentIndex === null) {
      if ($this->wantsJson()) {
        return $this->response->setStatusCode(404)->setJSON([
          'success' => false,
          'message' => 'Pertanyaan tidak ditemukan pada daftar kuesioner ini.',
        ]);
      }

      return redirect()->to(base_url('compliance/questionnaires/' . $questionnaireId))->with('error', 'Pertanyaan tidak ditemukan pada daftar kuesioner ini.');
    }

    $targetIndex = $currentIndex + $direction;
    if ($targetIndex < 0 || $targetIndex >= count($questions)) {
      if ($this->wantsJson()) {
        return $this->response->setStatusCode(409)->setJSON([
          'success' => false,
          'message' => 'Urutan pertanyaan sudah berada di batas.',
        ]);
      }

      return redirect()->to(base_url('compliance/questionnaires/' . $questionnaireId))->with('info', 'Urutan pertanyaan sudah berada di batas.');
    }

    $this->resequenceQuestionOrder($questionnaireId, $questionId, $targetIndex + 1, false);

    if ($this->wantsJson()) {
      return $this->questionMutationResponse($questionnaireId, 'Urutan pertanyaan berhasil diperbarui.', $questionId);
    }

    return redirect()->to(base_url('compliance/questionnaires/' . $questionnaireId))->with('success', 'Urutan pertanyaan berhasil diperbarui.');
  }

  protected function buildQuestionnairePayload(?int $ignoreId = null): array
  {
    $title = trim((string) $this->request->getPost('title'));
    if ($title === '') {
      return ['error' => 'Judul kuesioner wajib diisi.'];
    }

    $slugInput = trim((string) $this->request->getPost('slug'));
    $slug = $this->makeUniqueSlug($slugInput !== '' ? $slugInput : $title, $ignoreId);

    return [
      'title' => $title,
      'slug' => $slug,
      'subtitle' => $this->nullableText($this->request->getPost('subtitle')),
      'description' => $this->nullableText($this->request->getPost('description')),
      'footer_note' => $this->nullableText($this->request->getPost('footer_note')),
      'active' => $this->request->getPost('active') ? 1 : 0,
      'sort_order' => (int) ($this->request->getPost('sort_order') ?: 0),
    ];
  }

  protected function buildQuestionPayload(int $questionnaireId): array
  {
    $questionText = trim((string) $this->request->getPost('question_text'));
    $answerType = trim((string) $this->request->getPost('answer_type'));
    $allowedTypes = ['radio', 'text', 'textarea', 'date', 'email', 'phone', 'number', 'select', 'scale_5', 'scale_10'];

    if ($questionText === '') {
      return ['error' => 'Teks pertanyaan wajib diisi.'];
    }

    if (!in_array($answerType, $allowedTypes, true)) {
      return ['error' => 'Tipe jawaban tidak valid.'];
    }

    $optionsJson = null;
    $scaleLowLabel = null;
    $scaleHighLabel = null;

    if (in_array($answerType, ['radio', 'select'], true)) {
      $options = $this->parseOptionLines((string) $this->request->getPost('options_text'));
      if (count($options) < 2) {
        return ['error' => 'Pertanyaan pilihan harus memiliki minimal dua opsi.'];
      }
      $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);
    } elseif ($this->isScaleType($answerType)) {
      $optionsJson = json_encode($this->scaleOptionsForType($answerType), JSON_UNESCAPED_UNICODE);
      $scaleLowLabel = $this->nullableText($this->request->getPost('scale_low_label'));
      $scaleHighLabel = $this->nullableText($this->request->getPost('scale_high_label'));
    }

    return [
      'questionnaire_id' => $questionnaireId,
      'section_label' => $this->resolveSectionLabelInput(),
      'question_code' => null,
      'sort_order' => 0,
      'question_text' => $questionText,
      'answer_type' => $answerType,
      'options_json' => $optionsJson,
      'scale_low_label' => $scaleLowLabel,
      'scale_high_label' => $scaleHighLabel,
      'placeholder' => $this->nullableText($this->request->getPost('placeholder')),
      'help_text' => $this->nullableText($this->request->getPost('help_text')),
      'is_required' => $this->request->getPost('is_required') ? 1 : 0,
    ];
  }

  protected function loadResponseDetail(int $responseId): ?array
  {
    $response = $this->responseModel->find($responseId);
    if (!$response) {
      return null;
    }

    $questionnaire = $this->findQuestionnaire((int) $response['questionnaire_id']);
    if (!$questionnaire) {
      return null;
    }

    $questions = $this->loadQuestions((int) $questionnaire['id']);
    $answers = $this->answerModel
      ->where('response_id', $responseId)
      ->findAll();

    $answersMap = [];
    foreach ($answers as $answer) {
      $answersMap[(int) $answer['question_id']] = (string) ($answer['answer_value'] ?? '');
    }

    return [
      'questionnaire' => $questionnaire,
      'response' => $response,
      'questionGroups' => $this->groupQuestionsBySection($questions),
      'answersMap' => $answersMap,
    ];
  }

  protected function loadQuestions(int $questionnaireId): array
  {
    $questions = $this->questionModel
      ->where('questionnaire_id', $questionnaireId)
      ->orderBy('sort_order', 'ASC')
      ->orderBy('id', 'ASC')
      ->findAll();

    $displayOrder = 1;
    foreach ($questions as &$question) {
      $question['options'] = $this->resolveQuestionOptions($question);
      $question['display_order'] = $displayOrder;
      $question['is_auto_timestamp'] = $this->isAutoTimestampQuestion($question);
      $displayOrder++;
    }
    unset($question);

    return $questions;
  }

  protected function groupQuestionsBySection(array $questions): array
  {
    $groups = [];
    foreach ($questions as $question) {
      $section = trim((string) ($question['section_label'] ?? ''));
      if ($section === '') {
        $section = 'Pertanyaan';
      }

      if (!isset($groups[$section])) {
        $groups[$section] = [];
      }

      $groups[$section][] = $question;
    }

    return $groups;
  }

  protected function loadQuestionnaireTemplatesWithCounts(): array
  {
    $templates = $this->questionnaireModel
      ->orderBy('sort_order', 'ASC')
      ->orderBy('title', 'ASC')
      ->findAll();

    $questionCounts = $this->aggregateCounts('compliance_questionnaire_questions', 'questionnaire_id');
    $responseCounts = $this->aggregateCounts('compliance_questionnaire_responses', 'questionnaire_id');
    $latestRows = db_connect()->table('compliance_questionnaire_responses')
      ->select('questionnaire_id, MAX(submitted_at) AS latest_submitted', false)
      ->groupBy('questionnaire_id')
      ->get()
      ->getResultArray();

    $latestMap = [];
    foreach ($latestRows as $row) {
      $latestMap[(int) ($row['questionnaire_id'] ?? 0)] = (string) ($row['latest_submitted'] ?? '');
    }

    foreach ($templates as &$template) {
      $templateId = (int) ($template['id'] ?? 0);
      $template['question_count'] = (int) ($questionCounts[$templateId] ?? 0);
      $template['response_count'] = (int) ($responseCounts[$templateId] ?? 0);
      $template['latest_submitted'] = $latestMap[$templateId] ?? null;
      $template['public_path'] = $this->publicQuestionnairePath($template);
    }
    unset($template);

    return $templates;
  }

  protected function buildAnalyticsOverview(array $templates): array
  {
    $todayStart = date('Y-m-d 00:00:00');
    $weekStart = date('Y-m-d 00:00:00', strtotime('-6 days'));

    return [
      'total_templates' => count($templates),
      'active_templates' => count(array_filter($templates, static fn(array $item): bool => (int) ($item['active'] ?? 0) === 1)),
      'total_responses' => (int) $this->responseModel->countAllResults(),
      'responses_today' => (int) $this->responseModel->where('submitted_at >=', $todayStart)->countAllResults(),
      'responses_week' => (int) $this->responseModel->where('submitted_at >=', $weekStart)->countAllResults(),
      'total_questions' => array_sum(array_map(static fn(array $item): int => (int) ($item['question_count'] ?? 0), $templates)),
    ];
  }

  protected function buildSubmissionTrend(?int $questionnaireId = null, int $days = 7): array
  {
    $startDate = strtotime('-' . max(0, $days - 1) . ' days');
    $labels = [];
    $counts = [];

    for ($index = 0; $index < $days; $index++) {
      $date = date('Y-m-d', strtotime('+' . $index . ' days', $startDate));
      $labels[$date] = [
        'date' => $date,
        'label' => date('d M', strtotime($date)),
        'count' => 0,
      ];
      $counts[$date] = 0;
    }

    $builder = db_connect()->table('compliance_questionnaire_responses')
      ->select('DATE(submitted_at) AS submit_date, COUNT(*) AS total', false)
      ->where('submitted_at >=', date('Y-m-d 00:00:00', $startDate))
      ->groupBy('DATE(submitted_at)');

    if ($questionnaireId !== null) {
      $builder->where('questionnaire_id', $questionnaireId);
    }

    $rows = $builder->get()->getResultArray();
    foreach ($rows as $row) {
      $date = (string) ($row['submit_date'] ?? '');
      if (!isset($labels[$date])) {
        continue;
      }
      $labels[$date]['count'] = (int) ($row['total'] ?? 0);
      $counts[$date] = (int) ($row['total'] ?? 0);
    }

    $max = max($counts ?: [1]);
    foreach ($labels as &$item) {
      $item['width'] = $max > 0 ? max(6, (int) round(($item['count'] / $max) * 100)) : 0;
    }
    unset($item);

    return array_values($labels);
  }

  protected function loadRecentResponses(?int $questionnaireId = null, int $limit = 12): array
  {
    $builder = db_connect()->table('compliance_questionnaire_responses r')
      ->select('r.id, r.questionnaire_id, r.response_code, r.respondent_name, r.phone, r.email, r.submitted_at, q.title AS questionnaire_title')
      ->join('compliance_questionnaires q', 'q.id = r.questionnaire_id', 'left')
      ->orderBy('r.submitted_at', 'DESC')
      ->orderBy('r.id', 'DESC')
      ->limit($limit);

    if ($questionnaireId !== null) {
      $builder->where('r.questionnaire_id', $questionnaireId);
    }

    $rows = $builder->get()->getResultArray();
    foreach ($rows as &$row) {
      $row['detail_path'] = $this->adminResponsePath((int) ($row['id'] ?? 0));
      $row['pdf_path'] = $this->adminResponsePdfPath((int) ($row['id'] ?? 0));
    }
    unset($row);

    return $rows;
  }

  protected function buildQuestionAnalyses(int $questionnaireId): array
  {
    $questions = $this->loadQuestions($questionnaireId);
    $responses = $this->responseModel
      ->where('questionnaire_id', $questionnaireId)
      ->orderBy('submitted_at', 'DESC')
      ->orderBy('id', 'DESC')
      ->findAll();

    if (empty($questions) || empty($responses)) {
      return [];
    }

    $responseMap = [];
    $responseIds = [];
    foreach ($responses as $response) {
      $responseId = (int) ($response['id'] ?? 0);
      $responseIds[] = $responseId;
      $responseMap[$responseId] = $response;
    }

    $answerRows = $this->answerModel
      ->whereIn('response_id', $responseIds)
      ->findAll();

    $answersByQuestion = [];
    foreach ($answerRows as $answerRow) {
      $questionId = (int) ($answerRow['question_id'] ?? 0);
      $responseId = (int) ($answerRow['response_id'] ?? 0);
      $answersByQuestion[$questionId][] = [
        'value' => trim((string) ($answerRow['answer_value'] ?? '')),
        'response' => $responseMap[$responseId] ?? null,
      ];
    }

    $analyses = [];
    foreach ($questions as $question) {
      if (!empty($question['is_auto_timestamp'])) {
        continue;
      }

      $questionId = (int) ($question['id'] ?? 0);
      $entries = array_values(array_filter($answersByQuestion[$questionId] ?? [], static fn(array $item): bool => ($item['value'] ?? '') !== ''));
      $analysis = [
        'id' => $questionId,
        'display_order' => (int) ($question['display_order'] ?? 0),
        'question_text' => (string) ($question['question_text'] ?? ''),
        'help_text' => (string) ($question['help_text'] ?? ''),
        'answer_type' => (string) ($question['answer_type'] ?? 'text'),
        'response_count' => count($entries),
        'required' => (int) ($question['is_required'] ?? 0) === 1,
      ];

      if (!empty($question['options'])) {
        $counts = [];
        foreach ($question['options'] as $option) {
          $counts[(string) $option] = 0;
        }

        $numericValues = [];
        foreach ($entries as $entry) {
          $value = (string) ($entry['value'] ?? '');
          if (array_key_exists($value, $counts)) {
            $counts[$value]++;
          }

          if ($this->isScaleType((string) ($question['answer_type'] ?? '')) && is_numeric($value)) {
            $numericValues[] = (float) $value;
          }
        }

        $distribution = [];
        $maxCount = max($counts ?: [0]);
        foreach ($counts as $label => $count) {
          $distribution[] = [
            'label' => $label,
            'count' => $count,
            'percent' => count($entries) > 0 ? round(($count / count($entries)) * 100, 1) : 0,
            'width' => $maxCount > 0 ? max(6, (int) round(($count / $maxCount) * 100)) : 0,
          ];
        }

        $analysis['distribution'] = $distribution;

        if ($this->isScaleType((string) ($question['answer_type'] ?? ''))) {
          $analysis['scale_low_label'] = (string) ($question['scale_low_label'] ?? '');
          $analysis['scale_high_label'] = (string) ($question['scale_high_label'] ?? '');
          $analysis['average_score'] = !empty($numericValues) ? round(array_sum($numericValues) / count($numericValues), 2) : null;
        }
      } elseif ((string) ($question['answer_type'] ?? '') === 'number') {
        $numericValues = array_values(array_filter(array_map(static fn(array $entry) => is_numeric($entry['value']) ? (float) $entry['value'] : null, $entries), static fn($value) => $value !== null));
        $analysis['number_stats'] = [
          'min' => !empty($numericValues) ? min($numericValues) : null,
          'max' => !empty($numericValues) ? max($numericValues) : null,
          'avg' => !empty($numericValues) ? round(array_sum($numericValues) / count($numericValues), 2) : null,
        ];
        $analysis['sample_answers'] = $this->takeAnswerSamples($entries);
      } else {
        $analysis['sample_answers'] = $this->takeAnswerSamples($entries);
      }

      $analyses[] = $analysis;
    }

    return $analyses;
  }

  protected function takeAnswerSamples(array $entries, int $limit = 5): array
  {
    $samples = [];
    foreach (array_slice($entries, 0, $limit) as $entry) {
      $response = $entry['response'] ?? [];
      $samples[] = [
        'value' => $entry['value'] ?? '',
        'respondent_name' => (string) ($response['respondent_name'] ?? 'Anonim'),
        'submitted_at' => (string) ($response['submitted_at'] ?? ''),
      ];
    }

    return $samples;
  }

  protected function aggregateCounts(string $table, string $groupField): array
  {
    $rows = db_connect()->table($table)
      ->select($groupField . ', COUNT(*) AS total', false)
      ->groupBy($groupField)
      ->get()
      ->getResultArray();

    $map = [];
    foreach ($rows as $row) {
      $map[(int) $row[$groupField]] = (int) ($row['total'] ?? 0);
    }

    return $map;
  }

  protected function decodeOptions(?string $json): array
  {
    if ($json === null || trim($json) === '') {
      return [];
    }

    $decoded = json_decode($json, true);
    return is_array($decoded)
      ? array_values(array_filter(array_map('strval', $decoded), static fn(string $item): bool => trim($item) !== ''))
      : [];
  }

  protected function resolveQuestionOptions(array $question): array
  {
    $answerType = trim((string) ($question['answer_type'] ?? ''));
    if ($this->isScaleType($answerType)) {
      $decoded = $this->decodeOptions($question['options_json'] ?? null);
      return !empty($decoded) ? $decoded : $this->scaleOptionsForType($answerType);
    }

    return $this->decodeOptions($question['options_json'] ?? null);
  }

  protected function parseOptionLines(string $text): array
  {
    $lines = preg_split('/\\r\\n|\\r|\\n/', trim($text)) ?: [];
    $lines = array_map(static fn(string $line): string => trim($line), $lines);

    return array_values(array_filter($lines, static fn(string $line): bool => $line !== ''));
  }

  protected function parseRequestedPosition(): ?int
  {
    $raw = trim((string) $this->request->getPost('sort_order'));
    if ($raw === '') {
      return null;
    }

    $position = (int) $raw;
    return $position > 0 ? $position : null;
  }

  protected function resolveSectionLabelInput(): ?string
  {
    $selected = trim((string) $this->request->getPost('section_label'));
    $custom = trim((string) $this->request->getPost('section_label_custom'));

    if ($selected === '__new__') {
      return $custom !== '' ? $custom : null;
    }

    if ($selected !== '') {
      return $selected;
    }

    return $custom !== '' ? $custom : null;
  }

  protected function resequenceQuestionOrder(int $questionnaireId, int $focusQuestionId, ?int $requestedPosition, bool $appendIfEmpty): void
  {
    $questions = $this->questionModel
      ->where('questionnaire_id', $questionnaireId)
      ->orderBy('sort_order', 'ASC')
      ->orderBy('id', 'ASC')
      ->findAll();

    $currentIndex = null;
    foreach ($questions as $index => $question) {
      if ((int) ($question['id'] ?? 0) === $focusQuestionId) {
        $currentIndex = $index;
        break;
      }
    }

    if ($currentIndex === null) {
      return;
    }

    $focusQuestion = $questions[$currentIndex];
    unset($questions[$currentIndex]);
    $questions = array_values($questions);

    if ($requestedPosition === null) {
      $targetIndex = $appendIfEmpty ? count($questions) : $currentIndex;
    } else {
      $targetIndex = max(0, min(count($questions), $requestedPosition - 1));
    }

    array_splice($questions, $targetIndex, 0, [$focusQuestion]);

    $sortOrder = 10;
    foreach ($questions as $question) {
      $this->questionModel->update((int) $question['id'], ['sort_order' => $sortOrder]);
      $sortOrder += 10;
    }
  }

  protected function normalizeQuestionOrder(int $questionnaireId): void
  {
    $questions = $this->questionModel
      ->where('questionnaire_id', $questionnaireId)
      ->orderBy('sort_order', 'ASC')
      ->orderBy('id', 'ASC')
      ->findAll();

    $sortOrder = 10;
    foreach ($questions as $question) {
      $this->questionModel->update((int) $question['id'], ['sort_order' => $sortOrder]);
      $sortOrder += 10;
    }
  }

  protected function extractSectionOptions(array $questions): array
  {
    $options = [];

    foreach ($questions as $question) {
      $section = trim((string) ($question['section_label'] ?? ''));
      if ($section === '' || in_array($section, $options, true)) {
        continue;
      }

      $options[] = $section;
    }

    return $options;
  }

  protected function makeUniqueSlug(string $value, ?int $ignoreId = null): string
  {
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    if ($slug === '') {
      $slug = 'kuesioner';
    }

    $candidate = $slug;
    $counter = 2;

    while (true) {
      $builder = $this->questionnaireModel->where('slug', $candidate);
      if ($ignoreId !== null) {
        $builder->where('id !=', $ignoreId);
      }

      $exists = $builder->first();
      if (!$exists) {
        return $candidate;
      }

      $candidate = $slug . '-' . $counter;
      $counter++;
    }
  }

  protected function nullableText($value): ?string
  {
    $text = trim((string) $value);
    return $text !== '' ? $text : null;
  }

  protected function findQuestionnaire($id): ?array
  {
    return $this->questionnaireModel->find((int) $id);
  }

  protected function findPublicQuestionnaire(string $slug): array
  {
    $questionnaire = $this->questionnaireModel->where('slug', $slug)->first();
    if (!$questionnaire || !(int) ($questionnaire['active'] ?? 0)) {
      throw PageNotFoundException::forPageNotFound('Kuesioner tidak tersedia.');
    }

    return $questionnaire;
  }

  protected function generateResponseCode(int $questionnaireId): string
  {
    $prefix = 'QNR-' . date('Ymd-His') . '-' . $questionnaireId;
    $candidate = $prefix;
    $counter = 1;

    while ($this->responseModel->where('response_code', $candidate)->first()) {
      $counter++;
      $candidate = $prefix . '-' . $counter;
    }

    return $candidate;
  }

  protected function relativePath(string $uri): string
  {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  }

  protected function publicQuestionnairePath(array $questionnaire): string
  {
    return $this->relativePath('kuesioner/' . rawurlencode((string) ($questionnaire['slug'] ?? '')));
  }

  protected function adminResponsePath(int $responseId): string
  {
    return $this->relativePath('compliance/questionnaires/response/' . $responseId);
  }

  protected function adminResponsePdfPath(int $responseId): string
  {
    return $this->relativePath('compliance/questionnaires/response/' . $responseId . '/pdf');
  }

  protected function adminExcelPath(int $questionnaireId): string
  {
    return $this->relativePath('compliance/questionnaires/' . $questionnaireId . '/excel');
  }

  protected function questionColumnLabel(array $question): string
  {
    $text = trim((string) ($question['question_text'] ?? 'Pertanyaan'));
    return $text;
  }

  protected function answerTypeLabels(): array
  {
    return [
      'radio' => 'Pilihan satu jawaban',
      'select' => 'Daftar pilihan',
      'scale_5' => 'Skala 1-5',
      'scale_10' => 'Skala 1-10',
      'text' => 'Jawaban singkat',
      'textarea' => 'Paragraf',
      'date' => 'Tanggal',
      'email' => 'Alamat email',
      'phone' => 'Nomor telepon',
      'number' => 'Angka',
    ];
  }

  protected function respondentFields(array $questionnaire): array
  {
    return [
      'name' => (int) ($questionnaire['collect_name'] ?? 1) === 1,
      'phone' => (int) ($questionnaire['collect_phone'] ?? 1) === 1,
      'email' => (int) ($questionnaire['collect_email'] ?? 1) === 1,
    ];
  }

  protected function isAutoTimestampQuestion(array $question): bool
  {
    $text = strtolower(trim((string) ($question['question_text'] ?? '')));

    return str_contains($text, 'tanggal pengisian formulir')
      || str_contains($text, 'tanggal pengisian form');
  }

  protected function isScaleType(string $answerType): bool
  {
    return in_array($answerType, ['scale_5', 'scale_10'], true);
  }

  protected function scaleOptionsForType(string $answerType): array
  {
    $max = $answerType === 'scale_10' ? 10 : 5;
    $options = [];

    for ($index = 1; $index <= $max; $index++) {
      $options[] = (string) $index;
    }

    return $options;
  }

  protected function wantsJson(): bool
  {
    return $this->request->isAJAX()
      || str_contains(strtolower((string) $this->request->getHeaderLine('Accept')), 'application/json');
  }

  protected function questionMutationResponse(int $questionnaireId, string $message, ?int $openQuestionId = null)
  {
    $questionnaire = $this->findQuestionnaire($questionnaireId);
    if (!$questionnaire) {
      return $this->response->setStatusCode(404)->setJSON([
        'success' => false,
        'message' => 'Kuesioner tidak ditemukan.',
      ]);
    }

    $questions = $this->loadQuestions($questionnaireId);
    $sectionOptions = $this->extractSectionOptions($questions);

    $html = view('compliance/questionnaire/_question_list', [
      'questions' => $questions,
      'questionGroups' => $this->groupQuestionsBySection($questions),
      'sectionOptions' => $sectionOptions,
      'answerTypeLabels' => $this->answerTypeLabels(),
      'isWriteAllowed' => hasRole(['admin', 'compliance']),
      'openQuestionId' => $openQuestionId,
    ]);

    return $this->response->setJSON([
      'success' => true,
      'message' => $message,
      'html' => $html,
      'sectionOptions' => $sectionOptions,
      'openQuestionId' => $openQuestionId,
    ]);
  }

  protected function bootstrapDefaultsIfNeeded(): void
  {
    $db = db_connect();
    if (!$db->tableExists('compliance_questionnaires') || !$db->tableExists('compliance_questionnaire_questions')) {
      return;
    }

    foreach (ComplianceQuestionnaireCatalog::defaults() as $template) {
      $existing = $this->questionnaireModel->where('slug', $template['slug'])->first();
      if (!$existing) {
        $this->questionnaireModel->insert([
          'slug' => $template['slug'],
          'title' => $template['title'],
          'subtitle' => $template['subtitle'] ?? null,
          'description' => $template['description'] ?? null,
          'footer_note' => $template['footer_note'] ?? null,
          'active' => 1,
          'sort_order' => (int) ($template['sort_order'] ?? 0),
        ]);

        $questionnaireId = (int) $this->questionnaireModel->getInsertID();
        foreach ($template['questions'] as $question) {
          $this->questionModel->insert([
            'questionnaire_id' => $questionnaireId,
            'section_label' => $question['section_label'] ?? null,
            'question_code' => $question['question_code'] ?? null,
            'sort_order' => (int) ($question['sort_order'] ?? 0),
            'question_text' => $question['question_text'],
            'answer_type' => $question['answer_type'] ?? 'radio',
            'options_json' => !empty($question['options']) ? json_encode($question['options'], JSON_UNESCAPED_UNICODE) : null,
            'placeholder' => $question['placeholder'] ?? null,
            'help_text' => $question['help_text'] ?? null,
            'is_required' => (int) ($question['is_required'] ?? 1),
          ]);
        }

        continue;
      }

      $hasQuestions = $this->questionModel->where('questionnaire_id', $existing['id'])->countAllResults() > 0;
      if ($hasQuestions) {
        continue;
      }

      foreach ($template['questions'] as $question) {
        $this->questionModel->insert([
          'questionnaire_id' => (int) $existing['id'],
          'section_label' => $question['section_label'] ?? null,
          'question_code' => $question['question_code'] ?? null,
          'sort_order' => (int) ($question['sort_order'] ?? 0),
          'question_text' => $question['question_text'],
          'answer_type' => $question['answer_type'] ?? 'radio',
          'options_json' => !empty($question['options']) ? json_encode($question['options'], JSON_UNESCAPED_UNICODE) : null,
          'placeholder' => $question['placeholder'] ?? null,
          'help_text' => $question['help_text'] ?? null,
          'is_required' => (int) ($question['is_required'] ?? 1),
        ]);
      }
    }
  }

  protected function guardRead()
  {
    if (hasRole(['admin', 'compliance', 'auditor', 'staff'])) {
      return null;
    }

    return redirect()->to(base_url('home'))->with('error', 'Anda tidak memiliki akses ke menu kuesioner.');
  }

  protected function guardWrite()
  {
    if (hasRole(['admin', 'compliance'])) {
      return null;
    }

    return redirect()->to(base_url('compliance/questionnaires'))->with('error', 'Anda tidak memiliki akses untuk mengubah data kuesioner.');
  }
}
