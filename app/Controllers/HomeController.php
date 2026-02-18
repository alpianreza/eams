<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ComplianceInventoryModel;
use App\Models\ChecklistLogModel;

class HomeController extends BaseController
{
  protected $inventoryModel;
  protected $logModel;

  public function __construct()
  {
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->logModel       = new ChecklistLogModel();
  }

  public function index()
  {
    helper('period');

    $userName      = trim(session('name'));
    $selectedMonth = $this->request->getGet('month') ?? date('Y-m');

    [$year, $month] = explode('-', $selectedMonth);
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);

    $ym = $year . '-' . $month;

    // Ambil nama depan
    $nameParts = explode(' ', trim($userName));
    $firstName = trim($nameParts[0]);
    $firstName = preg_quote($firstName, '/');

    $pattern = "(^|[\n\- ]+)" . $firstName . "( |$)";

    $inventories = $this->inventoryModel
      ->select('
        compliance_inventory.*,
        asset_item_types.name as item_name,
        asset_item_types.checklist_frequency
    ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->where("compliance_inventory.pic REGEXP '{$pattern}'", null, false)
      ->findAll();




    $summary = [
      'total'   => 0,
      'pending' => 0,
      'late'    => 0,
      'not_ok'  => 0,
      'done'    => 0
    ];

    $pendingList   = [];
    $totalRequired = 0;
    $totalDone     = 0;
    $totalMissing  = 0;
    $totalNotOk    = 0;

    foreach ($inventories as $inv) {

      $summary['total']++;

      $frequency        = $inv['checklist_frequency'];
      $inv['remaining'] = 0;

      // =========================
      // Tentukan batas hari
      // =========================
      if ($selectedMonth == date('Y-m')) {
        $currentDay = date('d');
      } else {
        $currentDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
      }

      // =========================
      // DAILY
      // =========================
      if ($frequency === 'daily') {

        $holidayModel = new \App\Models\HolidayModel();

        $holidays = $holidayModel
          ->where('holiday_date >=', $ym . '-01')
          ->where('holiday_date <=', $ym . '-' . $currentDay)
          ->findAll();

        $holidayDates = array_column($holidays, 'holiday_date');

        for ($d = 1; $d <= $currentDay; $d++) {

          $date = $ym . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
          $dayOfWeek = date('w', strtotime($date));

          // skip Minggu & libur
          if ($dayOfWeek == 0) continue;
          if (in_array($date, $holidayDates)) continue;

          $totalRequired++;

          $exists = $this->logModel
            ->where('inventory_id', $inv['id'])
            ->where('period_key', $date)   // 🔥 FIX disini
            ->countAllResults();

          if ($exists > 0) {

            $totalDone++;

            $hasNotOk = $this->logModel
              ->where('inventory_id', $inv['id'])
              ->where('period_key', $date)   // 🔥 FIX disini juga
              ->where('status', 'not_ok')
              ->countAllResults();

            if ($hasNotOk > 0) {
              $totalNotOk++;
            }
          } else {
            $totalMissing++;
            $inv['remaining']++;
          }
        }
      }


      // =========================
      // WEEKLY
      // =========================
      if ($frequency === 'weekly') {

        $currentWeek = ceil($currentDay / 7);
        if ($currentWeek > 4) $currentWeek = 4;

        $totalRequired += $currentWeek;

        for ($w = 1; $w <= $currentWeek; $w++) {

          $periodKey = $ym . '-W' . $w;

          $exists = $this->logModel
            ->where('inventory_id', $inv['id'])
            ->where('period_key', $ym)
            ->countAllResults();

          if ($exists > 0) {

            $totalDone++;

            $hasNotOk = $this->logModel
              ->where('inventory_id', $inv['id'])
              ->where('period_key', $ym)
              ->where('status', 'not_ok')
              ->countAllResults();

            if ($hasNotOk > 0) {
              $totalNotOk++; // 🔥 hanya +1 per periode
            }
          } else {
            $totalMissing++;
            $inv['remaining']++;
          }
        }
      }

      // =========================
      // MONTHLY
      // =========================
      if ($frequency === 'monthly') {

        $totalRequired += 1;

        $exists = $this->logModel
          ->where('inventory_id', $inv['id'])
          ->where('period_key', $ym)
          ->countAllResults();

        if ($exists > 0) {

          $totalDone++;

          $hasNotOk = $this->logModel
            ->where('inventory_id', $inv['id'])
            ->where('period_key', $ym)
            ->where('status', 'not_ok')
            ->countAllResults();

          if ($hasNotOk > 0) {
            $totalNotOk++; // 🔥 hanya +1 per periode
          }
        } else {
          $totalMissing++;
          $inv['remaining']++;
        }
      }

      $pendingList[] = $inv;
    }

    // =========================
    // FINAL KPI
    // =========================
    $summary['pending'] = $totalMissing;
    $summary['not_ok']  = $totalNotOk;

    $progress = $totalRequired > 0
      ? round(($totalDone / $totalRequired) * 100)
      : 0;

    return view('home/index', [
      'summary'       => $summary,
      'pendingList'   => $pendingList,
      'progress'      => $progress,
      'selectedMonth' => $selectedMonth
    ]);

    $notifCount = $summary['pending'] + $summary['late'];

    $notifications = [];

    if ($summary['pending'] > 0) {
      $notifications[] = [
        'icon' => 'fas fa-clock text-warning',
        'text' => $summary['pending'] . ' periode belum checklist'
      ];
    }

    if ($summary['late'] > 0) {
      $notifications[] = [
        'icon' => 'fas fa-exclamation-circle text-danger',
        'text' => $summary['late'] . ' periode sudah melewati batas waktu'
      ];
    }
  }
}
