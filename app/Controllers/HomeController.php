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
    $userName = preg_replace('/\s+/', ' ', trim(session('name')));

    // ambil 2 kata pertama user
    $nameParts = explode(' ', $userName);

    $firstTwoWords = implode(' ', array_slice($nameParts, 0, 2));
    $firstTwoWords = preg_quote($firstTwoWords, '/');

    $pattern = "(^| - ){$firstTwoWords}($| - )";


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

    $pendingList = [];

    $totalRequired = 0;
    $totalDone     = 0;


    foreach ($inventories as $inv) {

      $summary['total']++;

      // 🔥 pakai helper yang benar
      $periodKey = generate_period_key($inv['checklist_frequency']);

      $log = $this->logModel
        ->where('inventory_id', $inv['id'])
        ->where('period_key', $periodKey)
        ->first();

      if (!$log) {
        $summary['pending']++;

        // cek apakah sudah lewat → LATE
        if (is_period_late($inv['checklist_frequency'], $periodKey)) {
          $summary['late']++;
        }

        $inv['remaining'] = 0;

        $frequency = $inv['checklist_frequency'];

        if ($frequency === 'daily') {

          $year  = date('Y');
          $month = date('m');
          $currentDay = date('d');

          $ym = $year . '-' . $month;

          $endDate = new \DateTime($ym . '-' . $currentDay);

          $holidayModel = new \App\Models\HolidayModel();
          $holidays = $holidayModel
            ->where('holiday_date >=', $ym . '-01')
            ->where('holiday_date <=', $ym . '-' . $currentDay)
            ->findAll();

          $holidayDates = array_column($holidays, 'holiday_date');

          $workDates = [];
          $start = new \DateTime($ym . '-01');

          while ($start <= $endDate) {

            $dateStr = $start->format('Y-m-d');
            $dayOfWeek = $start->format('w');

            if ($dayOfWeek != 0 && !in_array($dateStr, $holidayDates)) {
              $workDates[] = $dateStr;
            }

            $start->modify('+1 day');
          }

          $missing = [];

          $totalRequired += count($workDates);

          foreach ($workDates as $date) {

            $exists = $this->logModel
              ->where('inventory_id', $inv['id'])
              ->where('period_key', $date)
              ->countAllResults();

            if ($exists) {
              $totalDone++;
            } else {
              $missing[] = $date;
            }
          }


          $inv['remaining'] = count($missing);
        }

        if ($frequency === 'weekly') {

          $year  = date('Y');
          $month = date('m');
          $currentDay = date('d');

          $currentWeek = ceil($currentDay / 7);
          if ($currentWeek > 4) $currentWeek = 4;

          $ym = $year . '-' . $month;

          $missing = [];

          $totalRequired += $currentWeek;


          for ($w = 1; $w <= $currentWeek; $w++) {

            $periodKey = $ym . '-W' . $w;

            $exists = $this->logModel
              ->where('inventory_id', $inv['id'])
              ->where('period_key', $periodKey)
              ->countAllResults();

            if ($exists) {
              $totalDone++;
            } else {
              $missing[] = $periodKey;
            }
          }

          $inv['remaining'] = count($missing);
        }

        if ($frequency === 'monthly') {

          $ym = date('Y-m');

          $totalRequired += 1;

          $exists = $this->logModel
            ->where('inventory_id', $inv['id'])
            ->where('period_key', $ym)
            ->countAllResults();

          if ($exists) {
            $totalDone++;
          }

          $inv['remaining'] = $exists ? 0 : 1;
        }

        $pendingList[] = $inv;
        continue;
      }

      $pendingList[] = $inv;

      $summary['done']++;

      if ($log['status'] === 'not_ok') {
        $summary['not_ok']++;
      }
    }

    $progress = $totalRequired > 0
      ? round(($totalDone / $totalRequired) * 100)
      : 0;


    return view('home/index', [
      'summary'     => $summary,
      'pendingList' => $pendingList,
      'progress'    => $progress
    ]);
  }
}
