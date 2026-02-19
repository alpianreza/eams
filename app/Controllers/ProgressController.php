<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ComplianceInventoryModel;
use App\Models\ChecklistLogModel;
use App\Models\HolidayModel;

class ProgressController extends BaseController
{
  protected $userModel;
  protected $inventoryModel;
  protected $logModel;

  public function __construct()
  {
    $this->userModel      = new UserModel();
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->logModel       = new ChecklistLogModel();
  }

  public function index()
  {
    // 🔒 hanya admin & compliance
    if (!in_array($this->role, ['admin', 'compliance'])) {
      return redirect()->to('/');
    }

    $selectedMonth = $this->request->getGet('month') ?? date('Y-m');

    return view('compliance/progress/index', [
      'title'         => 'Monitoring Progress User',
      'selectedMonth' => $selectedMonth
    ]);
  }

  public function getProgressAjax()
  {
    helper('period');

    $selectedMonth = $this->request->getGet('month') ?? date('Y-m');
    [$year, $month] = explode('-', $selectedMonth);
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $ym = $year . '-' . $month;

    $currentMonth = date('Y-m');
    $currentDay   = date('d');

    // Ambil holiday sekali saja
    $holidayModel = new HolidayModel();

    if ($selectedMonth == $currentMonth) {
      $maxDay = $currentDay;
    } else {
      $maxDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }

    $holidays = $holidayModel
      ->where('holiday_date >=', $ym . '-01')
      ->where('holiday_date <=', $ym . '-' . $maxDay)
      ->findAll();

    $holidayDates = array_column($holidays, 'holiday_date');

    $users = $this->userModel
      ->where('status', 'active')
      ->whereNotIn('role', ['auditor'])
      ->where('username !=', 'admin')
      ->findAll();

    $result = [];

    foreach ($users as $user) {

      $nameParts = explode(' ', trim($user['name']));
      $firstName = trim($nameParts[0]);
      $firstName = preg_quote($firstName, '/');

      $pattern = "(^|[\n\- ]+)" . $firstName . "( |$)";

      $inventories = $this->inventoryModel
        ->select('
          compliance_inventory.id,
          compliance_inventory.specific_area,
          asset_item_types.name as item_name,
          asset_item_types.checklist_frequency
        ')

        ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
        ->where("compliance_inventory.pic REGEXP '{$pattern}'", null, false)
        ->findAll();

      $totalRequired = 0;
      $totalDone     = 0;
      $pending       = 0;
      $late          = 0;

      $detailMissing = [];

      foreach ($inventories as $inv) {

        $frequency = $inv['checklist_frequency'];
        $missingPeriods = [];

        // ================= DAILY =================
        if ($frequency === 'daily') {

          for ($d = 1; $d <= $maxDay; $d++) {

            $date = $ym . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
            $dayOfWeek = date('w', strtotime($date));

            if ($dayOfWeek == 0) continue;
            if (in_array($date, $holidayDates)) continue;

            $totalRequired++;

            $exists = $this->logModel
              ->where('inventory_id', $inv['id'])
              ->where('period_key', $date)
              ->countAllResults();

            if ($exists > 0) {
              $totalDone++;
            } else {
              $pending++;
              $missingPeriods[] = date('d', strtotime($date));

              if (is_period_late('daily', $date)) {
                $late++;
              }
            }
          }

          $detailMissing[] = [
            'inventory' => ($inv['item_name'] ?? 'Item') .
              ' — ' .
              ($inv['specific_area'] ?? '-'),
            'frequency' => ucfirst($frequency),
            'missing'   => $missingPeriods
          ];


          continue;
        }

        // ================= WEEKLY =================
        if ($frequency === 'weekly') {

          if ($selectedMonth == $currentMonth) {
            $currentWeek = ceil($currentDay / 7);
          } else {
            $currentWeek = 4;
          }

          if ($currentWeek > 4) $currentWeek = 4;

          for ($w = 1; $w <= $currentWeek; $w++) {

            $periodKey = $ym . '-W' . $w;
            $totalRequired++;

            $exists = $this->logModel
              ->where('inventory_id', $inv['id'])
              ->where('period_key', $periodKey)
              ->countAllResults();

            if ($exists > 0) {
              $totalDone++;
            } else {
              $pending++;
              $missingPeriods[] = "W{$w}";

              if (is_period_late('weekly', $periodKey)) {
                $late++;
              }
            }
          }

          $detailMissing[] = [
            'inventory' => ($inv['item_name'] ?? 'Item') .
              ' — ' .
              ($inv['specific_area'] ?? '-'),
            'frequency' => ucfirst($frequency),
            'missing'   => $missingPeriods
          ];


          continue;
        }

        // ================= MONTHLY =================
        if ($frequency === 'monthly') {

          $totalRequired++;

          $exists = $this->logModel
            ->where('inventory_id', $inv['id'])
            ->where('period_key', $ym)
            ->countAllResults();

          if ($exists > 0) {
            $totalDone++;
          } else {
            $pending++;
            $missingPeriods[] = 'Belum';

            if (is_period_late('monthly', $ym)) {
              $late++;
            }
          }

          $detailMissing[] = [
            'inventory' => ($inv['item_name'] ?? 'Item') .
              ' — ' .
              ($inv['specific_area'] ?? '-'),
            'frequency' => ucfirst($frequency),
            'missing'   => $missingPeriods
          ];
        }
      }

      $progress = $totalRequired > 0
        ? round(($totalDone / $totalRequired) * 100)
        : 0;

      $result[] = [
        'name'           => $user['name'],
        'totalInventory' => count($inventories),
        'required'       => $totalRequired,
        'done'           => $totalDone,
        'pending'        => $pending,
        'late'           => $late,
        'progress'       => $progress,
        'id'             => $user['id'],
        'detailMissing'  => $detailMissing
      ];
    }

    usort($result, fn($a, $b) => $a['progress'] <=> $b['progress']);

    return $this->response->setJSON($result);
  }

  public function export()
  {
    $month = $this->request->getGet('month') ?? date('Y-m');

    $response = $this->getProgressAjax();
    $data = json_decode($response->getBody(), true);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="progress-' . $month . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['User', 'Total Periode', 'Done', 'Pending', 'Late', 'Progress %']);

    foreach ($data as $row) {
      fputcsv($output, [
        $row['name'],
        $row['required'],
        $row['done'],
        $row['pending'],
        $row['late'],
        $row['progress']
      ]);
    }

    fclose($output);
    exit;
  }

  public function getUserDetailAjax()
  {
    helper('period');

    $userId = $this->request->getGet('user_id');
    $selectedMonth = $this->request->getGet('month') ?? date('Y-m');

    [$year, $month] = explode('-', $selectedMonth);
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $ym = $year . '-' . $month;

    $user = $this->userModel->find($userId);
    if (!$user) {
      return $this->response->setJSON([]);
    }

    $firstName = explode(' ', trim($user['name']))[0];

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id,
                  compliance_inventory.specific_area,
                  asset_item_types.name as item_name,
                  asset_item_types.checklist_frequency')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->like('compliance_inventory.pic', $firstName)
      ->findAll();

    $result = [];

    foreach ($inventories as $inv) {

      $frequency = $inv['checklist_frequency'];
      $periodKey = generate_period_key($frequency);

      $exists = $this->logModel
        ->where('inventory_id', $inv['id'])
        ->where('period_key', $periodKey)
        ->countAllResults();

      $result[] = [
        'item'      => $inv['item_name'],
        'area'      => $inv['specific_area'],
        'frequency' => ucfirst($frequency),
        'status'    => $exists > 0 ? '✓ Sudah' : 'Belum'
      ];
    }

    return $this->response->setJSON([
      'name' => $user['name'],
      'data' => $result
    ]);
  }
}
