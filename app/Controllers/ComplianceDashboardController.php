<?php

namespace App\Controllers;

use App\Models\ChecklistLogModel;
use App\Models\AssetItemTypeModel;

class ComplianceDashboardController extends BaseController
{
  protected $logModel;
  protected $itemTypeModel;

  public function __construct()
  {
    $this->logModel = new ChecklistLogModel();
    $this->itemTypeModel = new AssetItemTypeModel();
  }

  public function index()
  {
    // ===== AMBIL TAHUN TERSEDIA =====
    $years = $this->logModel
      ->select("LEFT(period_key,4) as year")
      ->groupBy("LEFT(period_key,4)")
      ->orderBy("year", "DESC")
      ->findAll();

    $availableYears = array_column($years, 'year');

    if (empty($availableYears)) {
      $availableYears = [date('Y')];
    }

    $selectedYear = $this->request->getGet('year') ?? $availableYears[0];

    // ===== PERIODE AKTIF (MONTHLY DEFAULT) =====
    $currentMonth = date('m');
    $activePeriod = $selectedYear . '-' . $currentMonth;

    // ===== TOTAL DISTINCT INVENTORY DI PERIODE =====
    $total = $this->logModel
      ->select('COUNT(DISTINCT inventory_id) as total')
      ->like('period_key', $activePeriod, 'after')
      ->first()['total'] ?? 0;

    // ===== STATUS COUNT =====
    $statusRows = $this->logModel
      ->select('status, COUNT(DISTINCT inventory_id) as total')
      ->like('period_key', $activePeriod, 'after')
      ->groupBy('status')
      ->findAll();

    $kpi = [
      'total' => $total,
      'sesuai' => 0,
      'tidak_sesuai' => 0,
      'tidak_berlaku' => 0,
      'late' => 0
    ];

    foreach ($statusRows as $row) {
      if ($row['status'] === 'ok') {
        $kpi['sesuai'] = $row['total'];
      } elseif ($row['status'] === 'not_ok') {
        $kpi['tidak_sesuai'] = $row['total'];
      } elseif ($row['status'] === 'na') {
        $kpi['tidak_berlaku'] = $row['total'];
      }
    }

    // ===== HITUNG LATE (sementara sederhana dulu) =====
    $allInventoryIds = $this->logModel
      ->distinct()
      ->select('inventory_id')
      ->findAll();

    $checkedIds = $this->logModel
      ->distinct()
      ->select('inventory_id')
      ->like('period_key', $activePeriod, 'after')
      ->findAll();

    $allInventoryIds = array_column($allInventoryIds, 'inventory_id');
    $checkedIds = array_column($checkedIds, 'inventory_id');

    $kpi['late'] = count(array_diff($allInventoryIds, $checkedIds));

    return view('compliance/dashboard/index', [
      'availableYears' => $availableYears,
      'selectedYear'   => $selectedYear,
      'kpi'            => $kpi
    ]);
  }



  public function getTrendAjax()
  {
    $type  = $this->request->getGet('type');
    $year  = $this->request->getGet('year');
    $month = $this->request->getGet('month');

    try {

      $builder = $this->logModel->builder();

      $builder->select('period_key, status');
      $builder->select('COUNT(DISTINCT inventory_id) as total', false);
      $builder->join('asset_item_types', 'asset_item_types.id = checklist_logs.item_type_id');
      $builder->where('asset_item_types.checklist_frequency', $type);

      if ($type === 'monthly') {

        $builder->like('period_key', $year . '-', 'after');
      } elseif ($type === 'weekly') {

        $builder->like('period_key', $year . '-' . $month . '-W', 'after');
      } elseif ($type === 'daily') {

        $builder->like('period_key', $year . '-' . $month . '-', 'after');
      }

      $builder->groupBy(['period_key', 'status']);
      $builder->orderBy('period_key', 'ASC');

      $data = $builder->get()->getResultArray();

      return $this->response->setJSON($data);
    } catch (\Throwable $e) {

      return $this->response->setJSON([
        'error' => $e->getMessage()
      ]);
    }
  }

  public function getProgressAjax()
  {
    $type  = $this->request->getGet('type');
    $year  = $this->request->getGet('year');
    $month = $this->request->getGet('month');
    $week  = $this->request->getGet('week');
    $day   = $this->request->getGet('day');

    $period = $this->buildPeriod($type, $year, $month, $week, $day);

    try {

      // Total inventory berdasarkan frequency
      $inventoryModel = new \App\Models\ComplianceInventoryModel();

      $total = $inventoryModel
        ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
        ->where('asset_item_types.checklist_frequency', $type)
        ->countAllResults();

      // Sudah checklist (distinct inventory)
      $checked = $this->logModel->builder()
        ->select('COUNT(DISTINCT inventory_id) as total', false)
        ->join('asset_item_types', 'asset_item_types.id = checklist_logs.item_type_id')
        ->where('asset_item_types.checklist_frequency', $type)
        ->where('period_key', $period)
        ->get()
        ->getRowArray()['total'] ?? 0;

      return $this->response->setJSON([
        'sudah' => (int)$checked,
        'belum' => (int)($total - $checked)
      ]);
    } catch (\Throwable $e) {
      return $this->response->setJSON(['error' => $e->getMessage()]);
    }
  }


  public function getStatusPieAjax()
  {
    $type  = $this->request->getGet('type');   // tetap dikirim
    $year  = $this->request->getGet('year');
    $month = $this->request->getGet('month');

    try {

      $builder = $this->logModel->builder();

      $builder->select('status, COUNT(*) as total', false);
      $builder->join(
        'asset_item_types',
        'asset_item_types.id = checklist_logs.item_type_id'
      );

      // tetap ikut frequency aktif
      $builder->where('asset_item_types.checklist_frequency', $type);

      // 🔥 KUNCI: selalu agregat per bulan
      $builder->like('period_key', $year . '-' . $month, 'after');

      $builder->where('status !=', '');
      $builder->groupBy('status');

      $rows = $builder->get()->getResultArray();

      $data = [
        'ok'     => 0,
        'not_ok' => 0
      ];

      foreach ($rows as $row) {
        if ($row['status'] === 'ok') {
          $data['ok'] = (int)$row['total'];
        }
        if ($row['status'] === 'not_ok') {
          $data['not_ok'] = (int)$row['total'];
        }
      }

      return $this->response->setJSON($data);
    } catch (\Throwable $e) {
      return $this->response->setJSON(['error' => $e->getMessage()]);
    }
  }

  private function buildPeriod($type, $year, $month = null, $week = null, $day = null)
  {
    if ($type === 'monthly') {
      return $year . '-' . $month;
    }

    if ($type === 'weekly') {
      return $year . '-' . $month . '-W' . $week;
    }

    if ($type === 'daily') {
      return $year . '-' . $month . '-' . $day;
    }

    return null;
  }

  public function getProgressTrendAjax()
  {
    $type  = $this->request->getGet('type');
    $year  = $this->request->getGet('year');
    $month = $this->request->getGet('month');

    try {

      $builder = $this->logModel->builder();
      $builder->select('period_key');
      $builder->select('COUNT(DISTINCT inventory_id) as total', false);
      $builder->join('asset_item_types', 'asset_item_types.id = checklist_logs.item_type_id');
      $builder->where('asset_item_types.checklist_frequency', $type);

      if ($type === 'monthly') {

        // hanya sampai bulan aktif
        $builder->where("LEFT(period_key,4)", $year);
        $builder->like('period_key', $year . '-', 'after');
      }

      if ($type === 'weekly') {

        $builder->like('period_key', $year . '-' . $month . '-W', 'after');
      }

      if ($type === 'daily') {

        $builder->like('period_key', $year . '-' . $month . '-', 'after');
      }

      $builder->groupBy('period_key');
      $builder->orderBy('period_key', 'ASC');

      $rows = $builder->get()->getResultArray();

      return $this->response->setJSON($rows);
    } catch (\Throwable $e) {
      return $this->response->setJSON(['error' => $e->getMessage()]);
    }
  }

  public function getTotalInventoryByType()
  {
    $type = $this->request->getGet('type');

    $inventoryModel = new \App\Models\ComplianceInventoryModel();

    $total = $inventoryModel
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->where('asset_item_types.checklist_frequency', $type)
      ->countAllResults();

    return $this->response->setJSON([
      'total' => (int)$total
    ]);
  }

  public function getRiskInsightAjax()
  {
    try {

      $year  = $this->request->getGet('year');
      $month = $this->request->getGet('month');

      // ======================
      // TOP ITEM BULAN INI
      // ======================

      $builder = $this->logModel->builder();

      $builder->select('asset_item_types.id as item_type_id, asset_item_types.name as item_name, COUNT(*) as total', false);
      $builder->join('asset_item_types', 'asset_item_types.id = checklist_logs.item_type_id');
      $builder->where('checklist_logs.status', 'not_ok');
      $builder->like('checklist_logs.period_key', $year . '-' . $month, 'after');
      $builder->groupBy('asset_item_types.id');
      $builder->orderBy('total', 'DESC');
      $builder->limit(5);

      $topItems = $builder->get()->getResultArray();


      // ======================
      // TAMBAH TREND 1 TAHUN PER ITEM
      // ======================

      foreach ($topItems as &$item) {

        $trendBuilder = $this->logModel->builder();

        $trendBuilder->select("LEFT(checklist_logs.period_key,7) as ym, COUNT(*) as total", false);
        $trendBuilder->where('checklist_logs.status', 'not_ok');
        $trendBuilder->where('checklist_logs.item_type_id', $item['item_type_id']);
        $trendBuilder->like('checklist_logs.period_key', $year . '-', 'after');
        $trendBuilder->groupBy("LEFT(checklist_logs.period_key,7)");
        $trendBuilder->orderBy("ym", "ASC");

        $trendRows = $trendBuilder->get()->getResultArray();

        $trend = [];

        foreach ($trendRows as $row) {
          $trend[] = (int)$row['total'];
        }

        $item['trend'] = $trend;
      }


      // ======================
      // TOP AREA BULAN INI
      // ======================

      $builder = $this->logModel->builder();

      $builder->select('compliance_inventory.specific_area, compliance_inventory.id as inventory_id, COUNT(*) as total', false);
      $builder->join('compliance_inventory', 'compliance_inventory.id = checklist_logs.inventory_id');
      $builder->where('checklist_logs.status', 'not_ok');
      $builder->like('checklist_logs.period_key', $year . '-' . $month, 'after');
      $builder->groupBy('compliance_inventory.specific_area');
      $builder->orderBy('total', 'DESC');
      $builder->limit(5);

      $topAreas = $builder->get()->getResultArray();


      // ======================
      // TAMBAH TREND 1 TAHUN PER AREA
      // ======================

      foreach ($topAreas as &$area) {

        $trendBuilder = $this->logModel->builder();

        $trendBuilder->select("LEFT(checklist_logs.period_key,7) as ym, COUNT(*) as total", false);
        $trendBuilder->join('compliance_inventory', 'compliance_inventory.id = checklist_logs.inventory_id');
        $trendBuilder->where('checklist_logs.status', 'not_ok');
        $trendBuilder->where('compliance_inventory.specific_area', $area['specific_area']);
        $trendBuilder->like('checklist_logs.period_key', $year . '-', 'after');
        $trendBuilder->groupBy("LEFT(checklist_logs.period_key,7)");
        $trendBuilder->orderBy("ym", "ASC");

        $trendRows = $trendBuilder->get()->getResultArray();


        $trendMap = [];

        foreach ($trendRows as $row) {
          $trendMap[$row['ym']] = (int)$row['total'];
        }

        // pad Jan–Dec
        $trend = [];

        for ($m = 1; $m <= 12; $m++) {
          $key = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
          $trend[] = $trendMap[$key] ?? 0;
        }

        $area['trend'] = $trend;
      }


      return $this->response->setJSON([
        'items' => $topItems,
        'areas' => $topAreas
      ]);
    } catch (\Throwable $e) {
      return $this->response->setJSON([
        'error' => $e->getMessage()
      ]);
    }
  }

  public function getPendingChecklistAjax()
  {
    try {

      // ============================
      // PARAMETER FILTER
      // ============================

      $year  = date('Y');
      $month = $this->request->getGet('month') ?? date('m');
      $filterFrequency = $this->request->getGet('frequency');

      $currentMonth = date('m');
      $currentDay   = date('d');

      // ============================
      // TENTUKAN RANGE PERIODE
      // ============================

      if ($month == $currentMonth) {
        // Bulan aktif → sampai hari ini
        $endDate = new \DateTime($year . '-' . $month . '-' . $currentDay);
        $currentWeek = ceil($currentDay / 7);
      } else {
        // Bulan lama → full bulan
        $endDate = new \DateTime($year . '-' . $month . '-01');
        $endDate->modify('last day of this month');
        $currentWeek = 4;
      }

      if ($currentWeek > 4) $currentWeek = 4;

      $ym = $year . '-' . $month;
      $todayStr = $endDate->format('Y-m-d');

      // ============================
      // AMBIL INVENTORY
      // ============================

      $inventoryModel = new \App\Models\ComplianceInventoryModel();

      $inventories = $inventoryModel
        ->select('
        compliance_inventory.id,
        compliance_inventory.specific_area,
        compliance_inventory.pic,
        asset_item_types.name as item_name,
        asset_item_types.checklist_frequency
    ')
        ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
        ->findAll();


      // === 2) Ambil holiday bulan ini
      $holidayModel = new \App\Models\HolidayModel();
      $holidays = $holidayModel
        ->where('holiday_date >=', $ym . '-01')
        ->where('holiday_date <=', $ym . '-31')
        ->findAll();

      $holidayDates = array_column($holidays, 'holiday_date');

      // === 3) Build daftar tanggal kerja bulan ini (Daily)
      $workDates = [];
      $start = new \DateTime($ym . '-01');

      $start = new \DateTime($ym . '-01');

      while ($start <= $endDate) {


        $dateStr = $start->format('Y-m-d');
        $dayOfWeek = $start->format('w'); // 0 = Sunday

        if ($dayOfWeek != 0 && !in_array($dateStr, $holidayDates)) {
          $workDates[] = $dateStr;
        }

        $start->modify('+1 day');
      }

      $logModel = new \App\Models\ChecklistLogModel();

      $result = [];

      foreach ($inventories as $inv) {

        $frequency = $inv['checklist_frequency'];
        if ($filterFrequency && $frequency !== $filterFrequency) {
          continue;
        }
        $missing   = [];
        $status    = 'OK';

        // ======================
        // DAILY
        // ======================
        if ($frequency === 'daily') {

          foreach ($workDates as $date) {

            $exists = $logModel
              ->where('inventory_id', $inv['id'])
              ->where('period_key', $date)
              ->countAllResults();

            if (!$exists) {
              $missing[] = $date;
            }
          }

          if (count($missing) > 0) {
            $status = count($missing) . ' Hari Belum';
          } elseif (!in_array($todayStr, $workDates)) {
            $status = 'Libur';
          } else {
            $status = 'Due Today';
          }
        }

        // ======================
        // WEEKLY
        // ======================
        if ($frequency === 'weekly') {

          if ($month == $currentMonth) {
            $currentWeek = ceil($currentDay / 7);
          } else {
            $currentWeek = 4; // full 1 bulan
          }

          if ($currentWeek > 4) $currentWeek = 4;


          for ($w = 1; $w <= $currentWeek; $w++) {

            $periodKey = $ym . '-W' . $w;

            $exists = $logModel
              ->where('inventory_id', $inv['id'])
              ->where('period_key', $periodKey)
              ->countAllResults();

            if (!$exists) {
              $missing[] = $periodKey;
            }
          }

          if (count($missing) > 0) {
            $status = count($missing) . ' Minggu Belum';
          }
        }

        // ======================
        // MONTHLY
        // ======================
        if ($frequency === 'monthly') {

          $exists = $logModel
            ->where('inventory_id', $inv['id'])
            ->where('period_key', $ym)
            ->countAllResults();

          if (!$exists) {
            $missing[] = $ym;
            $status = 'Belum Bulan Ini';
          }
        }

        if (count($missing) > 0) {

          $result[] = [
            'inventory_id' => $inv['id'],
            'specific_area' => $inv['specific_area'],
            'item_name' => $inv['item_name'],
            'pic' => $inv['pic'],
            'frequency' => ucfirst($frequency),
            'missing' => $missing,
            'status' => $status
          ];
        }
      }

      return $this->response->setJSON($result);
    } catch (\Throwable $e) {
      return $this->response->setJSON(['error' => $e->getMessage()]);
    }
  }
}
