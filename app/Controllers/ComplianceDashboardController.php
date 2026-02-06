<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ComplianceInventoryModel;
use App\Models\ChecklistLogModel;
use App\Models\AssetItemTypeModel;

class ComplianceDashboardController extends BaseController
{
  protected $inventoryModel;
  protected $checklistLogModel;
  protected $itemTypeModel;

  protected $startDate = '2026-01-01 00:00:00';

  public function __construct()
  {
    $this->inventoryModel    = new ComplianceInventoryModel();
    $this->checklistLogModel = new ChecklistLogModel();
    $this->itemTypeModel     = new AssetItemTypeModel();
  }

  public function index()
  {

    $selectedYear = $this->request->getGet('year') ?? date('Y');

    $data = [
      'selectedYear'   => $selectedYear,
      'availableYears' => $this->getAvailableYears(),
      'kpi'            => $this->getKpiSummary($selectedYear),
      'notifications'  => $this->getNotifications(),
      'notOkPhotos'    => $this->getLatestNotOkWithPhoto($selectedYear),
      'monthlyTrend' => $this->getMonthlyTrend($selectedYear),
      'overview' => $this->getChecklistOverview(),


    ];

    return view('compliance/dashboard/index', $data);
  }


  private function getAvailableYears()
  {
    $currentYear = date('Y');
    $years = [];

    for ($y = 2026; $y <= $currentYear; $y++) {
      $years[] = $y;
    }

    return $years;
  }

  private function getCurrentPeriodKey(string $frequency): string
  {
    switch ($frequency) {

      case 'daily':
        return date('Y-m-d');

      case 'weekly':
        $week = ceil(date('d') / 7);
        return date('Y-m') . '-W' . $week;

      case 'monthly':
        return date('Y-m');

      default:
        return date('Y-m');
    }
  }


  private function getKpiSummary($year)
  {
    // Total inventory compliance aktif
    $totalInventory = $this->inventoryModel
      ->where('status', 1)
      ->countAllResults();

    // Ambil semua log tahun terpilih mulai 2026
    $logs = $this->checklistLogModel
      ->where('created_at >=', $this->startDate)
      ->where('YEAR(created_at)', $year)
      ->findAll();

    $summary = [
      'total'          => $totalInventory,
      'sesuai'         => 0,
      'tidak_sesuai'   => 0,
      'tidak_berlaku'  => 0,
      'late'           => 0,
    ];

    foreach ($logs as $log) {

      switch ($log['status']) {
        case 'ok':
          $summary['sesuai']++;
          break;

        case 'not_ok':
          $summary['tidak_sesuai']++;
          break;

        case 'na':
          $summary['tidak_berlaku']++;
          break;
      }
    }

    // Hitung late per inventory
    $inventories = $this->inventoryModel
      ->where('status', 'active')
      ->findAll();

    foreach ($inventories as $inv) {

      $itemType = $this->itemTypeModel
        ->find($inv['item_type_id']);

      if (!$itemType) continue;

      $periodKey = $this->getCurrentPeriodKey(
        $itemType['checklist_frequency']
      );

      $periodStatus = resolve_period_status(
        $inv['id'],
        $itemType['checklist_frequency'],
        $periodKey
      );

      if ($periodStatus === 'late') {
        $summary['late']++;
      }
    }

    return $summary;
  }

  private function getNotifications()
  {
    $notifications = [];

    // ===============================
    // 1️⃣ CEK LATE
    // ===============================
    $inventories = $this->inventoryModel
      ->where('status', 'active')
      ->findAll();

    foreach ($inventories as $inv) {

      $itemType = $this->itemTypeModel
        ->find($inv['item_type_id']);

      if (!$itemType) continue;

      $periodKey = $this->getCurrentPeriodKey(
        $itemType['checklist_frequency']
      );

      $periodStatus = resolve_period_status(
        $inv['id'],
        $itemType['checklist_frequency'],
        $periodKey
      );

      if ($periodStatus === 'late') {

        $notifications[] = [
          'type' => 'late',
          'inventory_id' => $inv['id'],
          'item' => $itemType['name'] ?? '-',
          'area' => $inv['specific_area'] ?? '-',
          'message' => 'Checklist belum dilakukan dan sudah melewati batas periode.'
        ];
      }
    }

    // ===============================
    // 2️⃣ NOT_OK TERBARU (5 TERAKHIR)
    // ===============================
    $notOkLogs = $this->checklistLogModel
      ->where('status', 'not_ok')
      ->orderBy('created_at', 'DESC')
      ->limit(5)
      ->findAll();

    foreach ($notOkLogs as $log) {

      $inventory = $this->inventoryModel
        ->find($log['inventory_id']);

      if (!$inventory) continue;

      $itemType = $this->itemTypeModel
        ->find($inventory['item_type_id']);

      if (!$itemType) continue;

      $notifications[] = [
        'type' => 'not_ok',
        'inventory_id' => $inventory['id'],
        'item' => $itemType['name'] ?? '-',
        'area' => $inventory['specific_area'] ?? '-',
        'message' => 'Terdapat temuan tidak sesuai.',
        'photo' => $log['photo']
      ];
    }

    return $notifications;
  }




  private function getLatestNotOkWithPhoto($year)
  {
    return $this->checklistLogModel
      ->where('created_at >=', $this->startDate)
      ->where('YEAR(created_at)', $year)
      ->where('status', 'not_ok')
      ->where('photo IS NOT NULL')
      ->orderBy('created_at', 'DESC')
      ->limit(10)
      ->findAll();
  }

  private function getMonthlyTrend($year)
  {
    $result = array_fill(1, 12, 0);

    $logs = $this->checklistLogModel
      ->select("MONTH(created_at) as month, COUNT(*) as total")
      ->where('created_at >=', $this->startDate)
      ->where('YEAR(created_at)', $year)
      ->groupBy("MONTH(created_at)")
      ->findAll();

    foreach ($logs as $row) {
      $result[(int)$row['month']] = (int)$row['total'];
    }

    return $result;
  }

  private function getChecklistOverview()
  {
    $overview = [];

    $inventories = $this->inventoryModel
      ->where('active', 1)
      ->findAll();

    foreach ($inventories as $inv) {

      $itemType = $this->itemTypeModel
        ->find($inv['item_type_id']);

      if (!$itemType) continue;

      $overview[] = [
        'id'        => $inv['id'],
        'item'      => $itemType['name'],
        'area'      => $inv['specific_area'],
        'frequency' => $itemType['checklist_frequency'],
        'status'    => '✓',
        'raw_status' => 'done'
      ];
    }

    return $overview;
  }
}
