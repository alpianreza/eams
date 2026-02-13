<?php

namespace App\Controllers;

use App\Models\ComplianceInventoryModel;
use App\Models\ChecklistLogModel;
use App\Models\ChecklistMasterModel;
use App\Models\InventoryCategoryModel;
use App\Models\AssetItemTypeModel;

class ComplianceReportController extends BaseController
{
  protected $inventoryModel;
  protected $logModel;
  protected $masterModel;

  public function __construct()
  {
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->logModel       = new ChecklistLogModel();
    $this->masterModel    = new ChecklistMasterModel();
  }

  public function index()
  {
    $categories = model(InventoryCategoryModel::class)
      ->orderBy('name', 'ASC')
      ->findAll();

    return view('compliance/report/index', [
      'categories' => $categories
    ]);
  }

  public function getItemTypeByCategory()
  {
    $categoryId = $this->request->getGet('category_id');

    if (!$categoryId) {
      return $this->response->setJSON([]);
    }

    $types = $this->inventoryModel
      ->select('asset_item_types.id, asset_item_types.name')
      ->join(
        'asset_item_types',
        'asset_item_types.id = compliance_inventory.item_type_id'
      )
      ->where('compliance_inventory.category_id', $categoryId)
      ->groupBy('asset_item_types.id')
      ->orderBy('asset_item_types.name', 'ASC')
      ->findAll();

    return $this->response->setJSON($types);
  }

  public function getInventoryByType()
  {
    $itemTypeId = $this->request->getGet('item_type_id');

    $inventories = $this->inventoryModel
      ->where('item_type_id', $itemTypeId)
      ->orderBy('asset_code', 'ASC')
      ->findAll();

    return $this->response->setJSON($inventories);
  }

  public function loadAjax()
  {
    $inventoryId = $this->request->getGet('inventory_id');
    $year        = $this->request->getGet('year');
    $month       = $this->request->getGet('month');

    if (!$inventoryId || !$year) return '';

    $inventory = $this->inventoryModel->find($inventoryId);
    if (!$inventory) return '';

    // ==============================
    // ITEM TYPE
    // ==============================
    $itemTypeModel = new \App\Models\AssetItemTypeModel();
    $itemType = $itemTypeModel->find($inventory['item_type_id']);

    $frequency = $itemType['checklist_frequency'] ?? 'monthly';
    $itemName  = $itemType['name'] ?? '';
    $isFireExtinguisher = strtolower($itemName) === 'fire extinguisher';

    // ==============================
    // MASTER PERTANYAAN
    // ==============================
    $masters = $this->masterModel
      ->where('item_type_id', $inventory['item_type_id'])
      ->orderBy('id', 'ASC')
      ->findAll();

    // ==============================
    // DEFAULT VARIABLE
    // ==============================
    $monthlyGrid = [];
    $dailyGrid = [];
    $weeklyGrid = [];
    $checkerByMonth = [];
    $checkerByDate = [];
    $checkerByWeek = [];
    $findings = [];
    $findingsByMonth = [];
    $dailyDays = [];

    // =====================================================
    // ===================== MONTHLY ========================
    // =====================================================
    if ($frequency === 'monthly') {

      $logs = $this->logModel
        ->where('inventory_id', $inventoryId)
        ->like('period_key', $year, 'after')
        ->findAll();

      foreach ($logs as $log) {

        $periodMonth = (int) substr($log['period_key'], 5, 2);

        $monthlyGrid[$log['checklist_template_id']][$periodMonth] = [
          'status' => $log['status'],
          'checked_by' => $log['checked_by']
        ];

        if (!empty($log['checked_by'])) {
          $checkerByMonth[$periodMonth] = [
            'name' => $log['checked_by'],
            'date' => $log['check_date']
          ];
        }

        if ($log['status'] === 'not_ok') {
          $findingsByMonth[$periodMonth][] = $log;
        }
      }

      // Format display period untuk monthly findings
      foreach ($findingsByMonth as $monthKey => &$logsInMonth) {
        foreach ($logsInMonth as &$log) {
          $log['display_period'] = period_label($frequency, $log['period_key']);
        }
      }
      unset($logsInMonth, $log);
    }

    // =====================================================
    // ======================= DAILY ========================
    // =====================================================
    // =====================================================
    // ======================= DAILY ========================
    // =====================================================
    if ($frequency === 'daily' && $month) {

      $selectedPeriod = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

      $logsMonth = $this->logModel
        ->where('inventory_id', $inventoryId)
        ->where('period_key LIKE', $selectedPeriod . '%')
        ->findAll();

      foreach ($logsMonth as $log) {

        $dailyGrid[$log['checklist_template_id']][$log['period_key']] = $log['status'];

        if (!empty($log['checked_by'])) {
          $checkerByDate[$log['period_key']] = [
            'name' => $log['checked_by'],
            'date' => $log['check_date']
          ];
        }

        if ($log['status'] === 'not_ok') {
          $findings[] = $log;
        }
      }

      // =========================
      // GENERATE TANGGAL 1 BULAN
      // =========================

      $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);

      for ($d = 1; $d <= $daysInMonth; $d++) {
        $dailyDays[] = sprintf('%04d-%02d-%02d', $year, $month, $d);
      }

      // =========================
      // LIBUR NASIONAL
      // =========================

      $holidayDates = [];

      $ym = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

      $holidayModel = new \App\Models\HolidayModel();

      $holidays = $holidayModel
        ->where('holiday_date >=', $ym . '-01')
        ->where('holiday_date <=', $ym . '-31')
        ->findAll();

      $holidayDates = array_column($holidays, 'holiday_date');
    }

    // =====================================================
    // ======================= WEEKLY =======================
    // =====================================================
    if ($frequency === 'weekly' && $month) {

      $selectedPeriodPrefix = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

      $logsMonth = $this->logModel
        ->where('inventory_id', $inventoryId)
        ->like('period_key', $selectedPeriodPrefix, 'after') // contoh: 2026-01-W1
        ->findAll();

      foreach ($logsMonth as $log) {

        // Ambil W1-W4 dari period_key
        if (preg_match('/W([1-4])$/', $log['period_key'], $m)) {

          $weekNumber = (int)$m[1];

          $weeklyGrid[$log['checklist_template_id']][$weekNumber] = $log['status'];

          if (!empty($log['checked_by'])) {
            $checkerByWeek[$weekNumber] = [
              'name' => $log['checked_by'],
              'date' => $log['check_date']
            ];
          }

          if ($log['status'] === 'not_ok') {
            $findings[] = $log;
          }
        }
      }
    }

    // ==============================
    // FORMAT PERIOD UNTUK TEMUAN
    // ==============================

    helper('period');

    foreach ($findings as &$log) {
      $log['display_period'] = period_label($frequency, $log['period_key']);
    }
    unset($log);

    // PREV
    $prevData = $this->inventoryModel
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('asset_code <', $inventory['asset_code'])
      ->orderBy('asset_code', 'DESC')
      ->first();

    $prev = $prevData['id'] ?? null;

    // NEXT
    $nextData = $this->inventoryModel
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('asset_code >', $inventory['asset_code'])
      ->orderBy('asset_code', 'ASC')
      ->first();

    $next = $nextData['id'] ?? null;


    return view('compliance/report/_table', [
      'inventory'   => $inventory,
      'masters'     => $masters,
      'monthlyGrid' => $monthlyGrid,
      'dailyGrid'   => $dailyGrid,
      'weeklyGrid'  => $weeklyGrid,
      'checkerByMonth' => $checkerByMonth,
      'checkerByDate'  => $checkerByDate,
      'checkerByWeek'  => $checkerByWeek,
      'findings'    => $findings,
      'findingsByMonth' => $findingsByMonth,
      'dailyDays'   => $dailyDays,
      'year'        => $year,
      'month'       => $month,
      'prev'        => $prev,
      'next'        => $next,
      'specificArea' => $inventory['specific_area'] ?? '-',
      'pic'         => $inventory['pic'] ?? '-',
      'expired'     => $inventory['expired_date'] ?? null,
      'itemName'    => $itemName,
      'isFireExtinguisher' => $isFireExtinguisher,
      'frequency'   => $frequency,
      'role'        => session('role'),
      'holidayDates' => $holidayDates ?? [],
    ]);
  }
}
