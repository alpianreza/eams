<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Dompdf\Dompdf;
use Dompdf\Options;
use Mpdf\Mpdf;

/**
 * Checklist PDF Controller
 * FINAL - sesuai struktur compliance_inventory & checklist_master
 */
class ChecklistPdfController extends BaseController
{
  protected $inventoryModel;
  protected $checklistMasterModel;
  protected $checklistLogModel;
  protected $itemTypeModel;

  public function __construct()
  {
    $this->inventoryModel       = model('ComplianceInventoryModel');
    $this->checklistMasterModel = model('ChecklistMasterModel');
    $this->checklistLogModel    = model('ChecklistLogModel');
    $this->itemTypeModel        = model('AssetItemTypeModel'); // master item (APAR, dll)
  }

  /* =====================================================
     * CORE RENDER PDF
     * ===================================================== */
  protected function renderPdf(string $view, array $data, string $filename)
  {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);

    $html = view($view, $data);
    $dompdf->loadHtml($html);

    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $this->response
      ->setHeader('Content-Type', 'application/pdf')
      ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
      ->setBody($dompdf->output());
  }

  /* =====================================================
     * A. PRINT SATUAN (1 INVENTORY)
     * ===================================================== */
  public function singleItem(int $inventoryId, string $periodKey)
  {
    // 1️⃣ Inventory
    $inventory = $this->inventoryModel->find($inventoryId);
    if (! $inventory) {
      throw new \RuntimeException('Inventory tidak ditemukan');
    }

    // 2️⃣ Item Type (APAR / Hydrant / dll)
    $itemType = $this->itemTypeModel->find($inventory['item_type_id']);
    if (! $itemType) {
      throw new \RuntimeException('Item type tidak ditemukan');
    }

    // 3️⃣ Ambil pertanyaan checklist
    $masters = $this->checklistMasterModel
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    // 4️⃣ Mapping pertanyaan + status
    $questions = [];

    foreach ($masters as $m) {
      $log = $this->checklistLogModel
        ->where([
          'inventory_id'        => $inventory['id'],
          'checklist_template_id' => $m['id'],
          'period_key'          => $periodKey,
        ])
        ->first();

      $questions[] = [
        'question' => $m['question'],
        'status_symbol' => match ($log['status'] ?? null) {
          'ok' => '✓',
          'not_ok'  => '✗',
          'na'  => '–',
          default => '–',
        },
      ];
    }

    // 5️⃣ DATA KE VIEW (SEMUA DARI DB)
    $data = [
      'title'       => 'Checklist Pemeriksaan ' . $itemType['name'],
      'itemName'    => $itemType['name'],
      'inventoryNo' => $inventory['asset_code'],
      'location'    => $inventory['specific_area'], // ✅ FINAL
      'periodLabel' => $this->formatPeriodLabel($periodKey),
      'questions'   => $questions,
    ];

    return $this->renderPdf(
      'pdf/single_item',
      $data,
      'checklist-' . $inventory['asset_code'] . '.pdf'
    );
  }

  /* =====================================================
     * HELPER FORMAT PERIODE (DISPLAY ONLY)
     * ===================================================== */
  protected function formatPeriodLabel(string $periodKey): string
  {
    // contoh periodKey:
    // daily-2026-02-03
    // weekly-2026-02-w2
    // monthly-2026-02

    if (str_starts_with($periodKey, 'daily')) {
      return date('d F Y', strtotime(substr($periodKey, 6)));
    }

    if (str_starts_with($periodKey, 'weekly')) {
      return 'Minggu ' . substr($periodKey, -1);
    }

    if (str_starts_with($periodKey, 'monthly')) {
      return date('F Y', strtotime(substr($periodKey, 8) . '-01'));
    }

    return $periodKey;
  }

  public function recapMonthly(string $frequency, int $year, int $month)
  {
    // ===============================
    // VALIDASI FREKUENSI
    // ===============================
    if (! in_array($frequency, ['daily', 'weekly'])) {
      throw new \InvalidArgumentException('Frekuensi tidak valid');
    }

    // ===============================
    // KOLOM PERIODE
    // ===============================
    if ($frequency === 'daily') {
      $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
      $periodColumns = range(1, $daysInMonth);
      $periodLabel = date('F Y', strtotime("$year-$month-01"));
    } else {
      // weekly → selalu 1–4 (sesuai checkpoint)
      $periodColumns = [1, 2, 3, 4];
      $periodLabel = 'Bulan ' . date('F Y', strtotime("$year-$month-01"));
    }

    // ===============================
    // AMBIL SEMUA INVENTORY AKTIF
    // ===============================
    $inventories = $this->inventoryModel
      ->where('active', 1)
      ->orderBy('item_type_id')
      ->findAll();

    $items = [];

    foreach ($inventories as $inv) {

      // Ambil item type
      $itemType = $this->itemTypeModel->find($inv['item_type_id']);
      if (! $itemType) {
        continue;
      }

      // INIT STATUS PER KOLOM
      $statuses = [];
      foreach ($periodColumns as $p) {
        $statuses[$p] = '–';
      }

      // ===============================
      // AMBIL LOG CHECKLIST
      // ===============================
      $logs = $this->checklistLogModel
        ->where('inventory_id', $inv['id'])
        ->where('YEAR(check_date)', $year)
        ->where('MONTH(check_date)', $month)
        ->findAll();

      foreach ($logs as $log) {

        // Tentukan index kolom
        if ($frequency === 'daily') {
          $index = (int) date('j', strtotime($log['check_date']));
        } else {
          // weekly → minggu ke-1 s/d 4
          $weekOfMonth = ceil(date('j', strtotime($log['check_date'])) / 7);
          $index = min($weekOfMonth, 4);
        }

        // Status symbol dari log
        $symbol = match ($log['status']) {
          'not_ok'  => '✗',
          'ok' => '✓',
          'na'  => '–',
          default => '–',
        };

        // PRIORITAS STATUS
        if ($symbol === '✗') {
          $statuses[$index] = '✗';
        } elseif ($symbol === '✓' && $statuses[$index] !== '✗') {
          $statuses[$index] = '✓';
        }
      }

      // ===============================
      // PUSH KE TABEL
      // ===============================
      $items[] = [
        'item_name' => $itemType['name'],
        'location'  => $inv['specific_area'], // ✅ FINAL
        'statuses'  => $statuses,
      ];
    }

    // ===============================
    // DATA KE VIEW
    // ===============================
    $data = [
      'title'         => 'Rekap Checklist ' . ucfirst($frequency),
      'periodLabel'   => $periodLabel,
      'periodColumns' => $periodColumns,
      'items'         => $items,
    ];

    return $this->renderPdf(
      'pdf/recap_periodic',
      $data,
      'rekap-' . $frequency . '-' . $year . '-' . $month . '.pdf'
    );
  }

  public function recapItemYearly(int $itemTypeId, int $year)
  {
    // ===============================
    // ITEM TYPE
    // ===============================
    $itemType = $this->itemTypeModel->find($itemTypeId);
    if (! $itemType) {
      throw new \RuntimeException('Item type tidak ditemukan');
    }

    // ===============================
    // MASTER PERTANYAAN
    // ===============================
    $masters = $this->checklistMasterModel
      ->where('item_type_id', $itemTypeId)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    // ===============================
    // INVENTORY AKTIF UNTUK ITEM INI
    // ===============================
    $inventories = $this->inventoryModel
      ->where('item_type_id', $itemTypeId)
      ->where('active', 1)
      ->findAll();

    // ===============================
    // SIAPKAN STRUKTUR HASIL
    // ===============================
    $checks = [];

    foreach ($masters as $m) {

      // init Jan–Des
      $months = array_fill(1, 12, '–');

      // ===========================
      // LOOP INVENTORY → LOG
      // ===========================
      foreach ($inventories as $inv) {

        $logs = $this->checklistLogModel
          ->where('inventory_id', $inv['id'])
          ->where('checklist_template_id', $m['id'])
          ->where('YEAR(check_date)', $year)
          ->findAll();

        foreach ($logs as $log) {

          $monthIndex = (int) date('n', strtotime($log['check_date']));

          $symbol = match ($log['status']) {
            'not_ok'  => '✗',
            'ok' => '✓',
            'na'  => '–',
            default => '–',
          };

          // PRIORITAS STATUS
          if ($symbol === '✗') {
            $months[$monthIndex] = '✗';
          } elseif ($symbol === '✓' && $months[$monthIndex] !== '✗') {
            $months[$monthIndex] = '✓';
          }
        }
      }

      $checks[] = [
        'label'  => $m['question'],
        'months' => $months,
      ];
    }

    // ===============================
    // DATA KE VIEW
    // ===============================
    $data = [
      'title'       => 'Checklist Pemeriksaan ' . $itemType['name'],
      'periodLabel' => $year,
      'checks'      => $checks,
    ];

    return $this->renderPdf(
      'pdf/recap_item_yearly',
      $data,
      'rekap-' . $itemType['name'] . '-' . $year . '.pdf'
    );
  }

  protected function renderMergedPdf(array $views, array $datas, string $filename)
  {
    $mpdf = new Mpdf([
      'mode' => 'utf-8',
      'format' => 'A4',
      'default_font' => 'dejavusans'
    ]);

    foreach ($views as $i => $view) {
      if ($i > 0) {
        $mpdf->AddPage();
      }

      $html = view($view, $datas[$i]);
      $mpdf->WriteHTML($html);
    }

    return $this->response
      ->setHeader('Content-Type', 'application/pdf')
      ->setHeader(
        'Content-Disposition',
        'inline; filename="' . $filename . '"'
      )
      ->setBody($mpdf->Output('', 'S')); // 🔥 STRING, BUKAN FILE
  }



  public function singleItemWithAttachment(int $inventoryId, string $periodKey)
  {
    // ===============================
    // DATA UTAMA
    // ===============================
    $mainData = $this->getSingleItemData($inventoryId, $periodKey);

    // ===============================
    // DATA NG
    // ===============================
    $ngItems = $this->checklistLogModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('status', 'no')
      ->findAll();

    $views = ['pdf/single_item'];
    $datas = [$mainData];

    if ($ngItems) {
      $views[] = 'pdf/attachment_ng';
      $datas[] = [
        'title'       => 'Lampiran Ketidaksesuaian',
        'periodLabel' => $mainData['periodLabel'],
        'ngItems'     => $ngItems,
      ];
    }

    return $this->renderMergedPdf(
      $views,
      $datas,
      'checklist-' . $mainData['inventoryNo'] . '.pdf'
    );
  }



  protected function renderPdfToFile(string $view, array $data, string $filePath)
  {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);

    $html = view($view, $data);
    $dompdf->loadHtml($html);

    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    file_put_contents($filePath, $dompdf->output());
  }

  protected function getSingleItemData(int $inventoryId, string $periodKey): array
  {
    // 1️⃣ Inventory
    $inventory = $this->inventoryModel->find($inventoryId);
    if (! $inventory) {
      throw new \RuntimeException('Inventory tidak ditemukan');
    }

    // 2️⃣ Item Type
    $itemType = $this->itemTypeModel->find($inventory['item_type_id']);
    if (! $itemType) {
      throw new \RuntimeException('Item type tidak ditemukan');
    }

    foreach ($masters as $m) {

      // 1️⃣ Coba cari log SESUAI period_key
      $log = $this->checklistLogModel
        ->where([
          'inventory_id'           => $inventory['id'],
          'checklist_template_id'  => $m['id'],
          'period_key'             => $periodKey,
        ])
        ->first();

      // 2️⃣ FALLBACK: kalau tidak ketemu, ambil log TERAKHIR item tsb
      if (! $log) {
        $log = $this->checklistLogModel
          ->where([
            'inventory_id'          => $inventory['id'],
            'checklist_template_id' => $m['id'],
          ])
          ->orderBy('check_date', 'DESC')
          ->first();
      }

      // 3️⃣ Mapping status
      $questions[] = [
        'question' => $m['question'],
        'status_symbol' => match ($log['status'] ?? null) {
          'ok'      => '✓',
          'not_ok'  => '✗',
          'na'      => '–',
          default   => '–',
        },
      ];
    }


    // ===============================
    // RETURN NORMAL DATA
    // ===============================
    return [
      'title'       => 'Checklist Pemeriksaan ' . $itemType['name'],
      'itemName'    => $itemType['name'],
      'inventoryNo' => $inventory['asset_code'],
      'location'    => $inventory['specific_area'],
      'periodLabel' => $this->formatPeriodLabel($periodKey),
      'questions'   => $questions,
    ];
  }


  public function exportPeriode(string $periodKey)
  {
    // ===============================
    // AMBIL INVENTORY AKTIF
    // ===============================
    $inventories = $this->inventoryModel
      ->where('active', 1)
      ->orderBy('item_type_id', 'ASC')
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($inventories)) {
      return redirect()->back()
        ->with('error', 'Tidak ada inventory aktif untuk diexport.');
    }

    // ===============================
    // INIT mPDF
    // ===============================
    $mpdf = new \Mpdf\Mpdf([
      'mode'         => 'utf-8',
      'format'       => 'A4',
      'default_font' => 'dejavusans',
      'margin_top'    => 10,
      'margin_bottom' => 15,
    ]);

    // ===============================
    // LOAD CSS PDF (SEKALI SAJA)
    // ===============================
    $cssPath = FCPATH . 'assets/css/pdf.css';
    if (file_exists($cssPath)) {
      $css = file_get_contents($cssPath);
      $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    }

    // ===============================
    // LOOP INVENTORY → SATU PDF BESAR
    // ===============================
    foreach ($inventories as $i => $inv) {

      if ($i > 0) {
        $mpdf->AddPage();
      }

      // ===== DATA UTAMA INVENTORY =====
      $mainData = $this->getSingleItemData($inv['id'], $periodKey);

      // ===== PEMISAH INVENTORY =====
      $mpdf->WriteHTML(
        view('pdf/_inventory_separator', $mainData),
        \Mpdf\HTMLParserMode::HTML_BODY
      );

      // ===== CHECKLIST UTAMA =====
      $mpdf->WriteHTML(
        view('pdf/single_item', $mainData),
        \Mpdf\HTMLParserMode::HTML_BODY
      );

      // ===== LAMPIRAN NG (JIKA ADA) =====
      $ngItems = $this->checklistLogModel
        ->where('inventory_id', $inv['id'])
        ->where('period_key', $periodKey)
        ->where('status', 'no')
        ->findAll();

      if (! empty($ngItems)) {
        $mpdf->AddPage();
        $mpdf->WriteHTML(
          view('pdf/attachment_ng', [
            'title'       => 'Lampiran Ketidaksesuaian',
            'periodLabel' => $mainData['periodLabel'],
            'ngItems'     => $ngItems,
          ]),
          \Mpdf\HTMLParserMode::HTML_BODY
        );
      }
    }

    // ===============================
    // NAMA FILE (AUDIT FRIENDLY)
    // ===============================
    $filename = 'Checklist-Periode-' .
      str_replace(' ', '_', $this->formatPeriodLabel($periodKey)) .
      '.pdf';

    // ===============================
    // OUTPUT PDF
    // ===============================
    return $this->response
      ->setHeader('Content-Type', 'application/pdf')
      ->setHeader(
        'Content-Disposition',
        'inline; filename="' . $filename . '"'
      )
      ->setBody($mpdf->Output('', 'S'));
  }


  protected function pdfFileName(array $parts): string
  {
    return implode('-', array_map(
      fn($p) => str_replace(' ', '_', $p),
      $parts
    )) . '.pdf';
  }
}
