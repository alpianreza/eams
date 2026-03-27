<?php

namespace App\Controllers;

use App\Models\ChecklistLogModel;
use App\Models\ComplianceInventoryModel;
use App\Models\HolidayModel;
use App\Models\UserModel;

class ProgressController extends BaseController
{
  protected UserModel $userModel;
  protected ComplianceInventoryModel $inventoryModel;
  protected ChecklistLogModel $logModel;

  public function __construct()
  {
    $this->userModel      = new UserModel();
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->logModel       = new ChecklistLogModel();
  }

  public function index()
  {
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

    $selectedMonth = (string) ($this->request->getGet('month') ?? date('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
      $selectedMonth = date('Y-m');
    }

    [$year, $month] = explode('-', $selectedMonth);
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $ym = $year . '-' . $month;

    $currentMonth = date('Y-m');
    $currentDay   = (int) date('d');

    $maxDay = $selectedMonth === $currentMonth
      ? $currentDay
      : (int) cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);

    // Ambil holiday sekali
    $holidayDates = array_column(
      (new HolidayModel())
        ->where('holiday_date >=', $ym . '-01')
        ->where('holiday_date <=', $ym . '-' . str_pad((string) $maxDay, 2, '0', STR_PAD_LEFT))
        ->findAll(),
      'holiday_date'
    );

    // Precompute daily periods aktif
    $dailyPeriods = [];
    for ($d = 1; $d <= $maxDay; $d++) {
      $date = $ym . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
      $dayOfWeek = date('w', strtotime($date));

      if ($dayOfWeek == 0) continue; // skip Minggu
      if (in_array($date, $holidayDates, true)) continue; // skip libur

      $dailyPeriods[] = [
        'key' => $date,
        'label' => str_pad((string) $d, 2, '0', STR_PAD_LEFT),
      ];
    }

    // Precompute weekly periods aktif
    $currentWeek = $selectedMonth === $currentMonth
      ? (int) ceil($currentDay / 7)
      : 4;
    if ($currentWeek > 4) $currentWeek = 4;
    if ($currentWeek < 1) $currentWeek = 1;

    $weeklyPeriods = [];
    for ($w = 1; $w <= $currentWeek; $w++) {
      $weeklyPeriods[] = [
        'key' => $ym . '-W' . $w,
        'label' => "W{$w}",
      ];
    }

    $users = $this->userModel
      ->where('status', 'active')
      ->whereNotIn('role', ['auditor'])
      ->where('username !=', 'admin')
      ->findAll();

    // Cache inventory per first name
    $firstNameByUserId = [];
    $uniqueFirstNames = [];

    foreach ($users as $user) {
      $nameParts = explode(' ', trim((string) $user['name']));
      $firstName = trim((string) ($nameParts[0] ?? ''));

      $firstNameByUserId[$user['id']] = $firstName;
      if ($firstName !== '') {
        $uniqueFirstNames[$firstName] = true;
      }
    }

    $inventoryByFirstName = [];
    $allInventoryIds = [];

    foreach (array_keys($uniqueFirstNames) as $firstName) {
      $safeFirstName = preg_quote($firstName, '/');
      $pattern = "(^|[\n\- ]+)" . $safeFirstName . "( |$)";

      $rows = $this->inventoryModel
        ->select('
          compliance_inventory.id,
          compliance_inventory.specific_area,
          asset_item_types.name as item_name,
          asset_item_types.checklist_frequency
        ')
        ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
        ->where('compliance_inventory.active', 1)
        ->where("compliance_inventory.pic REGEXP '{$pattern}'", null, false)
        ->findAll();

      $inventoryByFirstName[$firstName] = $rows;

      foreach ($rows as $row) {
        $allInventoryIds[(int) $row['id']] = true;
      }
    }

    // Ambil log sekali untuk semua inventory di bulan terpilih
    $logLookup = [];
    if (!empty($allInventoryIds)) {
      $logRows = $this->logModel
        ->select('inventory_id, period_key')
        ->whereIn('inventory_id', array_keys($allInventoryIds))
        ->like('period_key', $ym, 'after')
        ->groupBy(['inventory_id', 'period_key'])
        ->findAll();

      foreach ($logRows as $row) {
        $inventoryId = (int) $row['inventory_id'];
        $periodKey = (string) $row['period_key'];
        $logLookup[$inventoryId][$periodKey] = true;
      }
    }

    $result = [];

    foreach ($users as $user) {
      $firstName = $firstNameByUserId[$user['id']] ?? '';
      $inventories = $inventoryByFirstName[$firstName] ?? [];

      $totalRequired = 0;
      $totalDone     = 0;
      $pending       = 0;
      $late          = 0;
      $detailMissing = [];

      foreach ($inventories as $inv) {
        $inventoryId = (int) $inv['id'];
        $frequency = strtolower((string) ($inv['checklist_frequency'] ?? ''));
        $missingPeriods = [];

        if ($frequency === 'daily') {
          $totalRequired += count($dailyPeriods);

          foreach ($dailyPeriods as $period) {
            $periodKey = $period['key'];

            if (!empty($logLookup[$inventoryId][$periodKey])) {
              $totalDone++;
            } else {
              $pending++;
              $missingPeriods[] = $period['label'];

              if (is_period_late('daily', $periodKey)) {
                $late++;
              }
            }
          }
        } elseif ($frequency === 'weekly') {
          $totalRequired += count($weeklyPeriods);

          foreach ($weeklyPeriods as $period) {
            $periodKey = $period['key'];

            if (!empty($logLookup[$inventoryId][$periodKey])) {
              $totalDone++;
            } else {
              $pending++;
              $missingPeriods[] = $period['label'];

              if (is_period_late('weekly', $periodKey)) {
                $late++;
              }
            }
          }
        } elseif ($frequency === 'monthly') {
          $totalRequired += 1;

          if (!empty($logLookup[$inventoryId][$ym])) {
            $totalDone++;
          } else {
            $pending++;
            $missingPeriods[] = 'Belum';

            if (is_period_late('monthly', $ym)) {
              $late++;
            }
          }
        }

        // Kirim ke modal hanya item yang benar-benar missing
        if (!empty($missingPeriods)) {
          $detailMissing[] = [
            'inventory' => ($inv['item_name'] ?? 'Item') . ' - ' . ($inv['specific_area'] ?? '-'),
            'frequency' => ucfirst($frequency),
            'missing'   => $missingPeriods
          ];
        }
      }

      $progress = $totalRequired > 0
        ? (int) round(($totalDone / $totalRequired) * 100)
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

    $userId = (int) $this->request->getGet('user_id');
    $selectedMonth = (string) ($this->request->getGet('month') ?? date('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
      $selectedMonth = date('Y-m');
    }

    [$year, $month] = explode('-', $selectedMonth);
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $ym = $year . '-' . $month;

    $user = $this->userModel->find($userId);
    if (!$user) {
      return $this->response->setJSON([]);
    }

    $firstName = explode(' ', trim((string) $user['name']))[0] ?? '';

    $inventories = $this->inventoryModel
      ->select('
        compliance_inventory.id,
        compliance_inventory.specific_area,
        asset_item_types.name as item_name,
        asset_item_types.checklist_frequency
      ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->like('compliance_inventory.pic', $firstName)
      ->findAll();

    $result = [];

    foreach ($inventories as $inv) {
      $frequency = strtolower((string) ($inv['checklist_frequency'] ?? 'monthly'));

      if ($frequency === 'daily') {
        $day = $selectedMonth === date('Y-m')
          ? date('d')
          : cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);
        $periodKey = $ym . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
      } elseif ($frequency === 'weekly') {
        $week = $selectedMonth === date('Y-m')
          ? (int) ceil(((int) date('d')) / 7)
          : 4;
        if ($week > 4) $week = 4;
        $periodKey = $ym . '-W' . $week;
      } else {
        $periodKey = $ym;
      }

      $exists = $this->logModel
        ->where('inventory_id', $inv['id'])
        ->where('period_key', $periodKey)
        ->countAllResults();

      $result[] = [
        'item'      => $inv['item_name'],
        'area'      => $inv['specific_area'],
        'frequency' => ucfirst($frequency),
        'status'    => $exists > 0 ? 'Sudah' : 'Belum'
      ];
    }

    return $this->response->setJSON([
      'name' => $user['name'],
      'data' => $result
    ]);
  }
}
