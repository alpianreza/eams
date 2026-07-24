<?php

namespace App\Controllers;

use App\Models\ThermalImagingLocationModel;
use App\Models\ThermalImagingReportItemModel;
use App\Models\ThermalImagingReportModel;
use Config\Database;
use Dompdf\Dompdf;
use Dompdf\Options;

class ThermalImagingController extends BaseController
{
  protected ThermalImagingLocationModel $locationModel;
  protected ThermalImagingReportModel $reportModel;
  protected ThermalImagingReportItemModel $itemModel;

  public function __construct()
  {
    $this->locationModel = new ThermalImagingLocationModel();
    $this->reportModel = new ThermalImagingReportModel();
    $this->itemModel = new ThermalImagingReportItemModel();
  }

  public function index()
  {
    if ($redirect = $this->guardReportAccess()) {
      return $redirect;
    }

    page('Thermal Imaging Report');

    $reports = $this->reportModel
      ->select('thermal_imaging_reports.*, users.name AS creator_name')
      ->join('users', 'users.id = thermal_imaging_reports.created_by', 'left')
      ->orderBy('inspection_date', 'DESC')
      ->orderBy('id', 'DESC')
      ->findAll();

    return view('compliance/thermal_imaging/index', [
      'reports' => $reports,
      'canManageLocations' => $this->canManageLocations(),
    ]);
  }

  public function create()
  {
    if ($redirect = $this->guardReportAccess()) {
      return $redirect;
    }

    page('Buat Thermal Imaging Report', 'compliance/thermal-imaging');

    return view('compliance/thermal_imaging/form', [
      'locations' => $this->activeLocations(),
      'canManageLocations' => $this->canManageLocations(),
      'defaultInspector' => (string) session()->get('name'),
      'defaultFacility' => 'PT.Younghyun Star',
    ]);
  }

  public function store()
  {
    if ($redirect = $this->guardReportAccess()) {
      return $redirect;
    }

    $inspectionDate = trim((string) $this->request->getPost('inspection_date'));
    $inspectorName = trim((string) $this->request->getPost('inspector_name'));
    $facility = trim((string) $this->request->getPost('facility'));

    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $inspectionDate)) {
      return redirect()->back()->withInput()->with('error', 'Tanggal inspeksi wajib diisi dengan benar.');
    }

    if ($inspectorName === '' || $facility === '') {
      return redirect()->back()->withInput()->with('error', 'General information wajib dilengkapi.');
    }

    $rows = $this->buildItemRows();
    if (isset($rows['error'])) {
      return redirect()->back()->withInput()->with('error', $rows['error']);
    }

    if (empty($rows)) {
      return redirect()->back()->withInput()->with('error', 'Minimal satu baris inspeksi wajib diisi.');
    }

    $db = Database::connect();
    $db->transStart();

    $reportId = $this->reportModel->insert([
      'inspection_date' => $inspectionDate,
      'inspector_name' => $inspectorName,
      'facility' => $facility,
      'area_name' => '',
      'created_by' => session()->get('user_id'),
    ]);

    foreach ($rows as $rowIndex => $row) {
      $row['report_id'] = $reportId;
      $row['sort_order'] = $rowIndex + 1;
      $this->itemModel->insert($row);
    }

    $db->transComplete();

    if (! $db->transStatus()) {
      return redirect()->back()->withInput()->with('error', 'Laporan gagal disimpan.');
    }

    return redirect()
      ->to('/compliance/thermal-imaging/' . $reportId)
      ->with('success', 'Thermal imaging report berhasil dibuat.');
  }

  public function show($id)
  {
    if ($redirect = $this->guardReportAccess()) {
      return $redirect;
    }

    $data = $this->reportData((int) $id);
    if (! $data) {
      return redirect()->to('/compliance/thermal-imaging')->with('error', 'Laporan tidak ditemukan.');
    }

    page('Output Thermal Imaging Report', 'compliance/thermal-imaging');

    return view('compliance/thermal_imaging/show', $data + [
      'isPdf' => false,
    ]);
  }

  public function pdf($id)
  {
    if ($redirect = $this->guardReportAccess()) {
      return $redirect;
    }

    $data = $this->reportData((int) $id);
    if (! $data) {
      return redirect()->to('/compliance/thermal-imaging')->with('error', 'Laporan tidak ditemukan.');
    }

    $html = view('compliance/thermal_imaging/print', $data + [
      'isPdf' => true,
    ]);

    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'Thermal_Imaging_Report_' . date('Ymd', strtotime($data['report']['inspection_date'])) . '_' . (int) $id . '.pdf';

    return $this->response
      ->setHeader('Content-Type', 'application/pdf')
      ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
      ->setBody($dompdf->output());
  }

  public function storeLocation()
  {
    if (! $this->canManageLocations()) {
      return $this->response->setStatusCode(403)->setJSON([
        'status' => 'error',
        'message' => 'Hanya admin dan compliance yang bisa menambah lokasi.',
        'csrf' => [
          'name' => csrf_token(),
          'hash' => csrf_hash(),
        ],
      ]);
    }

    $name = trim((string) $this->request->getPost('name'));
    $section = trim((string) $this->request->getPost('section'));

    if ($name === '') {
      return $this->response->setStatusCode(422)->setJSON([
        'status' => 'error',
        'message' => 'Nama lokasi wajib diisi.',
        'csrf' => [
          'name' => csrf_token(),
          'hash' => csrf_hash(),
        ],
      ]);
    }

    $existing = $this->locationModel
      ->where('name', $name)
      ->first();

    if ($existing) {
      if ((int) ($existing['active'] ?? 0) !== 1) {
        $this->locationModel->update((int) $existing['id'], [
          'section' => $section !== '' ? $section : null,
          'active' => 1,
        ]);
      }

      return $this->response->setJSON([
        'status' => 'success',
        'location' => [
          'id' => (int) $existing['id'],
          'name' => $name,
          'section' => $section,
        ],
        'csrf' => [
          'name' => csrf_token(),
          'hash' => csrf_hash(),
        ],
      ]);
    }

    $id = $this->locationModel->insert([
      'name' => $name,
      'section' => $section !== '' ? $section : null,
      'active' => 1,
      'created_by' => session()->get('user_id'),
    ]);

    return $this->response->setJSON([
      'status' => 'success',
      'location' => [
        'id' => (int) $id,
        'name' => $name,
        'section' => $section,
      ],
      'csrf' => [
        'name' => csrf_token(),
        'hash' => csrf_hash(),
      ],
    ]);
  }

  private function guardReportAccess()
  {
    if (! hasRole(['admin', 'compliance', 'staff'])) {
      return redirect()->to('/unauthorized')->with('error', 'Akses ditolak.');
    }

    return null;
  }

  private function canManageLocations(): bool
  {
    return in_array((string) session()->get('role'), ['admin', 'compliance'], true);
  }

  private function activeLocations(): array
  {
    return $this->locationModel
      ->where('active', 1)
      ->orderBy('section', 'ASC')
      ->orderBy('name', 'ASC')
      ->findAll();
  }

  private function buildItemRows(): array
  {
    $locationIds = (array) $this->request->getPost('location_id');
    $celsiusValues = (array) $this->request->getPost('celsius');
    $findings = (array) $this->request->getPost('findings');
    $recommendations = (array) $this->request->getPost('recommendation');
    $files = $this->request->getFileMultiple('thermal_images') ?: [];

    $locationMap = [];
    foreach ($this->activeLocations() as $location) {
      $locationMap[(int) $location['id']] = $location;
    }

    $rows = [];
    $rowCount = max(count($locationIds), count($celsiusValues), count($findings), count($recommendations), count($files));

    for ($index = 0; $index < $rowCount; $index++) {
      $locationId = (int) ($locationIds[$index] ?? 0);
      $celsius = trim((string) ($celsiusValues[$index] ?? ''));
      $finding = trim((string) ($findings[$index] ?? ''));
      $recommendation = trim((string) ($recommendations[$index] ?? ''));
      $file = $files[$index] ?? null;
      $hasFile = $file && $file->isValid() && ! $file->hasMoved();

      if ($locationId < 1 && $celsius === '' && $finding === '' && $recommendation === '' && ! $hasFile) {
        continue;
      }

      if ($locationId < 1 || ! isset($locationMap[$locationId])) {
        return ['error' => 'Pilih lokasi dari daftar untuk setiap baris inspeksi.'];
      }

      if ($celsius === '' || ! is_numeric($celsius)) {
        return ['error' => 'Nilai Celsius wajib berupa angka untuk setiap baris inspeksi.'];
      }

      $imagePath = null;
      if ($hasFile) {
        $mime = (string) $file->getMimeType();
        if (! str_starts_with($mime, 'image/')) {
          return ['error' => 'Thermal image harus berupa file gambar.'];
        }

        if ($file->getSizeByUnit('mb') > 5) {
          return ['error' => 'Ukuran thermal image maksimal 5 MB per file.'];
        }

        $directory = FCPATH . 'uploads/thermal-imaging/' . date('Y/m');
        if (! is_dir($directory)) {
          mkdir($directory, 0775, true);
        }

        $fileName = $file->getRandomName();
        $file->move($directory, $fileName, true);
        $imagePath = 'uploads/thermal-imaging/' . date('Y/m') . '/' . $fileName;
      }

      $rows[] = [
        'location_id' => $locationId,
        'location_name' => (string) $locationMap[$locationId]['name'],
        'celsius' => (float) $celsius,
        'thermal_image' => $imagePath,
        'findings' => $finding !== '' ? $finding : null,
        'recommendation' => $recommendation !== '' ? $recommendation : null,
      ];
    }

    return $rows;
  }

  private function reportData(int $id): ?array
  {
    $report = $this->reportModel->find($id);
    if (! $report) {
      return null;
    }

    $items = $this->itemModel
      ->where('report_id', $id)
      ->orderBy('sort_order', 'ASC')
      ->orderBy('id', 'ASC')
      ->findAll();

    return [
      'report' => $report,
      'items' => $items,
    ];
  }
}
