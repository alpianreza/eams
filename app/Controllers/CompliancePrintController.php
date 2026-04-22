<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\EamsPdf;
use App\Models\AssetItemTypeModel;
use App\Models\ChecklistLogModel;
use App\Models\ChecklistMasterModel;
use App\Models\ComplianceInventoryModel;

class CompliancePrintController extends BaseController
{
  protected function getPrintableItemTypes(): array
  {
    $db = \Config\Database::connect();

    return $db->table('compliance_inventory')
      ->distinct()
      ->select('asset_item_types.id, asset_item_types.name, asset_item_types.checklist_frequency')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->orderBy('asset_item_types.name', 'ASC')
      ->get()
      ->getResultArray();
  }

  public function index()
  {
    if (!hasRole(['admin', 'compliance', 'auditor'])) {
      return redirect()->back();
    }

    $data = [
      'title' => 'Print Center'
    ];

    return view('compliance/print/index', $data);
  }

  public function item()
  {
    return view('compliance/print/item', [
      'itemTypes' => $this->getPrintableItemTypes()
    ]);
  }

  public function itemPreview()
  {
    $inventoryIds = explode(',', $this->request->getGet('inventory'));
    $years  = explode(',', $this->request->getGet('year'));
    $months = explode(',', $this->request->getGet('month'));

    $inventoryModel = new \App\Models\ComplianceInventoryModel();
    $itemTypeModel  = new \App\Models\AssetItemTypeModel();

    $inventories = $inventoryModel
      ->whereIn('id', $inventoryIds)
      ->findAll();

    $data = [
      'inventories' => $inventories,
      'years' => $years,
      'months' => $months
    ];

    return view('compliance/print/preview', $data);
  }

  public function inventoryByType($itemTypeId)
  {
    $inventoryModel = new \App\Models\ComplianceInventoryModel();

    $inventories = $inventoryModel
      ->select('id, asset_code, specific_area')
      ->where('item_type_id', $itemTypeId)
      ->orderBy('asset_code', 'ASC')
      ->findAll();

    return view('compliance/print/_inventory_list', [
      'inventories' => $inventories
    ]);
  }

  public function batch()
  {
    return view('compliance/print/batch', [
      'itemTypes' => $this->getPrintableItemTypes()
    ]);
  }

  public function batchPreview()
  {
    helper('checklist');

    $itemTypeId = (int) $this->request->getGet('item_type_id');
    $selectedMonth = (int) ($this->request->getGet('month') ?: date('n'));
    $selectedYear = (int) ($this->request->getGet('year') ?: date('Y'));

    if ($itemTypeId < 1) {
      return $this->response
        ->setStatusCode(400)
        ->setBody('Item type tidak valid.');
    }

    $itemTypeModel = new AssetItemTypeModel();
    $inventoryModel = new ComplianceInventoryModel();
    $masterModel = new ChecklistMasterModel();

    $itemType = $itemTypeModel->find($itemTypeId);

    if (!$itemType) {
      return $this->response
        ->setStatusCode(404)
        ->setBody('Item type tidak ditemukan.');
    }

    $inventoryQuery = $inventoryModel
      ->select('compliance_inventory.*, asset_item_types.name AS item_name, asset_item_types.code AS item_code')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', $itemTypeId);

    if (in_array($this->itemTypeSlug((string) ($itemType['name'] ?? '')), ['smoke_detector', 'heat_detector'], true)) {
      $inventoryQuery
        ->orderBy('TRIM(compliance_inventory.specific_area)', 'ASC', false)
        ->orderBy('compliance_inventory.asset_code', 'ASC');
    } else {
      $inventoryQuery->orderBy('compliance_inventory.asset_code', 'ASC');
    }

    $inventories = $inventoryQuery->findAll();

    $masters = $masterModel
      ->where('item_type_id', $itemTypeId)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $layout = $this->resolveBatchLayout($itemType, $masters);
    $layout['period'] = $this->resolveBatchPeriod(
      (string) ($itemType['checklist_frequency'] ?? ''),
      $selectedMonth,
      $selectedYear
    );
    $checklistMatrix = $this->buildBatchChecklistMatrix(
      $inventories,
      (string) ($itemType['checklist_frequency'] ?? ''),
      $selectedMonth,
      $selectedYear
    );

    $findings = $this->buildBatchFindings(
      $inventories,
      $masters,
      (string) ($itemType['checklist_frequency'] ?? ''),
      $selectedMonth,
      $selectedYear
    );
    $weeklyChecklistMatrix = $this->buildBatchWeeklyChecklistMatrix(
      $inventories,
      (string) ($itemType['checklist_frequency'] ?? ''),
      $selectedMonth,
      $selectedYear
    );
    $dailyChecklistMatrix = $this->buildBatchDailyChecklistMatrix(
      $inventories,
      (string) ($itemType['checklist_frequency'] ?? ''),
      $selectedMonth,
      $selectedYear
    );
    $dailyPeriods = $this->buildBatchDailyPeriods(
      (string) ($itemType['checklist_frequency'] ?? ''),
      $selectedMonth,
      $selectedYear
    );

    $pdf = new EamsPdf();

    return $pdf->export('batch_form', [
      'itemType' => $itemType,
      'inventories' => $inventories,
      'masters' => $masters,
      'layout' => $layout,
      'checklistMatrix' => $checklistMatrix,
      'weeklyChecklistMatrix' => $weeklyChecklistMatrix,
      'dailyChecklistMatrix' => $dailyChecklistMatrix,
      'dailyPeriods' => $dailyPeriods,
      'findings' => $findings,
      'selectedMonth' => $selectedMonth,
      'selectedYear' => $selectedYear,
      'filename' => $this->buildBatchPdfFilename($itemType, $selectedMonth, $selectedYear),
    ]);
  }

  protected function resolveBatchLayout(array $itemType, array $masters): array
  {
    $itemName = trim((string) ($itemType['name'] ?? ''));
    $template = $this->resolveBatchTemplate($itemName);

    $layout = [
      'itemSlug' => $this->itemTypeSlug($itemName),
      'template' => $template,
      'headerTitle' => $this->resolveBatchHeaderTitle($itemName),
      'headerSubtitle' => $this->resolveBatchHeaderSubtitle($itemName),
      'company' => [
        'name' => 'PT. YOUNGHYUN STAR',
        'address' => [
          'Kmp. Kebon Randu RT.01/04',
          'Ds. Sekarwangi Kec. Cibadak Kab. Sukabumi',
          'Telp. (0266) 534620  Fax. (0266) 534623',
        ],
      ],
      'baseColumns' => [
        ['key' => 'row_number', 'label' => 'No', 'class' => 'col-no'],
        ['key' => 'specific_area', 'label' => 'Lokasi', 'class' => 'col-location'],
        ['key' => 'pic', 'label' => 'PIC', 'class' => 'col-pic'],
      ],
      'groupedColumns' => [
        [
          'label' => 'Informasi Item',
          'columns' => [
            [
              'type' => 'field',
              'key' => 'asset_code',
              'label' => 'Kode Inventaris',
              'class' => 'col-static',
            ],
            [
              'type' => 'field',
              'key' => 'type_description',
              'label' => 'Deskripsi',
              'class' => 'col-static',
            ],
            [
              'type' => 'field',
              'key' => 'expired_date',
              'label' => 'Kadaluarsa',
              'class' => 'col-static',
            ],
          ],
        ],
        [
          'label' => 'Checklist',
          'columns' => array_map(function (array $master): array {
            return [
              'type' => 'question',
              'id' => (int) $master['id'],
              'label' => $this->resolveQuestionLabel((string) ($master['question'] ?? '')),
              'class' => 'col-question',
            ];
          }, $masters),
        ],
      ],
    ];

    if ($this->itemTypeSlug($itemName) === 'fire_extinguisher') {
      $layout['groupedColumns'] = $this->resolveFireExtinguisherColumns($masters);
    }

    if (in_array($this->itemTypeSlug($itemName), ['fire_alarm', 'intrusion_alarm'], true)) {
      $layout['baseColumns'] = [
        ['key' => 'row_number', 'label' => 'No', 'class' => 'col-no'],
        ['key' => 'specific_area', 'label' => 'Keterangan', 'class' => 'col-location'],
      ];
      $layout['groupedColumns'] = $this->resolveFireAlarmColumns($masters);
      $layout['trailingColumns'] = [
        ['key' => 'notes', 'label' => 'KET', 'class' => 'col-note'],
      ];
    }

    if ($this->itemTypeSlug($itemName) === 'hydrant') {
      $layout['baseColumns'] = [
        ['key' => 'row_number', 'label' => 'No', 'class' => 'col-no'],
        ['key' => 'specific_area', 'label' => 'Keterangan', 'class' => 'col-location'],
      ];
      $layout['trailingColumns'] = [
        ['key' => 'notes', 'label' => 'KET', 'class' => 'col-note'],
      ];
    }

    if ($this->itemTypeSlug($itemName) === 'emergency_light') {
      $layout['baseColumns'] = [
        ['key' => 'row_number', 'label' => 'No', 'class' => 'col-no'],
        ['key' => 'specific_area', 'label' => 'Lokasi', 'class' => 'col-location'],
      ];
      $layout['groupedColumns'] = $this->resolveEmergencyLightColumns($masters);
    }

    if ($this->itemTypeSlug($itemName) === 'cctv') {
      $layout['headerTitle'] = 'PEMERIKSAAN & PERAWATAN CCTV';
      $layout['headerSubtitle'] = '(CCTV Inspection & Maintenance Checklist)';
      $layout['signatures'] = ['IT Officer', 'Diperiksa', 'Mengetahui'];
    }

    if (in_array($this->itemTypeSlug($itemName), ['smoke_detector', 'heat_detector'], true)) {
      $layout['baseColumns'] = [
        ['key' => 'row_number', 'label' => 'No.', 'class' => 'col-no'],
        ['key' => 'specific_area', 'label' => 'Lokasi', 'class' => 'col-location'],
      ];
      $layout['groupedColumns'] = [[
        'label' => '',
        'columns' => array_map(function (array $master): array {
          return [
            'type' => 'question',
            'id' => (int) ($master['id'] ?? 0),
            'label' => $this->resolveQuestionLabel((string) ($master['question'] ?? '')),
            'class' => 'col-question',
          ];
        }, $masters),
      ]];
      $layout['trailingColumns'] = [
        ['key' => 'notes', 'label' => 'Keterangan', 'class' => 'col-note'],
      ];
      $layout['signatures'] = ['Diperiksa oleh', 'Mengetahui'];
    }

    return $layout;
  }

  protected function resolveFireAlarmColumns(array $masters): array
  {
    $groupMap = [];
    foreach ($masters as $master) {
      $question = trim((string) ($master['question'] ?? ''));
      if ($question === '') {
        continue;
      }

      $groupMap[$question] = [
        'label' => $this->resolveQuestionLabel($question),
        'template_id' => (int) ($master['id'] ?? 0),
        'columns' => array_map(static function (int $week): array {
          return [
            'type' => 'week',
            'week' => $week,
            'label' => (string) $week,
            'class' => 'col-week',
          ];
        }, [1, 2, 3, 4]),
      ];
    }

    $orderedQuestions = [
      'Kerapihan Kabel',
      'Lampu Indikator',
      'Arus Listrik',
      'Fungsi Alarm',
      'Suara',
      'Kebersihan',
      'Manual Push Button',
    ];

    $ordered = $this->takeOrderedQuestions($groupMap, $orderedQuestions);

    foreach ($groupMap as $group) {
      $ordered[] = $group;
    }

    return $ordered;
  }

  protected function resolveEmergencyLightColumns(array $masters): array
  {
    $groups = [
      'lampu_darurat' => [
        'group_key' => 'lampu_darurat',
        'label' => 'Lampu Darurat',
        'question_ids' => [],
        'columns' => [
          [
            'type' => 'field',
            'key' => 'type_description',
            'label' => 'Jenis Lampu',
            'class' => 'col-type',
          ],
          [
            'type' => 'question',
            'slot' => 'berfungsi',
            'id' => 0,
            'label' => 'Berfungsi Baik',
            'class' => 'col-question',
          ],
          [
            'type' => 'question',
            'slot' => 'pecah',
            'id' => 0,
            'label' => 'Tidak Pecah',
            'class' => 'col-question',
          ],
          [
            'type' => 'question',
            'slot' => 'kabel',
            'id' => 0,
            'label' => 'Kabel',
            'class' => 'col-question',
          ],
        ],
      ],
      'lampu_exit' => [
        'group_key' => 'lampu_exit',
        'label' => 'Lampu EXIT',
        'question_ids' => [],
        'columns' => [
          [
            'type' => 'field',
            'key' => 'type_description',
            'label' => 'Jenis Lampu',
            'class' => 'col-type',
          ],
          [
            'type' => 'question',
            'slot' => 'berfungsi',
            'id' => 0,
            'label' => 'Berfungsi Baik',
            'class' => 'col-question',
          ],
          [
            'type' => 'question',
            'slot' => 'pecah',
            'id' => 0,
            'label' => 'Tidak Pecah',
            'class' => 'col-question',
          ],
          [
            'type' => 'question',
            'slot' => 'kabel',
            'id' => 0,
            'label' => 'Kabel',
            'class' => 'col-question',
          ],
        ],
      ],
    ];

    foreach ($masters as $master) {
      $templateId = (int) ($master['id'] ?? 0);
      $question = $this->normalizeQuestion((string) ($master['question'] ?? ''));
      $questionLower = strtolower($question);

      if ($templateId < 1 || $questionLower === '') {
        continue;
      }

      $groupKey = null;

      if (strpos($questionLower, 'exit') !== false) {
        $groupKey = 'lampu_exit';
      } elseif (strpos($questionLower, 'darurat') !== false || strpos($questionLower, 'emergency') !== false) {
        $groupKey = 'lampu_darurat';
      }

      if ($groupKey === null || !isset($groups[$groupKey])) {
        continue;
      }

      $groups[$groupKey]['question_ids'][] = $templateId;

      foreach ($groups[$groupKey]['columns'] as &$column) {
        if (($column['type'] ?? '') !== 'question') {
          continue;
        }

        $slot = (string) ($column['slot'] ?? '');
        if ($slot === 'berfungsi' && strpos($questionLower, 'berfun') !== false) {
          $column['id'] = $templateId;
        } elseif ($slot === 'pecah' && strpos($questionLower, 'pecah') !== false) {
          $column['id'] = $templateId;
        } elseif ($slot === 'kabel' && strpos($questionLower, 'kabel') !== false) {
          $column['id'] = $templateId;
        }
      }
      unset($column);
    }

    return array_values($groups);
  }

  protected function resolveFireExtinguisherColumns(array $masters): array
  {
    $columnMap = [];
    foreach ($masters as $master) {
      $question = trim((string) ($master['question'] ?? ''));
      $columnMap[$question] = [
        'type' => 'question',
        'id' => (int) $master['id'],
        'label' => $this->resolveQuestionLabel($question),
        'class' => 'col-question',
      ];
    }

    $primaryOrder = [
      'Pressure Gauge',
      'Pin/Segel',
      'Selang',
      'Klem Selang',
      'Handle',
      'Kondisi Fisik',
      'Petunjuk Pemakaian',
    ];

    $secondaryOrder = [
      'Tidak Terhalang',
      'Mudah Dijangkau',
      'Bersih',
      'Siap Pakai',
    ];

    $primaryQuestions = $this->takeOrderedQuestions($columnMap, $primaryOrder);
    $secondaryQuestions = $this->takeOrderedQuestions($columnMap, $secondaryOrder);

    foreach ($columnMap as $column) {
      if ($this->isFireExtinguisherConditionQuestion((string) $column['label'])) {
        $secondaryQuestions[] = $column;
      } else {
        $primaryQuestions[] = $column;
      }
    }

    return [
      [
        'label' => 'Tabung APAR',
        'columns' => array_merge([
          [
            'type' => 'field',
            'key' => 'type_description',
            'label' => 'Kapasitas',
            'class' => 'col-static',
          ],
          [
            'type' => 'field',
            'key' => 'expired_date',
            'label' => 'Tanggal Kadaluarsa',
            'class' => 'col-static',
          ],
        ], $primaryQuestions),
      ],
      [
        'label' => 'Kondisi APAR',
        'columns' => $secondaryQuestions,
      ],
    ];
  }

  protected function takeOrderedQuestions(array &$columnMap, array $orderedQuestions): array
  {
    $ordered = [];

    foreach ($orderedQuestions as $question) {
      if (!isset($columnMap[$question])) {
        continue;
      }

      $ordered[] = $columnMap[$question];
      unset($columnMap[$question]);
    }

    return $ordered;
  }

  protected function isFireExtinguisherConditionQuestion(string $label): bool
  {
    $normalized = strtolower(trim($label));
    $keywords = [
      'terhalang',
      'jangkau',
      'bersih',
      'siap pakai',
      'mudah dijangkau',
    ];

    foreach ($keywords as $keyword) {
      if (strpos($normalized, $keyword) !== false) {
        return true;
      }
    }

    return false;
  }

  protected function resolveQuestionLabel(string $question): string
  {
    $question = $this->normalizeQuestion($question);

    $labelMap = [
      'Pin/Segel' => 'Kondisi Segel',
      'Tidak Terhalang' => 'Terhalang',
      'Lampu Exit Berfunsi Baik' => 'Lampu Exit Berfungsi Baik',
    ];

    return $labelMap[$question] ?? $question;
  }

  protected function resolveBatchHeaderSubtitle(string $itemName): string
  {
      $subtitleMap = [
        'fire_alarm' => '(Fire Alarm Checklist)',
        'emergency_light' => '(Emergency Light and Exit Checklist)',
        'intrusion_alarm' => '(Intrusion Alarm Inspection & Maintenance Checklist)',
        'hydrant' => '(Weekly Hydrant Checklist)',
        'smoke_detector' => '(Smoke Detector Checklist)',
        'heat_detector' => '(Heat Detector Checklist)',
      ];

    $slug = $this->itemTypeSlug($itemName);
    if (isset($subtitleMap[$slug])) {
      return $subtitleMap[$slug];
    }

    return '(' . ($itemName !== '' ? $itemName : 'Checklist') . ' Checklist)';
  }

  protected function resolveBatchHeaderTitle(string $itemName): string
  {
      $titleMap = [
        'fire_extinguisher' => 'CHECKLIST ALAT PEMADAM API RINGAN',
        'fire_alarm' => 'CHECKLIST ALARM KEBAKARAN',
        'emergency_light' => 'CHECKLIST LAMPU DARURAT DAN LAMPU EXIT',
        'intrusion_alarm' => 'PEMERIKSAAN & PERAWATAN ALARM KEAMANAN',
        'hydrant' => 'PENGECEKAN HIDRAN PER MINGGU',
        'smoke_detector' => 'CHECKLIST SMOKE DETECTOR',
        'heat_detector' => 'CHECKLIST HEAT DETECTOR',
      ];

    $slug = $this->itemTypeSlug($itemName);
    if (isset($titleMap[$slug])) {
      return $titleMap[$slug];
    }

    return 'CHECKLIST ' . strtoupper($itemName !== '' ? $itemName : 'ITEM');
  }

  protected function resolveBatchTemplate(string $itemName): string
  {
    $slug = $this->itemTypeSlug($itemName);
    $templatePath = APPPATH . 'Views/compliance/print/templates/batch/' . $slug . '.php';

    return is_file($templatePath) ? $slug : 'generic';
  }

  protected function itemTypeSlug(string $itemName): string
  {
    $slug = strtolower(trim($itemName));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? '';

    return trim($slug, '_');
  }

  protected function normalizeQuestion(string $question): string
  {
    return trim((string) (preg_replace('/\s+/', ' ', trim($question)) ?? ''));
  }

  protected function resolveBatchPeriod(string $frequency, int $month, int $year): array
  {
    $months = [
      1 => 'Januari',
      2 => 'Februari',
      3 => 'Maret',
      4 => 'April',
      5 => 'Mei',
      6 => 'Juni',
      7 => 'Juli',
      8 => 'Agustus',
      9 => 'September',
      10 => 'Oktober',
      11 => 'November',
      12 => 'Desember',
    ];

    $month = max(1, min(12, $month));
    $year = $year > 2000 ? $year : (int) date('Y');

    if ($frequency === 'daily' || $frequency === 'weekly' || $frequency === 'monthly') {
      return [
        'label' => 'BULAN',
        'value' => ($months[$month] ?? 'Bulan') . ' ' . $year,
      ];
    }

    return [
      'label' => 'HARI / TANGGAL',
      'value' => '',
    ];
  }

  protected function buildBatchChecklistMatrix(array $inventories, string $frequency, int $month, int $year): array
  {
    if (empty($inventories)) {
      return [];
    }

    $inventoryIds = array_values(array_unique(array_map(static function (array $inventory): int {
      return (int) ($inventory['id'] ?? 0);
    }, $inventories)));

    $inventoryIds = array_values(array_filter($inventoryIds));

    if (empty($inventoryIds)) {
      return [];
    }

    $logModel = new ChecklistLogModel();
    $builder = $logModel
      ->whereIn('inventory_id', $inventoryIds)
      ->orderBy('check_date', 'DESC')
      ->orderBy('id', 'DESC');

    $monthKey = sprintf('%04d-%02d', $year, $month);

    if ($frequency === 'monthly') {
      $builder->where('period_key', $monthKey);
    } else {
      $builder->like('period_key', $monthKey, 'after');
    }

    $logs = $builder->findAll();
    $matrix = [];

    foreach ($logs as $log) {
      $inventoryId = (int) ($log['inventory_id'] ?? 0);
      $templateId = (int) ($log['checklist_template_id'] ?? 0);
      $status = trim((string) ($log['status'] ?? ''));

      if ($inventoryId < 1 || $templateId < 1 || $status === '') {
        continue;
      }

      if ($frequency === 'monthly') {
        if (!isset($matrix[$inventoryId][$templateId])) {
          $matrix[$inventoryId][$templateId] = $status;
        }
        continue;
      }

      $current = $matrix[$inventoryId][$templateId] ?? null;
      $matrix[$inventoryId][$templateId] = $this->aggregateBatchStatus($current, $status);
    }

    return $matrix;
  }

  protected function buildBatchDailyChecklistMatrix(array $inventories, string $frequency, int $month, int $year): array
  {
    if ($frequency !== 'daily' || empty($inventories)) {
      return [];
    }

    $inventoryIds = array_values(array_unique(array_map(static function (array $inventory): int {
      return (int) ($inventory['id'] ?? 0);
    }, $inventories)));

    $inventoryIds = array_values(array_filter($inventoryIds));
    if (empty($inventoryIds)) {
      return [];
    }

    $monthKey = sprintf('%04d-%02d', $year, $month);

    $logs = (new ChecklistLogModel())
      ->whereIn('inventory_id', $inventoryIds)
      ->like('period_key', $monthKey . '-', 'after')
      ->orderBy('check_date', 'DESC')
      ->orderBy('id', 'DESC')
      ->findAll();

    $matrix = [];

    foreach ($logs as $log) {
      $inventoryId = (int) ($log['inventory_id'] ?? 0);
      $status = trim((string) ($log['status'] ?? ''));
      $periodKey = trim((string) ($log['period_key'] ?? ''));

      if ($inventoryId < 1 || $status === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodKey)) {
        continue;
      }

      $current = $matrix[$inventoryId][$periodKey] ?? null;
      $matrix[$inventoryId][$periodKey] = $this->aggregateBatchStatus($current, $status);
    }

    return $matrix;
  }

  protected function buildBatchDailyPeriods(string $frequency, int $month, int $year): array
  {
    if ($frequency !== 'daily') {
      return [];
    }

    $month = max(1, min(12, $month));
    $year = $year > 2000 ? $year : (int) date('Y');
    $monthStart = sprintf('%04d-%02d-01', $year, $month);
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $holidayDates = holiday_dates_between($monthStart, $monthEnd);
    $periods = generate_calendar_periods('daily', $year, $month);

    foreach ($periods as &$period) {
      $periodKey = (string) ($period['period_key'] ?? '');
      $period['day'] = (int) date('j', strtotime($periodKey));
      $period['is_offday'] = is_date_offday($periodKey, $holidayDates);
    }
    unset($period);

    return $periods;
  }

  protected function buildBatchWeeklyChecklistMatrix(array $inventories, string $frequency, int $month, int $year): array
  {
    if ($frequency !== 'weekly' || empty($inventories)) {
      return [];
    }

    $inventoryIds = array_values(array_unique(array_map(static function (array $inventory): int {
      return (int) ($inventory['id'] ?? 0);
    }, $inventories)));

    $inventoryIds = array_values(array_filter($inventoryIds));
    if (empty($inventoryIds)) {
      return [];
    }

    $monthKey = sprintf('%04d-%02d', $year, $month);

    $logs = (new ChecklistLogModel())
      ->whereIn('inventory_id', $inventoryIds)
      ->like('period_key', $monthKey . '-W', 'after')
      ->orderBy('check_date', 'DESC')
      ->orderBy('id', 'DESC')
      ->findAll();

    $matrix = [];

    foreach ($logs as $log) {
      $inventoryId = (int) ($log['inventory_id'] ?? 0);
      $templateId = (int) ($log['checklist_template_id'] ?? 0);
      $status = trim((string) ($log['status'] ?? ''));
      $periodKey = trim((string) ($log['period_key'] ?? ''));

      if ($inventoryId < 1 || $templateId < 1 || $status === '') {
        continue;
      }

      if (!preg_match('/W([1-4])$/', $periodKey, $matches)) {
        continue;
      }

      $weekNumber = (int) $matches[1];

      if (!isset($matrix[$inventoryId][$templateId][$weekNumber])) {
        $matrix[$inventoryId][$templateId][$weekNumber] = $status;
      }
    }

    return $matrix;
  }

  protected function buildBatchFindings(array $inventories, array $masters, string $frequency, int $month, int $year): array
  {
    if (empty($inventories) || empty($masters)) {
      return [];
    }

    $inventoryMap = [];
    foreach ($inventories as $inventory) {
      $inventoryId = (int) ($inventory['id'] ?? 0);
      if ($inventoryId > 0) {
        $inventoryMap[$inventoryId] = $inventory;
      }
    }

    if (empty($inventoryMap)) {
      return [];
    }

    $questionMap = [];
    foreach ($masters as $master) {
      $templateId = (int) ($master['id'] ?? 0);
      if ($templateId > 0) {
        $questionMap[$templateId] = $this->resolveQuestionLabel((string) ($master['question'] ?? ''));
      }
    }

    $logModel = new ChecklistLogModel();
    $builder = $logModel
      ->whereIn('inventory_id', array_keys($inventoryMap))
      ->where('status', 'not_ok')
      ->orderBy('check_date', 'DESC')
      ->orderBy('id', 'DESC');

    $monthKey = sprintf('%04d-%02d', $year, $month);

    if ($frequency === 'monthly') {
      $builder->where('period_key', $monthKey);
    } else {
      $builder->like('period_key', $monthKey, 'after');
    }

    $logs = $builder->findAll();
    helper('period');

    $findings = [];

    foreach ($logs as $log) {
      $inventoryId = (int) ($log['inventory_id'] ?? 0);
      $templateId = (int) ($log['checklist_template_id'] ?? 0);

      if (!isset($inventoryMap[$inventoryId])) {
        continue;
      }

      $inventory = $inventoryMap[$inventoryId];
      $photoName = trim((string) ($log['photo'] ?? ''));
      $photoPath = '';

      if ($photoName !== '') {
        $candidate = FCPATH . 'uploads/checklist/' . $photoName;
        if (is_file($candidate)) {
          $photoPath = $candidate;
        }
      }

      $findings[] = [
        'inventory_id' => $inventoryId,
        'asset_code' => (string) ($inventory['asset_code'] ?? '-'),
        'specific_area' => (string) ($inventory['specific_area'] ?? '-'),
        'pic' => (string) ($inventory['pic'] ?? '-'),
        'question' => $questionMap[$templateId] ?? ('Pertanyaan #' . $templateId),
        'remark' => (string) ($log['remark'] ?? ''),
        'checked_by' => (string) ($log['checked_by'] ?? '-'),
        'check_date' => (string) ($log['check_date'] ?? ''),
        'period_key' => (string) ($log['period_key'] ?? ''),
        'display_period' => period_label($frequency, (string) ($log['period_key'] ?? '')),
        'photo_path' => $photoPath,
      ];
    }

    return $findings;
  }

  protected function buildBatchPdfFilename(array $itemType, int $month, int $year): string
  {
    $slug = $this->itemTypeSlug((string) ($itemType['name'] ?? 'batch'));
    $month = max(1, min(12, $month));
    $year = $year > 2000 ? $year : (int) date('Y');

    return sprintf('Print-%s-%04d-%02d.pdf', $slug !== '' ? $slug : 'batch', $year, $month);
  }

  protected function aggregateBatchStatus(?string $current, string $next): string
  {
    $current = trim((string) $current);
    $next = trim((string) $next);

    if ($current === 'not_ok' || $next === 'not_ok') {
      return 'not_ok';
    }

    if ($current === 'ok' || $next === 'ok') {
      return 'ok';
    }

    if ($current === 'na' || $next === 'na') {
      return 'na';
    }

    return $next !== '' ? $next : $current;
  }
}
