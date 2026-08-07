<?php

namespace App\Controllers;

use App\Models\ChecklistLogModel;
use App\Models\ComplianceInventoryModel;
use App\Models\NotificationModel;

class HomeController extends BaseController
{
  protected $inventoryModel;
  protected $logModel;

  public function __construct()
  {
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->logModel = new ChecklistLogModel();
  }

  public function index()
  {
    page('Home Compliance');
    if ($this->request->getGet('view') === 'notifications') return $this->notificationCenter();
    helper(['period', 'checklist']);
    $selectedMonth = (string) ($this->request->getGet('month') ?? date('Y-m'));
    if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) $selectedMonth = date('Y-m');
    [$year, $month] = explode('-', $selectedMonth); $month = str_pad($month, 2, '0', STR_PAD_LEFT); $ym = $year . '-' . $month;

    $inventories = $this->inventoryModel->select('compliance_inventory.*, asset_item_types.name as item_name, asset_item_types.checklist_frequency')->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')->assignedToUser((int) session()->get('user_id'))->findAll();
    $summary = ['total' => 0, 'pending' => 0, 'late' => 0, 'not_ok' => 0, 'done' => 0];
    $pendingList = []; $totalRequired = 0; $totalDone = 0; $totalMissing = 0; $totalNotOk = 0;
    $currentDay = $selectedMonth === date('Y-m') ? (int) date('d') : (int) cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);
    $holidayDates = array_column((new \App\Models\HolidayModel())->where('holiday_date >=', $ym . '-01')->where('holiday_date <=', $ym . '-' . str_pad((string) $currentDay, 2, '0', STR_PAD_LEFT))->findAll(), 'holiday_date');

    foreach ($inventories as $inv) {
      $summary['total']++; $missingPeriods = []; $frequency = strtolower((string) ($inv['checklist_frequency'] ?? 'monthly')); $inv['remaining'] = 0;
      $periods = [];
      if ($frequency === 'daily') {
        for ($d = 1; $d <= $currentDay; $d++) { $date = $ym . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT); if (! is_date_offday($date, $holidayDates)) $periods[] = ['key' => $date, 'label' => str_pad((string) $d, 2, '0', STR_PAD_LEFT)]; }
      } elseif ($frequency === 'weekly') {
        $weekCount = min(4, (int) ceil($currentDay / 7)); for ($w = 1; $w <= $weekCount; $w++) $periods[] = ['key' => $ym . '-W' . $w, 'label' => (string) $w];
      } else $periods[] = ['key' => $ym, 'label' => $month];

      foreach ($periods as $period) {
        $totalRequired++;
        $logs = $this->logModel->where('inventory_id', $inv['id'])->where('period_key', $period['key'])->findAll();
        if ($logs !== []) { $totalDone++; foreach ($logs as $log) if (($log['status'] ?? '') === 'not_ok') { $totalNotOk++; break; } }
        else { $totalMissing++; $inv['remaining']++; $missingPeriods[] = $period['label']; }
      }
      $inv['missing_periods'] = $missingPeriods; $pendingList[] = $inv;
    }

    $summary['pending'] = $totalMissing; $summary['not_ok'] = $totalNotOk;
    $progress = $totalRequired > 0 ? round(($totalDone / $totalRequired) * 100) : 0;
    return view('home/index', ['summary' => $summary, 'pendingList' => $pendingList, 'progress' => $progress, 'selectedMonth' => $selectedMonth, 'showAllPending' => $this->request->getGet('show') === 'all']);
  }

  private function notificationCenter()
  {
    $userId = (int) session()->get('user_id'); $filter = (string) ($this->request->getGet('type') ?? 'all');
    $items = [];
    if (db_connect()->tableExists('notifications')) {
      $model = new NotificationModel(); $model->where('user_id', $userId);
      if ($filter === 'unread') $model->where('read_at', null); elseif (in_array($filter, ['assignment', 'reminder', 'comment', 'approval'], true)) $model->where('type', $filter);
      $items = $model->orderBy('created_at', 'DESC')->findAll(100);
    }
    return view('home/notifications', ['title' => 'Pusat Notifikasi', 'notificationItems' => $items, 'notificationFilter' => $filter]);
  }
}
