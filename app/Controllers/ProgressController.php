<?php

namespace App\Controllers;

use App\Models\ChecklistLogModel;
use App\Models\ComplianceInventoryModel;
use App\Models\HolidayModel;
use App\Models\UserModel;
use App\Services\WhatsAppService;

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
    helper(['period', 'checklist']);

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
      if (is_date_offday($date, $holidayDates)) continue; // skip hari libur

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
        ->where("TRIM(COALESCE(compliance_inventory.pic, '')) <> ''", null, false)
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

      if (empty($inventories)) {
        continue;
      }

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
    helper(['period', 'checklist']);

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
      ->where('compliance_inventory.active', 1)
      ->where("TRIM(COALESCE(compliance_inventory.pic, '')) <> ''", null, false)
      ->like('compliance_inventory.pic', $firstName)
      ->findAll();

    $result = [];

    foreach ($inventories as $inv) {
      $frequency = strtolower((string) ($inv['checklist_frequency'] ?? 'monthly'));

      if ($frequency === 'daily') {
        $maxDay = $selectedMonth === date('Y-m')
          ? date('d')
          : cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);

        $holidayDates = holiday_dates_between(
          $ym . '-01',
          $ym . '-' . str_pad((string) $maxDay, 2, '0', STR_PAD_LEFT)
        );

        $periodKey = null;
        for ($d = (int) $maxDay; $d >= 1; $d--) {
          $candidate = $ym . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
          if (!is_date_offday($candidate, $holidayDates)) {
            $periodKey = $candidate;
            break;
          }
        }

        if ($periodKey === null) {
          $periodKey = $ym . '-01';
        }
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

  public function sendReminderAjax()
  {
    helper(['period', 'checklist']);

    if (!in_array($this->role, ['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak punya akses untuk mengirim reminder.',
      ]);
    }

    $userId = (int) ($this->request->getPost('user_id') ?? 0);
    $selectedMonth = $this->normalizeSelectedMonth((string) ($this->request->getPost('month') ?? date('Y-m')));

    if ($userId <= 0) {
      return $this->response->setJSON([
        'ok' => false,
        'message' => 'User tidak valid.',
      ]);
    }

    $user = $this->userModel->find($userId);
    if (!$user) {
      return $this->response->setJSON([
        'ok' => false,
        'message' => 'User tidak ditemukan.',
      ]);
    }

    $summary = $this->buildUserProgressSummary($user, $selectedMonth);
    if (empty($summary['detailMissing'])) {
      return $this->response->setJSON([
        'ok' => false,
        'message' => 'User ini tidak punya checklist pending untuk periode tersebut.',
      ]);
    }

    $wa = new WhatsAppService();
    if (!$wa->canSend()) {
      return $this->response->setJSON([
        'ok' => false,
        'message' => 'WhatsApp belum siap kirim. Cek konfigurasi Fonnte.',
      ]);
    }

    $phone = $this->resolveReminderPhone($user);
    if ($phone === null) {
      return $this->response->setJSON([
        'ok' => false,
        'message' => 'Nomor WhatsApp user belum tersedia.',
      ]);
    }

    $message = $this->buildReminderMessage((string) ($user['name'] ?? $user['username'] ?? 'User'), $summary['detailMissing'], $selectedMonth);
    $result = $wa->sendMessage($phone, $message);

    if (!$result['success']) {
      return $this->response->setJSON([
        'ok' => false,
        'message' => 'Reminder gagal dikirim: ' . ($result['response'] ?? 'unknown error'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'message' => 'Reminder berhasil dikirim ke ' . ($user['name'] ?? 'user') . ' untuk periode ' . $selectedMonth . '.',
    ]);
  }

  private function normalizeSelectedMonth(string $selectedMonth): string
  {
    return preg_match('/^\d{4}-\d{2}$/', $selectedMonth) ? $selectedMonth : date('Y-m');
  }

  private function buildMonthContext(string $selectedMonth): array
  {
    $selectedMonth = $this->normalizeSelectedMonth($selectedMonth);
    [$year, $month] = explode('-', $selectedMonth);
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $ym = $year . '-' . $month;

    $currentMonth = date('Y-m');
    $currentDay   = (int) date('d');

    $maxDay = $selectedMonth === $currentMonth
      ? $currentDay
      : (int) cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);

    $holidayDates = array_column(
      (new HolidayModel())
        ->where('holiday_date >=', $ym . '-01')
        ->where('holiday_date <=', $ym . '-' . str_pad((string) $maxDay, 2, '0', STR_PAD_LEFT))
        ->findAll(),
      'holiday_date'
    );

    $dailyPeriods = [];
    for ($d = 1; $d <= $maxDay; $d++) {
      $date = $ym . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
      if (is_date_offday($date, $holidayDates)) {
        continue;
      }

      $dailyPeriods[] = [
        'key' => $date,
        'label' => str_pad((string) $d, 2, '0', STR_PAD_LEFT),
      ];
    }

    $currentWeek = $selectedMonth === $currentMonth
      ? (int) ceil($currentDay / 7)
      : 4;
    if ($currentWeek > 4) {
      $currentWeek = 4;
    }
    if ($currentWeek < 1) {
      $currentWeek = 1;
    }

    $weeklyPeriods = [];
    for ($w = 1; $w <= $currentWeek; $w++) {
      $weeklyPeriods[] = [
        'key' => $ym . '-W' . $w,
        'label' => 'W' . $w,
      ];
    }

    return compact('selectedMonth', 'year', 'month', 'ym', 'maxDay', 'dailyPeriods', 'weeklyPeriods');
  }

  private function findInventoriesForUserName(string $userName): array
  {
    $nameParts = explode(' ', trim($userName));
    $firstName = trim((string) ($nameParts[0] ?? ''));
    if ($firstName === '') {
      return [];
    }

    $safeFirstName = preg_quote($firstName, '/');
    $pattern = "(^|[\n\\- ]+)" . $safeFirstName . "( |$)";

    return $this->inventoryModel
      ->select('
        compliance_inventory.id,
        compliance_inventory.specific_area,
        compliance_inventory.pic,
        asset_item_types.name as item_name,
        asset_item_types.checklist_frequency
      ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->where('compliance_inventory.active', 1)
      ->where("TRIM(COALESCE(compliance_inventory.pic, '')) <> ''", null, false)
      ->where("compliance_inventory.pic REGEXP '{$pattern}'", null, false)
      ->findAll();
  }

  private function buildLogLookupForInventories(array $inventories, string $ym): array
  {
    $inventoryIds = array_map(static fn($row) => (int) ($row['id'] ?? 0), $inventories);
    $inventoryIds = array_values(array_filter($inventoryIds));
    if (empty($inventoryIds)) {
      return [];
    }

    $lookup = [];
    $logRows = $this->logModel
      ->select('inventory_id, period_key')
      ->whereIn('inventory_id', $inventoryIds)
      ->like('period_key', $ym, 'after')
      ->groupBy(['inventory_id', 'period_key'])
      ->findAll();

    foreach ($logRows as $row) {
      $lookup[(int) $row['inventory_id']][(string) $row['period_key']] = true;
    }

    return $lookup;
  }

  private function summarizeInventoryProgress(array $inventories, array $logLookup, array $dailyPeriods, array $weeklyPeriods, string $ym): array
  {
    $totalRequired = 0;
    $totalDone     = 0;
    $pending       = 0;
    $late          = 0;
    $detailMissing = [];

    foreach ($inventories as $inv) {
      $inventoryId = (int) ($inv['id'] ?? 0);
      $frequency = strtolower((string) ($inv['checklist_frequency'] ?? ''));
      $missingPeriods = [];

      if ($frequency === 'daily') {
        $totalRequired += count($dailyPeriods);

        foreach ($dailyPeriods as $period) {
          $periodKey = $period['key'];
          if (!empty($logLookup[$inventoryId][$periodKey])) {
            $totalDone++;
            continue;
          }

          $pending++;
          $missingPeriods[] = $period['label'];
          if (is_period_late('daily', $periodKey)) {
            $late++;
          }
        }
      } elseif ($frequency === 'weekly') {
        $totalRequired += count($weeklyPeriods);

        foreach ($weeklyPeriods as $period) {
          $periodKey = $period['key'];
          if (!empty($logLookup[$inventoryId][$periodKey])) {
            $totalDone++;
            continue;
          }

          $pending++;
          $missingPeriods[] = $period['label'];
          if (is_period_late('weekly', $periodKey)) {
            $late++;
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

      if (!empty($missingPeriods)) {
        $detailMissing[] = [
          'inventory' => ($inv['item_name'] ?? 'Item') . ' - ' . ($inv['specific_area'] ?? '-'),
          'frequency' => ucfirst($frequency),
          'missing'   => $missingPeriods,
        ];
      }
    }

    $progress = $totalRequired > 0 ? (int) round(($totalDone / $totalRequired) * 100) : 0;

    return [
      'required' => $totalRequired,
      'done' => $totalDone,
      'pending' => $pending,
      'late' => $late,
      'progress' => $progress,
      'detailMissing' => $detailMissing,
    ];
  }

  private function buildUserProgressSummary(array $user, string $selectedMonth): array
  {
    $context = $this->buildMonthContext($selectedMonth);
    $inventories = $this->findInventoriesForUserName((string) ($user['name'] ?? ''));
    $logLookup = $this->buildLogLookupForInventories($inventories, $context['ym']);
    $summary = $this->summarizeInventoryProgress($inventories, $logLookup, $context['dailyPeriods'], $context['weeklyPeriods'], $context['ym']);
    $summary['totalInventory'] = count($inventories);
    return $summary;
  }

  private function resolveReminderPhone(array $user): ?string
  {
    $phoneFields = ['wa_number', 'whatsapp_number', 'phone', 'phone_number', 'mobile', 'mobile_number', 'no_hp', 'no_telp', 'telp'];

    foreach ($phoneFields as $field) {
      if (!array_key_exists($field, $user)) {
        continue;
      }

      $normalized = $this->normalizePhone((string) $user[$field]);
      if ($normalized !== null) {
        return $normalized;
      }
    }

    $namePhoneMap = $this->parseNamePhoneMap((string) config('WhatsApp')->namePhoneMap);
    $name = $this->normalizeName((string) ($user['name'] ?? ''));
    return $name !== '' && isset($namePhoneMap[$name]) ? $namePhoneMap[$name] : null;
  }

  private function parseNamePhoneMap(string $raw): array
  {
    $map = [];
    if (trim($raw) === '') {
      return $map;
    }

    $pairs = preg_split('/[\r\n,;]+/', $raw) ?: [];
    foreach ($pairs as $pair) {
      $pair = trim($pair);
      if ($pair === '' || strpos($pair, ':') === false) {
        continue;
      }

      [$name, $phone] = array_map('trim', explode(':', $pair, 2));
      $normalizedName = $this->normalizeName($name);
      $normalizedPhone = $this->normalizePhone($phone);
      if ($normalizedName !== '' && $normalizedPhone !== null) {
        $map[$normalizedName] = $normalizedPhone;
      }
    }

    return $map;
  }

  private function normalizePhone(string $value): ?string
  {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') {
      return null;
    }

    if (str_starts_with($digits, '0')) {
      $digits = '62' . substr($digits, 1);
    } elseif (str_starts_with($digits, '8')) {
      $digits = '62' . $digits;
    }

    if (!str_starts_with($digits, '62')) {
      return null;
    }

    $length = strlen($digits);
    return ($length >= 10 && $length <= 16) ? $digits : null;
  }

  private function normalizeName(string $name): string
  {
    $name = strtolower(trim($name));
    return preg_replace('/\s+/', ' ', $name) ?? $name;
  }

  private function buildReminderMessage(string $name, array $detailMissing, string $selectedMonth): string
  {
    $periodLabel = date('F Y', strtotime($selectedMonth . '-01'));
    $lines = [];
    $lines[] = "Halo {$name},";
    $lines[] = '';
    $lines[] = "Reminder checklist EAMS untuk periode {$periodLabel}.";
    $lines[] = 'Masih ada checklist yang belum diisi:';

    foreach ($detailMissing as $index => $row) {
      $missingText = implode(', ', array_map(static fn($item) => (string) $item, $row['missing'] ?? []));
      $lines[] = ($index + 1) . '. ' . ($row['inventory'] ?? '-') . ' [' . ($row['frequency'] ?? '-') . ']';
      $lines[] = '   Missing: ' . ($missingText !== '' ? $missingText : '-');
    }

    $publicUrl = env('app.publicURL') ?: config('App')->baseURL;
    $appUrl = rtrim((string) $publicUrl, '/');

    $lines[] = '';
    $lines[] = 'Mohon segera lengkapi checklist di: ' . $appUrl . '/home';
    $lines[] = 'Terima kasih.';

    return implode("\n", $lines);
  }
}
