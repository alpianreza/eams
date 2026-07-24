<?php

namespace App\Controllers;

use App\Models\ComplianceInventoryModel;
use App\Models\InventoryCategoryModel;
use App\Models\AreaModel;
use App\Models\ChecklistLogModel;


class ComplianceInventoryController extends BaseController
{
  private const CCTV_ITEM_TYPE_ID = 13;
  private const EMERGENCY_LIGHT_ITEM_TYPE_ID = 4;
  private const EMERGENCY_EXIT_LIGHT_ITEM_TYPE_ID = 59;
  private const FIRST_AID_BOX_ITEM_TYPE_ID = 10;
  private const FIRST_AID_CONTENT_ITEM_TYPE_ID = 33;
  private const FIRE_EXTINGUISHER_ITEM_TYPE_ID = 1;
  private const INTRUSION_ALARM_ITEM_TYPE_ID = 8;
  private const HYDRANT_ITEM_TYPE_ID = 2;
  private const HEAT_DETECTOR_ITEM_TYPE_ID = 6;
  private const SMOKE_DETECTOR_ITEM_TYPE_ID = 7;
  private const GATE_INSPECTION_ITEM_TYPE_ID = 40;
  private const TOILET_CHECKLIST_ITEM_TYPE_ID = 52;
  protected $inventoryModel;
  protected $categoryModel;
  protected $areaModel;

  public function __construct()
  {
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->categoryModel  = new InventoryCategoryModel();
    $this->areaModel      = new AreaModel();
  }

  public function index()
  {

    page('Compliance Inventory');

    $request = $this->request;

    $categoryId = $request->getGet('category');
    $areaId     = $request->getGet('area');
    $keyword    = $request->getGet('q');
    $perPage    = $request->getGet('perPage') ?? 20;
    $sort       = strtolower(trim((string) $request->getGet('sort')));
    $direction  = strtolower(trim((string) $request->getGet('direction')));

    $sortMap = [
      'no' => 'compliance_inventory.id',
      'item' => 'asset_item_types.name',
      'asset_code' => 'compliance_inventory.asset_code',
      'type' => 'compliance_inventory.type_description',
      'specific_area' => 'compliance_inventory.specific_area',
      'pic' => 'compliance_inventory.pic',
      'status' => 'compliance_inventory.status',
    ];

    if (! isset($sortMap[$sort])) {
      $sort = 'no';
    }

    if (! in_array($direction, ['asc', 'desc'], true)) {
      $direction = 'asc';
    }

    $query = $this->inventoryModel
      ->select('
      compliance_inventory.*,
      inventory_categories.name AS category_name,
      asset_item_types.name AS item_display_name,
      areas.name AS area_name
    ')
      ->join('inventory_categories', 'inventory_categories.id = compliance_inventory.category_id', 'left')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->join('areas', 'areas.id = compliance_inventory.area_id', 'left');

    // ======================
    // FILTER KATEGORI (ID)
    // ======================
    if ($categoryId) {
      $query->where('compliance_inventory.category_id', $categoryId);
    }

    // ======================
    // FILTER AREA (ID)
    // ======================
    if ($areaId) {
      $query->where('compliance_inventory.area_id', $areaId);
    }

    // ======================
    // SEARCH
    // ======================
    if ($keyword) {
      $query->groupStart()
        ->like('asset_item_types.name', $keyword)
        ->orLike('compliance_inventory.asset_code', $keyword)
        ->orLike('compliance_inventory.pic', $keyword)
        ->groupEnd();
    }

    $query->orderBy($sortMap[$sort], strtoupper($direction));

    if ($sort !== 'no') {
      $query->orderBy('compliance_inventory.id', 'ASC');
    }

    return view('compliance/inventory/index', [
      'inventories' => $query->paginate($perPage),
      'pager'       => $this->inventoryModel->pager,
      'categories'  => $this->categoryModel->findAll(),
      'areas'       => $this->areaModel->findAll(),
      'category'    => $categoryId,
      'area'        => $areaId,
      'keyword'     => $keyword,
      'perPage'     => $perPage,
      'sort'        => $sort,
      'direction'   => $direction,
      'isWritable'  => hasWriteAccess()
    ]);
  }


  public function update($id)
  {
    if (!hasRole(['admin', 'compliance'])) {
      return $this->response->setJSON(['status' => 'error']);
    }

    $inventory = $this->inventoryModel->find($id);

    $newAssetCode = $this->request->getPost('asset_code');
    $oldAssetCode = $inventory['asset_code'];

    $newAssetCode = trim($this->request->getPost('asset_code'));

    if (!$newAssetCode) {

      $itemTypeId = $this->request->getPost('item_type_id');
      $categoryId = $this->request->getPost('category_id');

      $itemTypeModel = new \App\Models\AssetItemTypeModel();
      $categoryModel = new \App\Models\InventoryCategoryModel();

      $item     = $itemTypeModel->find($itemTypeId);
      $category = $categoryModel->find($categoryId);

      if ($item && $category) {

        $prefix = strtoupper($category['code']) . '-' . strtoupper($item['code']);

        $last = $this->inventoryModel
          ->like('asset_code', $prefix, 'after')
          ->orderBy('id', 'DESC')
          ->first();

        $next = 1;

        if ($last) {
          preg_match('/(\d+)$/', $last['asset_code'], $m);
          if ($m) $next = intval($m[1]) + 1;
        }

        $newAssetCode = $prefix . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
      }
    }

    $data = [
      'category_id'      => $this->request->getPost('category_id'),
      'area_id'          => $this->request->getPost('area_id'),
      'item_type_id'     => $this->request->getPost('item_type_id'),
      'asset_code'       => $newAssetCode,
      'type_description' => $this->request->getPost('type_description'),
      'specific_area'    => $this->request->getPost('specific_area'),
      'pic'              => $this->request->getPost('pic'),
      'status'           => $this->request->getPost('status'),
      'remark'           => $this->request->getPost('remark'),
      'expired_date'     => $this->request->getPost('expired_date')
    ];

    $this->inventoryModel->update($id, $data);

    // =====================================
    // QR REGENERATE JIKA CODE BERUBAH
    // =====================================
    if ($newAssetCode !== $oldAssetCode) {

      $detailUrl = base_url('compliance/inventory/detail/' . $id);

      $qrFile = 'qr_inv_' . $id . '.png';
      $qrPath = FCPATH . 'uploads/qr/' . $qrFile;

      $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='
        . urlencode($detailUrl);

      $qrContent = @file_get_contents($qrApiUrl);

      if ($qrContent) {

        file_put_contents($qrPath, $qrContent);

        // tambah text lagi di tengah
        $image = imagecreatefrompng($qrPath);

        $black = imagecolorallocate($image, 0, 0, 0);
        $white = imagecolorallocate($image, 255, 255, 255);

        $font = 5;

        $imgW = imagesx($image);
        $imgH = imagesy($image);

        $textW = imagefontwidth($font) * strlen($newAssetCode);
        $textH = imagefontheight($font);

        $x = ($imgW - $textW) / 2;
        $y = ($imgH - $textH) / 2;

        imagefilledrectangle($image, $x - 4, $y - 3, $x + $textW + 4, $y + $textH + 3, $white);
        imagestring($image, $font, $x, $y, $newAssetCode, $black);

        imagepng($image, $qrPath);
        imagedestroy($image);

        $this->inventoryModel->update($id, [
          'qr_image' => $qrFile
        ]);
      }
    }

    return $this->response->setJSON([
      'status' => 'success',
      'asset_code' => $newAssetCode,
      'qr_image' => $qrFile ?? $inventory['qr_image'],
      'specific_area' => $data['specific_area'],
      'type_description' => $data['type_description'],
      'pic' => $data['pic'],
      'remark' => $data['remark'],
      'status_label' => $data['status']
    ]);
  }

  public function delete($id)
  {
    if (! hasRole(['admin', 'compliance'])) {

      if ($this->request->isAJAX()) {
        return $this->response->setJSON(['status' => 'error']);
      }

      return redirect()->to('/unauthorized');
    }

    $this->inventoryModel->delete($id);

    // AJAX mode
    if ($this->request->isAJAX()) {
      return $this->response->setJSON([
        'status' => 'success'
      ]);
    }

    return redirect()->back()->with('success', 'Inventory dihapus');
  }

  public function store()
  {
    if (!hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    $itemTypeId = $this->request->getPost('item_type_id');
    $assetCode  = $this->request->getPost('asset_code');

    // =====================================
    // AUTO GENERATE NO INVENTARIS
    // =====================================
    if (!$assetCode && $itemTypeId) {

      $itemTypeModel   = new \App\Models\AssetItemTypeModel();
      $categoryModel   = new \App\Models\InventoryCategoryModel();

      $item     = $itemTypeModel->find($itemTypeId);
      $category = $categoryModel->find($this->request->getPost('category_id'));

      if ($item && $item['code'] && $category && $category['code']) {

        $prefix = strtoupper($category['code']) . '-' . strtoupper($item['code']);

        // cari terakhir dengan prefix itu
        $last = $this->inventoryModel
          ->like('asset_code', $prefix, 'after')
          ->orderBy('id', 'DESC')
          ->first();

        $next = 1;

        if ($last) {
          preg_match('/(\d+)$/', $last['asset_code'], $m);
          if ($m) $next = intval($m[1]) + 1;
        }

        $assetCode = $prefix . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
      }
    }

    // =====================================
    // FOTO
    // =====================================
    $photoFile = $this->request->getFile('photo');
    $photoName = null;

    if ($photoFile && $photoFile->isValid()) {
      $photoName = $photoFile->getRandomName();
      $photoFile->move(FCPATH . 'uploads/inventory', $photoName);
    }

    // =====================================
    // INSERT DATA
    // =====================================
    $data = [
      'category_id'      => $this->request->getPost('category_id'),
      'area_id'          => $this->request->getPost('area_id'),
      'item_type_id'     => $itemTypeId,
      'asset_code'       => $assetCode,
      'type_description' => $this->request->getPost('type_description'),
      'specific_area'    => $this->request->getPost('specific_area'),
      'pic'              => $this->request->getPost('pic'),
      'status'           => $this->request->getPost('status'),
      'qty'              => $this->request->getPost('qty'),
      'remark'           => $this->request->getPost('remark'),
      'expired_date'     => $this->request->getPost('expired_date'),
      'photo'            => $photoName
    ];

    $this->inventoryModel->insert($data);
    $inventoryId = $this->inventoryModel->getInsertID();

    // =====================================
    // QR GENERATE
    // =====================================
    $detailUrl = base_url('compliance/inventory/detail/' . $inventoryId);

    $qrFile = 'qr_inv_' . $inventoryId . '.png';
    $qrPath = FCPATH . 'uploads/qr/' . $qrFile;

    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='
      . urlencode($detailUrl);

    // download QR
    file_put_contents($qrPath, file_get_contents($qrApiUrl));


    // =====================================
    // TAMBAH TEXT NO INVENTARIS DI TENGAH
    // =====================================
    $assetCodeText = $assetCode;

    // buka image
    $image = imagecreatefrompng($qrPath);

    // warna text
    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);

    // font built-in
    $font = 5;

    // hitung posisi tengah
    $imgW = imagesx($image);
    $imgH = imagesy($image);

    $textW = imagefontwidth($font) * strlen($assetCodeText);
    $textH = imagefontheight($font);

    $x = ($imgW - $textW) / 2;
    $y = ($imgH - $textH) / 2;

    // background putih biar kebaca
    imagefilledrectangle(
      $image,
      $x - 6,
      $y - 4,
      $x + $textW + 6,
      $y + $textH + 4,
      $white
    );

    // tulis text
    imagestring($image, $font, $x, $y, $assetCodeText, $black);

    // simpan ulang
    imagepng($image, $qrPath);
    imagedestroy($image);

    // simpan nama qr ke DB
    $this->inventoryModel->update($inventoryId, [
      'qr_image' => $qrFile
    ]);

    // =====================================
    // RESPONSE
    // =====================================
    if ($this->request->isAJAX()) {
      return $this->response->setJSON([
        'status' => 'success',
        'inventory_id' => $inventoryId,
        'qr_image' => $qrFile
      ]);
    }

    return redirect()->to('/compliance/inventory')
      ->with('success', 'Inventory & QR Code berhasil ditambahkan');
  }

  public function detail($id)
  {
    page('Detail Compliance Inventory', 'compliance/inventory');
    helper('checklist');

    $inventory = $this->inventoryModel
      ->select('
            compliance_inventory.*,
            inventory_categories.name AS category_name,
            asset_item_types.name AS item_display_name,
            asset_item_types.checklist_frequency,
            areas.name AS area_name')
      ->join('inventory_categories', 'inventory_categories.id = compliance_inventory.category_id', 'left')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->join('areas', 'areas.id = compliance_inventory.area_id', 'left')
      ->where('compliance_inventory.id', $id)
      ->first();

    if (! $inventory) {
      throw new \CodeIgniter\Exceptions\PageNotFoundException('Inventory tidak ditemukan');
    }

    // =========================
    // BULAN AKTIF
    // =========================
    $ym = $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    [$year, $month] = explode('-', $ym);

    // =========================
    // AMBIL LOG BULAN AKTIF
    // =========================
    $logs = (new \App\Models\ChecklistLogModel())
      ->where('inventory_id', $id)
      ->like('period_key', $ym, 'after')
      ->findAll();

    // =========================
    // PERIOD GENERATION
    // =========================
    $periods = generate_calendar_periods(
      $inventory['checklist_frequency'],
      (int) $year,
      (int) $month
    );

    $rekap = [
      'total' => count($periods),
      'ok'    => 0,
      'not_ok' => 0,
      'late'  => 0,
    ];

    foreach ($logs as $log) {
      if ($log['status'] === 'ok') $rekap['ok']++;
      if ($log['status'] === 'not_ok') $rekap['not_ok']++;
    }

    foreach ($periods as $period) {

      $periodKey = is_array($period)
        ? ($period['key'] ?? $period['period_key'])
        : $period;

      $exists = false;

      foreach ($logs as $log) {
        if ($log['period_key'] === $periodKey) {
          $exists = true;
          break;
        }
      }

      if (! $exists && is_period_late(
        $inventory['checklist_frequency'],
        $periodKey
      )) {
        $rekap['late']++;
      }
    }

    // =========================
    // AMBIL SEMUA PERTANYAAN
    // =========================
    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', $inventory['item_type_id'])
      ->orderBy('id', 'ASC')
      ->findAll();

    // =========================
    // GRID GENERATOR
    // =========================
    $isToiletChecklist = ($inventory['item_type_id'] == 52);
    $dailyDays  = [];
    $dataGrid   = [];
    $weeklyGrid = [];

    $frequency = $inventory['checklist_frequency'];

    if ($frequency === 'daily') {

      $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);

      for ($d = 1; $d <= $daysInMonth; $d++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dailyDays[] = $date;
      }

      foreach ($logs as $log) {

        $qid  = $log['checklist_template_id'];
        $date = $log['period_key'];

        if ($isToiletChecklist) {

          $slot = $log['time_slot'] ?? null;

          if ($slot) {
            $dataGrid[$qid][$date][$slot] = $log['status'];
          }
        } else {

          $dataGrid[$qid][$date] = $log['status'];
        }
      }
    }

    if ($frequency === 'weekly') {

      foreach ($logs as $log) {

        if (preg_match('/W([1-4])$/', $log['period_key'], $m)) {
          $weekNumber = (int)$m[1];
          $weeklyGrid[$log['checklist_template_id']][$weekNumber] = $log['status'];
        }
      }
    }

    $checklistHistoryModel = new \App\Models\ChecklistLogModel();
    $checklists = $checklistHistoryModel
      ->select('
        period_key,
        MAX(check_date) as check_date,
        MAX(checked_by) as checked_by
    ')
      ->where('inventory_id', $id)
      ->groupBy('period_key')
      ->orderBy('check_date', 'DESC')
      ->paginate(10, 'checklist_history');


    // =========================
    // MONTHLY DETAIL
    // =========================
    $detailLogs = (new \App\Models\ChecklistLogModel())
      ->select('
            checklist_logs.*,
            checklist_master.question
        ')
      ->join(
        'checklist_master',
        'checklist_master.id = checklist_logs.checklist_template_id',
        'left'
      )
      ->where('checklist_logs.inventory_id', $id)
      ->where('checklist_logs.period_key', $ym)
      ->orderBy('checklist_logs.checklist_template_id', 'ASC')
      ->findAll();

    // Libur Nasional

    $holidayDates = [];

    if ($inventory['checklist_frequency'] === 'daily') {

      $holidayModel = new \App\Models\HolidayModel();

      $holidays = $holidayModel
        ->where('holiday_date >=', $ym . '-01')
        ->where('holiday_date <=', $ym . '-31')
        ->findAll();

      $holidayDates = array_column($holidays, 'holiday_date');
    }


    $nowYM = date('Y-m');


    $data = [
      'inventory'   => $inventory,
      'rekap'       => $rekap,
      'ym'          => $ym,
      'nowYM'       => $nowYM,
      'questions'   => $questions,
      'dailyDays'   => $dailyDays,
      'dataGrid'    => $dataGrid,
      'weeklyGrid'  => $weeklyGrid,
      'detailLogs'  => $detailLogs,
      'checklists' => $checklists,
      'checklistPager' => $checklistHistoryModel->pager,
      'holidayDates' => $holidayDates,
    ];

    if ($this->request->isAJAX()) {
      return view('compliance/inventory/_detail_month', $data);
    }

    return view('compliance/inventory/detail', $data);
  }

  public function updatePhoto($id)
  {
    $inventory = $this->inventoryModel->find($id);
    if (! $inventory) {

      // AJAX
      if ($this->request->isAJAX()) {
        return $this->response->setJSON([
          'status'  => 'error',
          'message' => 'Data tidak ditemukan'
        ])->setStatusCode(404);
      }

      // NON-AJAX
      return redirect()->back()->with('error', 'Data tidak ditemukan');
    }

    $photo = $this->request->getFile('photo');

    // ================= VALIDASI FILE =================
    if (! $photo || ! $photo->isValid()) {

      if ($this->request->isAJAX()) {
        return $this->response->setJSON([
          'status'  => 'error',
          'message' => 'File foto tidak valid'
        ])->setStatusCode(400);
      }

      return redirect()->back()->with('error', 'File foto tidak valid');
    }

    // ================= VALIDASI MIME =================
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    if (! in_array($photo->getMimeType(), $allowedMime)) {

      if ($this->request->isAJAX()) {
        return $this->response->setJSON([
          'status'  => 'error',
          'message' => 'Format foto tidak didukung'
        ])->setStatusCode(400);
      }

      return redirect()->back()->with('error', 'Format foto tidak didukung');
    }

    // ================= HAPUS FOTO LAMA =================
    if (! empty($inventory['photo'])) {
      $oldPath = FCPATH . 'uploads/inventory/' . $inventory['photo'];
      if (file_exists($oldPath)) {
        unlink($oldPath);
      }
    }

    // ================= SIMPAN FOTO BARU =================
    $newName = $photo->getRandomName();
    $photo->move(FCPATH . 'uploads/inventory', $newName);

    $this->inventoryModel->update($id, [
      'photo' => $newName
    ]);

    // ================= RESPONSE =================
    if ($this->request->isAJAX()) {
      return $this->response->setJSON([
        'status' => 'success',
        'photo'  => $newName,
        'url'    => base_url('uploads/inventory/' . $newName)
      ]);
    }

    return redirect()->back()->with('success', 'Foto inventory berhasil diperbarui');
  }


  public function getItemTypesByCategory($categoryId)
  {
    $model = new \App\Models\AssetItemTypeModel();

    $items = $model
      ->where('inventory_category_id', $categoryId)
      ->where('active', 1)
      ->orderBy('name', 'ASC')
      ->findAll();

    return $this->response->setJSON($items);
  }

  public function checklist($inventoryId)
  {
    page(
      'Ceklis',
      'compliance/inventory/detail/' . $inventoryId
    );
    helper('checklist');

    // ================= INVENTORY =================
    $inventory = $this->inventoryModel
      ->select('
      compliance_inventory.*,
      asset_item_types.name AS item_display_name,
      asset_item_types.checklist_frequency,
      asset_item_types.allow_na
    ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory) {
      throw new \CodeIgniter\Exceptions\PageNotFoundException();
    }

    if ((int) ($inventory['item_type_id'] ?? 0) === self::CCTV_ITEM_TYPE_ID) {
      $ym = $this->request->getGet('ym');
      if (! preg_match('/^\d{4}-\d{2}$/', (string) $ym)) {
        $ym = date('Y-m');
      }

      return redirect()->to('/compliance/checklist/cctv-grid?ym=' . rawurlencode($ym) . '&focus_id=' . (int) $inventoryId);
    }

    $frequency = $inventory['checklist_frequency'] ?? 'monthly';

    /* ================= SLOT CHECKLIST ================= */
    $isSlotChecklist = ($inventory['item_type_id'] == 52);
    $selectedSlot = $this->request->getGet('slot');

    $isLocked = false;
    $lockReason = null;

    if ($isSlotChecklist && empty($selectedSlot)) {
      $isLocked = true;
      $lockReason = 'slot';
    }

    $slots = null;

    if ($isSlotChecklist) {
      $slots = [
        'PG' => 'Pagi',
        'SI' => 'Siang',
        'SO' => 'Sore'
      ];
    }
    // ================= MONTH NAV =================
    $ym = $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    [$year, $month] = explode('-', $ym);

    $prevYM = date('Y-m', strtotime("$ym -1 month"));
    $nextYM = date('Y-m', strtotime("$ym +1 month"));

    $canPrev = true;
    $canNext = true;

    // ================= PERIOD KEY =================
    $periodKey = $this->request->getGet('period_key');

    // DEFAULT PER FREQUENCY
    if ($frequency === 'daily') {

      $today = date('Y-m-d');
      $monthStart = $ym . '-01';
      $monthEnd = date('Y-m-t', strtotime($monthStart));
      $holidayDates = holiday_dates_between($monthStart, $monthEnd);

      // ===============================
      // 1️⃣ Kalau buka bulan sekarang
      // ===============================
      if ($ym === date('Y-m')) {

        $candidate = $today;

        // Kalau hari ini libur → mundur cari hari kerja terakhir
        while (true) {
          if (!is_date_offday($candidate, $holidayDates)) {
            break;
          }

          $candidate = date('Y-m-d', strtotime($candidate . ' -1 day'));

          // Stop kalau keluar bulan
          if (substr($candidate, 0, 7) !== $ym) {
            break;
          }
        }

        $defaultPeriodKey = $candidate;
      } else {

        // ===============================
        // 2️⃣ Kalau buka bulan lain
        // ===============================
        $daysInMonth = date('t', strtotime($ym . '-01'));
        $firstValidDate = null;

        for ($d = 1; $d <= $daysInMonth; $d++) {

          $date = $ym . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);

          if (!is_date_offday($date, $holidayDates)) {
            $firstValidDate = $date;
            break;
          }
        }

        $defaultPeriodKey = $firstValidDate ?? ($ym . '-01');
      }
    } elseif ($frequency === 'weekly') {

      $defaultPeriodKey = $ym . '-W1';
    } else {

      $defaultPeriodKey = $ym;
    }

    // VALIDASI period_key
    if (! $periodKey) {
      $periodKey = $defaultPeriodKey;
    } else {
      if (
        ($frequency === 'daily'   && ! str_starts_with($periodKey, $ym . '-')) ||
        ($frequency === 'weekly'  && ! str_starts_with($periodKey, $ym . '-W')) ||
        ($frequency === 'monthly' && $periodKey !== $ym)
      ) {
        $periodKey = $defaultPeriodKey;
      }
    }


    $periodLabel = period_label($frequency, $periodKey);

    // ================= CALENDAR =================
    $periods = generate_calendar_periods($frequency, (int)$year, (int)$month);
    $calendarHolidayDates = [];
    if ($frequency === 'daily') {
      $calendarHolidayDates = holiday_dates_between($ym . '-01', date('Y-m-t', strtotime($ym . '-01')));
    }

    foreach ($periods as &$p) {

      $p['is_offday'] = false;

      // ================= DAILY ONLY =================
      if ($frequency === 'daily') {

        $date = $p['period_key'];
        $isOffday = is_date_offday($date, $calendarHolidayDates);

        $p['is_offday'] = $isOffday;

        if ($isOffday) {
          $p['allowed'] = false;
          $p['status']  = 'future';
          $p['is_active'] = ($p['period_key'] === $periodKey);
          continue; // STOP di sini untuk daily
        }
      }

      // ================= WEEKLY & MONTHLY =================
      $p['allowed'] = is_period_editable($frequency, $p['period_key']);

      $p['status'] = resolve_period_status(
        $inventory['id'],
        $frequency,
        $p['period_key']
      );

      $p['is_active'] = ($p['period_key'] === $periodKey);
    }

    unset($p);


    // ================= LOCK =================
    $logModel = new \App\Models\ChecklistLogModel();

    $exists = false;

    if (!$isSlotChecklist) {

      $exists = $logModel
        ->where('inventory_id', $inventoryId)
        ->where('period_key', $periodKey)
        ->first();
    } elseif ($selectedSlot) {

      $exists = $logModel
        ->where('inventory_id', $inventoryId)
        ->where('period_key', $periodKey)
        ->where('time_slot', $selectedSlot)
        ->first();
    }


    /* ================= OFFDAY KHUSUS DAILY ================= */
    if ($frequency === 'daily') {
      if (is_date_offday($periodKey, $calendarHolidayDates)) {
        $isLocked = true;
        $lockReason = 'offday';
      }
    }

    /* ================= DONE ================= */
    if (!$isLocked && $exists) {
      $isLocked = true;
      $lockReason = 'done';
    }

    /* ================= FUTURE ================= */
    if (!$isLocked && !$isSlotChecklist && is_period_future($frequency, $periodKey)) {
      $isLocked = true;
      $lockReason = 'future';
    }

    /* ================= EXPIRED ================= */
    if (!$isLocked && !$isSlotChecklist && ! is_period_editable($frequency, $periodKey)) {
      $isLocked = true;
      $lockReason = 'expired';
    }

    // ================= QUESTIONS =================
    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if ($this->request->isAJAX()) {

      return
        view('compliance/checklist/_calendar', [
          'inventory'   => $inventory,
          'periods'     => $periods,
          'frequency'   => $frequency,
          'navYM'       => $ym,
          'prevYM'      => $prevYM,
          'nextYM'      => $nextYM,
          'canPrev'     => $canPrev,
          'canNext'     => $canNext,
          'period_key'  => $periodKey,
        ])
        .
        view('compliance/checklist/_form', [
          'inventory'    => $inventory,
          'questions'    => $questions,
          'frequency'    => $frequency,
          'period_key'   => $periodKey,
          'period_label' => $periodLabel,
          'isLocked'     => $isLocked,
          'lockReason'   => $lockReason,
          'slots'        => $slots,
          'slot'         => $selectedSlot,
        ]);
    }
    // ================= FULL PAGE =================
    return view('compliance/checklist/index', [
      'inventory'    => $inventory,
      'questions'    => $questions,
      'frequency'    => $frequency,
      'period_key'   => $periodKey,
      'period_label' => $periodLabel,
      'isLocked'     => $isLocked,
      'lockReason'   => $lockReason,
      'periods'      => $periods,
      'navYM'        => $ym,
      'prevYM'       => $prevYM,
      'nextYM'       => $nextYM,
      'canPrev'      => $canPrev,
      'canNext'      => $canNext,
      'slots'        => $slots,
      'slot'         => $selectedSlot,
    ]);
  }

  public function submitChecklist()
  {
    if (! hasRole(['admin', 'compliance', 'staff'])) {
      return redirect()->to('/unauthorized');
    }

    helper('checklist');


    $inventoryId = $this->request->getPost('inventory_id');
    $periodKey   = $this->request->getPost('period_key');
    $timeSlot    = $this->request->getPost('time_slot');
    $itemTypeId  = $this->request->getPost('item_type_id');
    $questions   = $this->request->getPost('questions');
    $remarks     = $this->request->getPost('remarks') ?? [];
    $photos      = $this->request->getFiles()['photos'] ?? [];
    $user        = session()->get('name');

    if (! is_array($questions)) {
      return redirect()->back()
        ->with('error', 'Checklist tidak valid.');
    }

    // === AMBIL INVENTORY + ITEM FREQUENCY (AMAN) ===
    $inventory = $this->inventoryModel
      ->select('
    compliance_inventory.id,
    compliance_inventory.item_type_id,
    asset_item_types.checklist_frequency
  ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory) {
      return redirect()->back()->with('error', 'Inventory tidak ditemukan.');
    }

    $frequency = $inventory['checklist_frequency'] ?? 'monthly';

    /* ================= SLOT CHECKLIST ================= */
    $isSlotChecklist = ($inventory['item_type_id'] == 52);

    $logModel = new ChecklistLogModel();

    // === LOCK PER INVENTORY + PERIOD ===
    $existsQuery = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey);

    if ($isSlotChecklist && !empty($timeSlot)) {
      $existsQuery->where('time_slot', $timeSlot);
    }

    $exists = $existsQuery->first();

    if ($exists) {
      return redirect()->back()
        ->with('error', 'Checklist untuk periode ini sudah diisi.');
    }

    if ($isSlotChecklist && empty($timeSlot)) {
      return redirect()->back()
        ->with('error', 'Slot waktu harus dipilih.');
    }

    if ($frequency === 'daily') {
      $dayHolidayDates = holiday_dates_between($periodKey, $periodKey);
      if (is_date_offday($periodKey, $dayHolidayDates)) {
        return redirect()->back()
          ->with('error', 'Checklist tidak dapat diisi pada hari libur.');
      }
    }

    foreach ($questions as $templateId => $status) {

      // 🔥 MAPPING STATUS
      $statusDb = match ($status) {
        'ok' => 'ok',
        'not_ok' => 'not_ok',
        'ng' => 'not_ok',
        'na' => 'na',
        default => 'na'
      };

      $remarkValue = trim($remarks[$templateId] ?? '');
      $hasPhoto = isset($photos[$templateId]) && $photos[$templateId]->isValid();

      if (in_array($status, ['not_ok', 'ng'], true) && $remarkValue === '' && ! $hasPhoto) {
        return redirect()->back()
          ->with('error', 'Checklist NOT OK wajib memiliki catatan atau foto.');
      }

      $photoName = null;
      if ($hasPhoto) {
        $photoName = $photos[$templateId]->getRandomName();
        $photos[$templateId]->move(FCPATH . 'uploads/checklist', $photoName);
      }

      $logModel->insert([
        'inventory_id'          => $inventoryId,
        'item_type_id'          => $itemTypeId,
        'checklist_template_id' => $templateId,
        'check_date'            => date('Y-m-d'),
        'period_key'            => $periodKey,
        'time_slot'             => $isSlotChecklist ? $timeSlot : null,
        'status'                => $statusDb,
        'remark'                => $remarkValue ?: null,
        'photo'                 => $photoName,
        'checked_by'            => $user,
        'created_at'            => date('Y-m-d H:i:s')
      ]);
    }


    return redirect()
      ->to(base_url('compliance/inventory/detail/' . $inventoryId))
      ->with('success', 'Checklist berhasil disimpan.');
  }

  public function cctvGrid()
  {
    if (! hasRole(['admin', 'compliance', 'staff', 'auditor'])) {
      return redirect()->to('/unauthorized');
    }

    helper('checklist');
    page('Checklist CCTV');

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    [$year, $month] = explode('-', $ym);
    $monthStart = $ym . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $focusId = (int) ($this->request->getGet('focus_id') ?: 0);

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::CCTV_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->first();

    if (! $question) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist CCTV belum tersedia.');
    }

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, compliance_inventory.remark, compliance_inventory.status, asset_item_types.name AS item_display_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', self::CCTV_ITEM_TYPE_ID)
      ->orderBy('compliance_inventory.asset_code', 'ASC')
      ->findAll();

    $inventoryIds = array_column($inventories, 'id');
    $logs = [];
    if (! empty($inventoryIds)) {
      $logs = (new ChecklistLogModel())
        ->select('id, inventory_id, period_key, status, checked_by, check_date')
        ->whereIn('inventory_id', $inventoryIds)
        ->where('item_type_id', self::CCTV_ITEM_TYPE_ID)
        ->like('period_key', $ym . '-', 'after')
        ->findAll();
    }

    $logMap = [];
    foreach ($logs as $log) {
      $logMap[(int) $log['inventory_id']][$log['period_key']] = $log;
    }

    $holidayDates = holiday_dates_between($monthStart, $monthEnd);
    $days = generate_calendar_periods('daily', (int) $year, (int) $month);

    $rows = [];
    foreach ($inventories as $index => $inventory) {
      $rows[] = [
        'id' => (int) $inventory['id'],
        'no' => $index + 1,
        'display_name' => $this->buildCctvDisplayName($inventory),
        'location' => trim((string) ($inventory['specific_area'] ?? '-')),
        'asset_code' => (string) ($inventory['asset_code'] ?? ''),
        'detail_url' => '/compliance/inventory/detail/' . (int) $inventory['id'] . '?ym=' . rawurlencode($ym),
        'checks' => $logMap[(int) $inventory['id']] ?? [],
      ];
    }

    return view('compliance/checklist/cctv_grid', [
      'title' => 'Checklist CCTV',
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($monthStart)),
      'question' => $question,
      'days' => $days,
      'rows' => $rows,
      'holidayDates' => $holidayDates,
      'focusId' => $focusId,
      'saveUrl' => '/compliance/checklist/cctv-grid/save',
      'bulkUrl' => '/compliance/checklist/cctv-grid/mark-all',
      'currentUser' => (string) session()->get('name'),
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function saveCctvGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/cctv-grid');
    }

    if (! hasRole(['admin', 'compliance', 'staff'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    helper('checklist');

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = (string) $this->request->getPost('period_key');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist CCTV tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist CCTV tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel
      ->select('id, item_type_id')
      ->where('id', $inventoryId)
      ->first();

    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::CCTV_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory CCTV tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $holidayDates = holiday_dates_between($periodKey, $periodKey);
    if (is_date_offday($periodKey, $holidayDates)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Checklist tidak dapat diisi pada hari libur.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::CCTV_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist CCTV belum tersedia.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', (int) $question['id'])
      ->first();

    if ($mode === 'clear') {
      if ($existing && in_array(($existing['status'] ?? ''), ['ok', 'not_ok'], true)) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist CCTV dikosongkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      if (($existing['status'] ?? '') === 'na') {
        return $this->response->setStatusCode(409)->setJSON([
          'ok' => false,
          'message' => 'Status N/A tetap perlu dikelola dari halaman detail item.',
          'state' => strtolower((string) ($existing['status'] ?? 'empty')),
          'detailUrl' => '/compliance/inventory/detail/' . $inventoryId . '?ym=' . substr($periodKey, 0, 7),
          'csrfHash' => csrf_hash(),
        ]);
      }

      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => $periodKey,
      ]);

      return $this->response->setJSON([
        'ok' => true,
        'state' => $mode,
        'message' => 'Checklist CCTV diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel->insert([
      'inventory_id' => $inventoryId,
      'item_type_id' => self::CCTV_ITEM_TYPE_ID,
      'checklist_template_id' => (int) $question['id'],
      'check_date' => $periodKey,
      'period_key' => $periodKey,
      'time_slot' => null,
      'status' => $mode,
      'remark' => null,
      'photo' => null,
      'checked_by' => session()->get('name'),
      'created_at' => date('Y-m-d H:i:s'),
    ]);

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist CCTV tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllCctvGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/cctv-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    helper('checklist');

    $ym = trim((string) $this->request->getPost('ym'));
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode CCTV tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::CCTV_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist CCTV belum tersedia.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventories = $this->inventoryModel
      ->select('id')
      ->where('item_type_id', self::CCTV_ITEM_TYPE_ID)
      ->findAll();

    $inventoryIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $inventories)));
    if (empty($inventoryIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada inventory CCTV untuk diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    [$year, $month] = explode('-', $ym);
    $monthStart = $ym . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $holidayDates = holiday_dates_between($monthStart, $monthEnd);
    $days = generate_calendar_periods('daily', (int) $year, (int) $month);

    $validPeriodKeys = [];
    foreach ($days as $day) {
      $periodKey = (string) ($day['period_key'] ?? '');
      if ($periodKey === '' || is_date_offday($periodKey, $holidayDates)) {
        continue;
      }

      $validPeriodKeys[] = $periodKey;
    }

    if (empty($validPeriodKeys)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada hari kerja yang bisa dicentang.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questionId = (int) ($question['id'] ?? 0);
    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->whereIn('inventory_id', $inventoryIds)
      ->where('checklist_template_id', $questionId)
      ->where('item_type_id', self::CCTV_ITEM_TYPE_ID)
      ->whereIn('period_key', $validPeriodKeys)
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['inventory_id']][(string) $log['period_key']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkedBy = session()->get('name');
    $inserted = 0;

    foreach ($inventoryIds as $inventoryId) {
      foreach ($validPeriodKeys as $periodKey) {
        if (isset($existingMap[$inventoryId][$periodKey])) {
          continue;
        }

        $logModel->insert([
          'inventory_id' => $inventoryId,
          'item_type_id' => self::CCTV_ITEM_TYPE_ID,
          'checklist_template_id' => $questionId,
          'check_date' => $periodKey,
          'period_key' => $periodKey,
          'time_slot' => null,
          'status' => 'ok',
          'remark' => null,
          'photo' => null,
          'checked_by' => $checkedBy,
          'created_at' => $now,
        ]);
        $inserted++;
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'inserted' => $inserted,
      'message' => 'Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function emergencyLightGrid()
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    page('Checklist Emergency Light');

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    [$year, $month] = explode('-', $ym);
    $focusId = (int) ($this->request->getGet('focus_id') ?: 0);

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::EMERGENCY_LIGHT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist Emergency Light belum tersedia.');
    }

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, compliance_inventory.type_description, compliance_inventory.status, asset_item_types.name AS item_display_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', self::EMERGENCY_LIGHT_ITEM_TYPE_ID)
      ->orderBy('compliance_inventory.asset_code', 'ASC')
      ->findAll();

    $questionColumns = $this->resolveEmergencyLightGridColumns($questions);
    $inventoryIds = array_column($inventories, 'id');
    $logMap = [];

    if (! empty($inventoryIds)) {
      $logs = (new ChecklistLogModel())
        ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
        ->whereIn('inventory_id', $inventoryIds)
        ->where('item_type_id', self::EMERGENCY_LIGHT_ITEM_TYPE_ID)
        ->where('period_key', $ym)
        ->findAll();

      foreach ($logs as $log) {
        $logMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
      }
    }

    $rows = [];
    foreach ($inventories as $index => $inventory) {
      $inventoryId = (int) ($inventory['id'] ?? 0);
      $rows[] = [
        'id' => $inventoryId,
        'no' => $index + 1,
        'location' => trim((string) ($inventory['specific_area'] ?? '')) !== '' ? (string) $inventory['specific_area'] : '-',
        'type_description' => trim((string) ($inventory['type_description'] ?? '')) !== '' ? (string) $inventory['type_description'] : '-',
        'checks' => $logMap[$inventoryId] ?? [],
        'detail_url' => '/compliance/checklist/' . $inventoryId . '?ym=' . rawurlencode($ym),
      ];
    }

    return view('compliance/checklist/emergency_light_grid', [
      'title' => 'Checklist Emergency Light',
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'rows' => $rows,
      'groupedColumns' => $questionColumns,
      'saveUrl' => '/compliance/checklist/emergency-light-grid/save',
      'bulkUrl' => '/compliance/checklist/emergency-light-grid/mark-all',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
      'focusId' => $focusId,
      'currentUser' => trim((string) session()->get('name')),
    ]);
  }

  public function saveEmergencyLightGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/emergency-light-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist Emergency Light tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'na', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel
      ->where('id', $inventoryId)
      ->first();

    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::EMERGENCY_LIGHT_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory Emergency Light tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::EMERGENCY_LIGHT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist Emergency Light dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::EMERGENCY_LIGHT_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist Emergency Light tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllEmergencyLightGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/emergency-light-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $periodKey = trim((string) $this->request->getPost('period_key'));
    if (! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode Emergency Light tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    return $this->bulkMarkEmergencyLampGrid(self::EMERGENCY_LIGHT_ITEM_TYPE_ID, $periodKey, 'Emergency Light');
  }

  public function emergencyExitLightGrid()
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    page('Checklist Emergency Exit Light');

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    $focusId = (int) ($this->request->getGet('focus_id') ?: 0);

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::EMERGENCY_EXIT_LIGHT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist Emergency Exit Light belum tersedia.');
    }

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, compliance_inventory.type_description, compliance_inventory.status, asset_item_types.name AS item_display_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', self::EMERGENCY_EXIT_LIGHT_ITEM_TYPE_ID)
      ->orderBy('compliance_inventory.asset_code', 'ASC')
      ->findAll();

    $questionColumns = $this->resolveEmergencyExitLightGridColumns($questions);
    $inventoryIds = array_column($inventories, 'id');
    $logMap = [];

    if (! empty($inventoryIds)) {
      $logs = (new ChecklistLogModel())
        ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
        ->whereIn('inventory_id', $inventoryIds)
        ->where('item_type_id', self::EMERGENCY_EXIT_LIGHT_ITEM_TYPE_ID)
        ->where('period_key', $ym)
        ->findAll();

      foreach ($logs as $log) {
        $logMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
      }
    }

    $rows = [];
    foreach ($inventories as $index => $inventory) {
      $inventoryId = (int) ($inventory['id'] ?? 0);
      $rows[] = [
        'id' => $inventoryId,
        'no' => $index + 1,
        'location' => trim((string) ($inventory['specific_area'] ?? '')) !== '' ? (string) $inventory['specific_area'] : '-',
        'type_description' => trim((string) ($inventory['type_description'] ?? '')) !== '' ? (string) $inventory['type_description'] : '-',
        'checks' => $logMap[$inventoryId] ?? [],
        'detail_url' => '/compliance/checklist/' . $inventoryId . '?ym=' . rawurlencode($ym),
      ];
    }

    return view('compliance/checklist/emergency_exit_light_grid', [
      'title' => 'Checklist Emergency Exit Light',
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'rows' => $rows,
      'groupedColumns' => $questionColumns,
      'saveUrl' => '/compliance/checklist/emergency-exit-light-grid/save',
      'bulkUrl' => '/compliance/checklist/emergency-exit-light-grid/mark-all',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
      'focusId' => $focusId,
      'currentUser' => trim((string) session()->get('name')),
    ]);
  }

  public function saveEmergencyExitLightGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/emergency-exit-light-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist Emergency Exit Light tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'na', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel
      ->where('id', $inventoryId)
      ->first();

    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::EMERGENCY_EXIT_LIGHT_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory Emergency Exit Light tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::EMERGENCY_EXIT_LIGHT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist Emergency Exit Light dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::EMERGENCY_EXIT_LIGHT_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist Emergency Exit Light tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllEmergencyExitLightGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/emergency-exit-light-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $periodKey = trim((string) $this->request->getPost('period_key'));
    if (! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode Emergency Exit Light tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    return $this->bulkMarkEmergencyLampGrid(self::EMERGENCY_EXIT_LIGHT_ITEM_TYPE_ID, $periodKey, 'Emergency Exit Light');
  }

  public function firstAidBoxGrid()
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    page('Checklist First Aid Box');

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    $focusId = (int) ($this->request->getGet('focus_id') ?: 0);

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::FIRST_AID_BOX_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist First Aid Box belum tersedia.');
    }

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, compliance_inventory.type_description, compliance_inventory.status, asset_item_types.name AS item_display_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', self::FIRST_AID_BOX_ITEM_TYPE_ID)
      ->orderBy('compliance_inventory.asset_code', 'ASC')
      ->findAll();

    $inventoryIds = array_column($inventories, 'id');
    $logMap = [];

    if (! empty($inventoryIds)) {
      $logs = (new ChecklistLogModel())
        ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
        ->whereIn('inventory_id', $inventoryIds)
        ->where('item_type_id', self::FIRST_AID_BOX_ITEM_TYPE_ID)
        ->where('period_key', $ym)
        ->findAll();

      foreach ($logs as $log) {
        $logMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
      }
    }

    $rows = [];
    foreach ($inventories as $index => $inventory) {
      $inventoryId = (int) ($inventory['id'] ?? 0);
      $rows[] = [
        'id' => $inventoryId,
        'no' => $index + 1,
        'asset_code' => trim((string) ($inventory['asset_code'] ?? '')) !== '' ? (string) $inventory['asset_code'] : '-',
        'location' => trim((string) ($inventory['specific_area'] ?? '')) !== '' ? (string) $inventory['specific_area'] : '-',
        'type_description' => trim((string) ($inventory['type_description'] ?? '')) !== '' ? (string) $inventory['type_description'] : '-',
        'checks' => $logMap[$inventoryId] ?? [],
        'detail_url' => '/compliance/checklist/' . $inventoryId . '?ym=' . rawurlencode($ym),
      ];
    }

    return view('compliance/checklist/first_aid_box_grid', [
      'title' => 'Checklist First Aid Box',
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'rows' => $rows,
      'questions' => $questions,
      'saveUrl' => '/compliance/checklist/first-aid-box-grid/save',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
      'focusId' => $focusId,
    ]);
  }

  public function saveFirstAidBoxGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/first-aid-box-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist First Aid Box tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel
      ->where('id', $inventoryId)
      ->first();

    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::FIRST_AID_BOX_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory First Aid Box tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::FIRST_AID_BOX_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist First Aid Box dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::FIRST_AID_BOX_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist First Aid Box tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllFirstAidBoxGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/first-aid-box-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $periodKey = trim((string) $this->request->getPost('period_key'));
    if (! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode First Aid Box tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::FIRST_AID_BOX_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $inventories = $this->inventoryModel
      ->select('id')
      ->where('item_type_id', self::FIRST_AID_BOX_ITEM_TYPE_ID)
      ->findAll();

    $inventoryIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $inventories)));
    $questionIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $questions)));

    if (empty($inventoryIds) || empty($questionIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada data First Aid Box untuk diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->whereIn('inventory_id', $inventoryIds)
      ->whereIn('checklist_template_id', $questionIds)
      ->where('item_type_id', self::FIRST_AID_BOX_ITEM_TYPE_ID)
      ->where('period_key', $periodKey)
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkDate = date('Y-m-d');
    $checkedBy = session()->get('name');
    $inserted = 0;

    foreach ($inventoryIds as $inventoryId) {
      foreach ($questionIds as $questionId) {
        if (isset($existingMap[$inventoryId][$questionId])) {
          continue;
        }

        $logModel->insert([
          'inventory_id' => $inventoryId,
          'item_type_id' => self::FIRST_AID_BOX_ITEM_TYPE_ID,
          'checklist_template_id' => $questionId,
          'check_date' => $checkDate,
          'period_key' => $periodKey,
          'time_slot' => null,
          'status' => 'ok',
          'remark' => null,
          'photo' => null,
          'checked_by' => $checkedBy,
          'created_at' => $now,
        ]);
        $inserted++;
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'inserted' => $inserted,
      'message' => 'Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function firstAidContentGrid($inventoryId)
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    helper('checklist');
    page('Checklist First Aid Kit Content');

    $inventoryId = (int) $inventoryId;
    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    [$year, $month] = explode('-', $ym);
    $monthStart = $ym . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    $inventory = $this->inventoryModel
      ->select('compliance_inventory.*, asset_item_types.name AS item_display_name, asset_item_types.checklist_frequency, asset_item_types.allow_na')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::FIRST_AID_CONTENT_ITEM_TYPE_ID) {
      return redirect()->to('/compliance/inventory')->with('error', 'Inventory First Aid Kit Content tidak ditemukan.');
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::FIRST_AID_CONTENT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory/detail/' . $inventoryId)->with('error', 'Pertanyaan checklist First Aid Kit Content belum tersedia.');
    }

    $logs = (new ChecklistLogModel())
      ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
      ->where('inventory_id', $inventoryId)
      ->where('item_type_id', self::FIRST_AID_CONTENT_ITEM_TYPE_ID)
      ->like('period_key', $ym . '-', 'after')
      ->findAll();

    $logMap = [];
    foreach ($logs as $log) {
      $logMap[(int) $log['checklist_template_id']][(string) $log['period_key']] = $log;
    }

    $holidayDates = holiday_dates_between($monthStart, $monthEnd);
    $days = generate_calendar_periods('daily', (int) $year, (int) $month);
    foreach ($days as &$day) {
      $day['is_offday'] = is_date_offday((string) ($day['period_key'] ?? ''), $holidayDates);
    }
    unset($day);

    return view('compliance/checklist/first_aid_content_grid', [
      'title' => 'Checklist First Aid Kit Content',
      'inventory' => $inventory,
      'questions' => $questions,
      'days' => $days,
      'logMap' => $logMap,
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'saveUrl' => '/compliance/checklist/first-aid-content-grid/save',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function saveFirstAidContentGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/inventory');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    helper('checklist');

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist First Aid Kit Content tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel->where('id', $inventoryId)->first();
    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::FIRST_AID_CONTENT_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory First Aid Kit Content tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $holidayDates = holiday_dates_between($periodKey, $periodKey);
    if (is_date_offday($periodKey, $holidayDates)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Checklist tidak dapat diisi pada hari libur.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::FIRST_AID_CONTENT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist First Aid Kit Content dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::FIRST_AID_CONTENT_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist First Aid Kit Content tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllFirstAidContentGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/inventory');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    helper('checklist');

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $ym = trim((string) $this->request->getPost('ym'));

    if ($inventoryId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode First Aid Kit Content tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel->where('id', $inventoryId)->first();
    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::FIRST_AID_CONTENT_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory First Aid Kit Content tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::FIRST_AID_CONTENT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    [$year, $month] = explode('-', $ym);
    $monthStart = $ym . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $holidayDates = holiday_dates_between($monthStart, $monthEnd);
    $days = generate_calendar_periods('daily', (int) $year, (int) $month);

    $validPeriodKeys = [];
    foreach ($days as $day) {
      $periodKey = (string) ($day['period_key'] ?? '');
      if ($periodKey === '' || is_date_offday($periodKey, $holidayDates)) {
        continue;
      }
      $validPeriodKeys[] = $periodKey;
    }

    if (empty($validPeriodKeys)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada hari kerja yang bisa dicentang.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questionIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $questions)));

    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->where('inventory_id', $inventoryId)
      ->whereIn('checklist_template_id', $questionIds)
      ->where('item_type_id', self::FIRST_AID_CONTENT_ITEM_TYPE_ID)
      ->whereIn('period_key', $validPeriodKeys)
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['checklist_template_id']][(string) $log['period_key']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $inserted = 0;

    foreach ($questionIds as $questionId) {
      foreach ($validPeriodKeys as $periodKey) {
        if (isset($existingMap[$questionId][$periodKey])) {
          continue;
        }

        $logModel->insert([
          'inventory_id' => $inventoryId,
          'item_type_id' => self::FIRST_AID_CONTENT_ITEM_TYPE_ID,
          'checklist_template_id' => $questionId,
          'check_date' => $periodKey,
          'period_key' => $periodKey,
          'time_slot' => null,
          'status' => 'ok',
          'remark' => null,
          'photo' => null,
          'checked_by' => session()->get('name'),
          'created_at' => $now,
        ]);
        $inserted++;
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'inserted' => $inserted,
      'message' => 'Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function fireExtinguisherGrid()
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    page('Checklist Fire Extinguisher');

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    $focusId = (int) ($this->request->getGet('focus_id') ?: 0);

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::FIRE_EXTINGUISHER_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist Fire Extinguisher belum tersedia.');
    }

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, compliance_inventory.type_description, compliance_inventory.status, compliance_inventory.expired_date, asset_item_types.name AS item_display_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', self::FIRE_EXTINGUISHER_ITEM_TYPE_ID)
      ->orderBy('compliance_inventory.asset_code', 'ASC')
      ->findAll();

    $groupedColumns = $this->resolveFireExtinguisherGridColumns($questions);
    $inventoryIds = array_column($inventories, 'id');
    $logMap = [];

    if (! empty($inventoryIds)) {
      $logs = (new ChecklistLogModel())
        ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
        ->whereIn('inventory_id', $inventoryIds)
        ->where('item_type_id', self::FIRE_EXTINGUISHER_ITEM_TYPE_ID)
        ->where('period_key', $ym)
        ->findAll();

      foreach ($logs as $log) {
        $logMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
      }
    }

    $rows = [];
    foreach ($inventories as $index => $inventory) {
      $inventoryId = (int) ($inventory['id'] ?? 0);
      $rows[] = [
        'id' => $inventoryId,
        'no' => $index + 1,
        'asset_code' => trim((string) ($inventory['asset_code'] ?? '')) !== '' ? (string) $inventory['asset_code'] : '-',
        'location' => trim((string) ($inventory['specific_area'] ?? '')) !== '' ? (string) $inventory['specific_area'] : '-',
        'type_description' => trim((string) ($inventory['type_description'] ?? '')) !== '' ? (string) $inventory['type_description'] : '-',
        'expired_date' => trim((string) ($inventory['expired_date'] ?? '')),
        'checks' => $logMap[$inventoryId] ?? [],
        'detail_url' => '/compliance/checklist/' . $inventoryId . '?ym=' . rawurlencode($ym),
      ];
    }

    return view('compliance/checklist/fire_extinguisher_grid', [
      'title' => 'Checklist Fire Extinguisher',
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'rows' => $rows,
      'groupedColumns' => $groupedColumns,
      'saveUrl' => '/compliance/checklist/fire-extinguisher-grid/save',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
      'focusId' => $focusId,
    ]);
  }

  public function saveFireExtinguisherGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/fire-extinguisher-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist Fire Extinguisher tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel->where('id', $inventoryId)->first();
    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::FIRE_EXTINGUISHER_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory Fire Extinguisher tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::FIRE_EXTINGUISHER_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist Fire Extinguisher dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::FIRE_EXTINGUISHER_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist Fire Extinguisher tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllFireExtinguisherGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/fire-extinguisher-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $periodKey = trim((string) $this->request->getPost('period_key'));
    if (! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode Fire Extinguisher tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::FIRE_EXTINGUISHER_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $inventories = $this->inventoryModel
      ->select('id')
      ->where('item_type_id', self::FIRE_EXTINGUISHER_ITEM_TYPE_ID)
      ->findAll();

    $inventoryIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $inventories)));
    $questionIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $questions)));

    if (empty($inventoryIds) || empty($questionIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada data Fire Extinguisher untuk diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->whereIn('inventory_id', $inventoryIds)
      ->whereIn('checklist_template_id', $questionIds)
      ->where('item_type_id', self::FIRE_EXTINGUISHER_ITEM_TYPE_ID)
      ->where('period_key', $periodKey)
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkDate = date('Y-m-d');
    $checkedBy = session()->get('name');
    $inserted = 0;

    foreach ($inventoryIds as $inventoryId) {
      foreach ($questionIds as $questionId) {
        if (isset($existingMap[$inventoryId][$questionId])) {
          continue;
        }

        $logModel->insert([
          'inventory_id' => $inventoryId,
          'item_type_id' => self::FIRE_EXTINGUISHER_ITEM_TYPE_ID,
          'checklist_template_id' => $questionId,
          'check_date' => $checkDate,
          'period_key' => $periodKey,
          'time_slot' => null,
          'status' => 'ok',
          'remark' => null,
          'photo' => null,
          'checked_by' => $checkedBy,
          'created_at' => $now,
        ]);
        $inserted++;
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'inserted' => $inserted,
      'message' => 'Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function intrusionAlarmGrid()
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    page('Checklist Intrusion Alarm');

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    $focusId = (int) ($this->request->getGet('focus_id') ?: 0);

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::INTRUSION_ALARM_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist Intrusion Alarm belum tersedia.');
    }

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, compliance_inventory.type_description, compliance_inventory.status, asset_item_types.name AS item_display_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', self::INTRUSION_ALARM_ITEM_TYPE_ID)
      ->orderBy('compliance_inventory.asset_code', 'ASC')
      ->findAll();

    $groupedColumns = $this->resolveWeeklyAlarmGridColumns($questions);
    $inventoryIds = array_column($inventories, 'id');
    $logMap = [];

    if (! empty($inventoryIds)) {
      $logs = (new ChecklistLogModel())
        ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
        ->whereIn('inventory_id', $inventoryIds)
        ->where('item_type_id', self::INTRUSION_ALARM_ITEM_TYPE_ID)
        ->like('period_key', $ym . '-W', 'after')
        ->findAll();

      foreach ($logs as $log) {
        if (! preg_match('/W([1-4])$/', (string) ($log['period_key'] ?? ''), $matches)) {
          continue;
        }

        $weekNumber = (int) $matches[1];
        $logMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']][$weekNumber] = $log;
      }
    }

    $rows = [];
    foreach ($inventories as $index => $inventory) {
      $inventoryId = (int) ($inventory['id'] ?? 0);
      $rows[] = [
        'id' => $inventoryId,
        'no' => $index + 1,
        'location' => trim((string) ($inventory['specific_area'] ?? '')) !== '' ? (string) $inventory['specific_area'] : '-',
        'checks' => $logMap[$inventoryId] ?? [],
        'detail_url' => '/compliance/checklist/' . $inventoryId . '?ym=' . rawurlencode($ym),
      ];
    }

    return view('compliance/checklist/intrusion_alarm_grid', [
      'title' => 'Checklist Intrusion Alarm',
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'rows' => $rows,
      'groupedColumns' => $groupedColumns,
      'saveUrl' => '/compliance/checklist/intrusion-alarm-grid/save',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
      'focusId' => $focusId,
    ]);
  }

  public function saveIntrusionAlarmGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/intrusion-alarm-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}-W[1-4]$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist Intrusion Alarm tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel->where('id', $inventoryId)->first();
    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::INTRUSION_ALARM_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory Intrusion Alarm tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::INTRUSION_ALARM_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist Intrusion Alarm dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::INTRUSION_ALARM_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist Intrusion Alarm tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllIntrusionAlarmGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/intrusion-alarm-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    helper('checklist');

    $ym = trim((string) $this->request->getPost('ym'));
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode Intrusion Alarm tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::INTRUSION_ALARM_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $inventories = $this->inventoryModel
      ->select('id')
      ->where('item_type_id', self::INTRUSION_ALARM_ITEM_TYPE_ID)
      ->findAll();

    $inventoryIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $inventories)));
    $questionIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $questions)));

    if (empty($inventoryIds) || empty($questionIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada data Intrusion Alarm untuk diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $periods = [];
    for ($week = 1; $week <= 4; $week++) {
      $periodKey = sprintf('%s-W%d', $ym, $week);
      if (is_period_future('weekly', $periodKey) || ! is_period_editable('weekly', $periodKey)) {
        continue;
      }
      $periods[] = $periodKey;
    }

    if (empty($periods)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada periode Intrusion Alarm yang bisa dicentang.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->whereIn('inventory_id', $inventoryIds)
      ->whereIn('checklist_template_id', $questionIds)
      ->where('item_type_id', self::INTRUSION_ALARM_ITEM_TYPE_ID)
      ->like('period_key', $ym . '-W', 'after')
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']][(string) $log['period_key']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkedBy = session()->get('name');
    $inserted = 0;

    foreach ($inventoryIds as $inventoryId) {
      foreach ($questionIds as $questionId) {
        foreach ($periods as $periodKey) {
          if (isset($existingMap[$inventoryId][$questionId][$periodKey])) {
            continue;
          }

          $logModel->insert([
            'inventory_id' => $inventoryId,
            'item_type_id' => self::INTRUSION_ALARM_ITEM_TYPE_ID,
            'checklist_template_id' => $questionId,
            'check_date' => preg_replace('/-W[1-4]$/', '-01', $periodKey),
            'period_key' => $periodKey,
            'time_slot' => null,
            'status' => 'ok',
            'remark' => null,
            'photo' => null,
            'checked_by' => $checkedBy,
            'created_at' => $now,
          ]);
          $inserted++;
        }
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'inserted' => $inserted,
      'message' => 'Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function hydrantGrid()
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    page('Checklist Hydrant');

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::HYDRANT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist Hydrant belum tersedia.');
    }

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, compliance_inventory.type_description, compliance_inventory.status, asset_item_types.name AS item_display_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', self::HYDRANT_ITEM_TYPE_ID)
      ->orderBy('compliance_inventory.asset_code', 'ASC')
      ->findAll();

    $logMap = [];
    $inventoryIds = array_column($inventories, 'id');
    if (! empty($inventoryIds)) {
      $logs = (new ChecklistLogModel())
        ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
        ->whereIn('inventory_id', $inventoryIds)
        ->where('item_type_id', self::HYDRANT_ITEM_TYPE_ID)
        ->like('period_key', $ym . '-W', 'after')
        ->findAll();

      foreach ($logs as $log) {
        if (! preg_match('/W([1-4])$/', (string) ($log['period_key'] ?? ''), $matches)) {
          continue;
        }

        $weekNumber = (int) $matches[1];
        $logMap[(int) $log['checklist_template_id']][(int) $log['inventory_id']][$weekNumber] = $log;
      }
    }

    $hydrants = [];
    foreach ($inventories as $inventory) {
      $hydrants[] = [
        'id' => (int) ($inventory['id'] ?? 0),
        'label' => $this->resolveHydrantLabel($inventory),
        'location' => trim((string) ($inventory['specific_area'] ?? '')) !== '' ? (string) $inventory['specific_area'] : '-',
        'detail_url' => '/compliance/checklist/' . (int) ($inventory['id'] ?? 0) . '?ym=' . rawurlencode($ym),
      ];
    }

    return view('compliance/checklist/hydrant_grid', [
      'title' => 'Checklist Hydrant',
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'questions' => $questions,
      'hydrants' => $hydrants,
      'logMap' => $logMap,
      'saveUrl' => '/compliance/checklist/hydrant-grid/save',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function saveHydrantGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/hydrant-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}-W[1-4]$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist Hydrant tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel->where('id', $inventoryId)->first();
    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::HYDRANT_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory Hydrant tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::HYDRANT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist Hydrant dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::HYDRANT_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist Hydrant tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllHydrantGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/hydrant-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    helper('checklist');

    $ym = trim((string) $this->request->getPost('ym'));
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode Hydrant tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::HYDRANT_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $inventories = $this->inventoryModel
      ->select('id')
      ->where('item_type_id', self::HYDRANT_ITEM_TYPE_ID)
      ->findAll();

    $inventoryIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $inventories)));
    $questionIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $questions)));

    if (empty($inventoryIds) || empty($questionIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada data Hydrant untuk diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $periods = [];
    for ($week = 1; $week <= 4; $week++) {
      $periodKey = sprintf('%s-W%d', $ym, $week);
      if (is_period_future('weekly', $periodKey) || ! is_period_editable('weekly', $periodKey)) {
        continue;
      }
      $periods[] = $periodKey;
    }

    if (empty($periods)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada periode Hydrant yang bisa dicentang.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->whereIn('inventory_id', $inventoryIds)
      ->whereIn('checklist_template_id', $questionIds)
      ->where('item_type_id', self::HYDRANT_ITEM_TYPE_ID)
      ->like('period_key', $ym . '-W', 'after')
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']][(string) $log['period_key']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkedBy = session()->get('name');
    $inserted = 0;

    foreach ($inventoryIds as $inventoryId) {
      foreach ($questionIds as $questionId) {
        foreach ($periods as $periodKey) {
          if (isset($existingMap[$inventoryId][$questionId][$periodKey])) {
            continue;
          }

          $logModel->insert([
            'inventory_id' => $inventoryId,
            'item_type_id' => self::HYDRANT_ITEM_TYPE_ID,
            'checklist_template_id' => $questionId,
            'check_date' => preg_replace('/-W[1-4]$/', '-01', $periodKey),
            'period_key' => $periodKey,
            'time_slot' => null,
            'status' => 'ok',
            'remark' => null,
            'photo' => null,
            'checked_by' => $checkedBy,
            'created_at' => $now,
          ]);
          $inserted++;
        }
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'inserted' => $inserted,
      'message' => 'Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function smokeDetectorGrid()
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    page('Checklist Smoke Detector');

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    $focusId = (int) ($this->request->getGet('focus_id') ?: 0);

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::SMOKE_DETECTOR_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist Smoke Detector belum tersedia.');
    }

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, asset_item_types.name AS item_display_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', self::SMOKE_DETECTOR_ITEM_TYPE_ID)
      ->orderBy('TRIM(compliance_inventory.specific_area)', 'ASC', false)
      ->orderBy('compliance_inventory.asset_code', 'ASC')
      ->findAll();

    $inventoryIds = array_column($inventories, 'id');
    $logMap = [];

    if (! empty($inventoryIds)) {
      $logs = (new ChecklistLogModel())
        ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
        ->whereIn('inventory_id', $inventoryIds)
        ->where('item_type_id', self::SMOKE_DETECTOR_ITEM_TYPE_ID)
        ->where('period_key', $ym)
        ->findAll();

      foreach ($logs as $log) {
        $logMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
      }
    }

    $rows = [];
    foreach ($inventories as $index => $inventory) {
      $inventoryId = (int) ($inventory['id'] ?? 0);
      $rows[] = [
        'id' => $inventoryId,
        'no' => $index + 1,
        'location' => trim((string) ($inventory['specific_area'] ?? '')) !== '' ? trim((string) $inventory['specific_area']) : '-',
        'checks' => $logMap[$inventoryId] ?? [],
        'detail_url' => '/compliance/checklist/' . $inventoryId . '?ym=' . rawurlencode($ym),
      ];
    }

    return view('compliance/checklist/smoke_detector_grid', [
      'title' => 'Checklist Smoke Detector',
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'rows' => $rows,
      'questions' => $questions,
      'saveUrl' => '/compliance/checklist/smoke-detector-grid/save',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
      'focusId' => $focusId,
    ]);
  }

  public function saveSmokeDetectorGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/smoke-detector-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist Smoke Detector tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel->where('id', $inventoryId)->first();
    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::SMOKE_DETECTOR_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory Smoke Detector tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::SMOKE_DETECTOR_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist Smoke Detector dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::SMOKE_DETECTOR_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist Smoke Detector tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllSmokeDetectorGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/smoke-detector-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $periodKey = trim((string) $this->request->getPost('period_key'));
    if (! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode Smoke Detector tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::SMOKE_DETECTOR_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $inventories = $this->inventoryModel
      ->select('id')
      ->where('item_type_id', self::SMOKE_DETECTOR_ITEM_TYPE_ID)
      ->findAll();

    $inventoryIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $inventories)));
    $questionIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $questions)));

    if (empty($inventoryIds) || empty($questionIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada data Smoke Detector untuk diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->whereIn('inventory_id', $inventoryIds)
      ->whereIn('checklist_template_id', $questionIds)
      ->where('item_type_id', self::SMOKE_DETECTOR_ITEM_TYPE_ID)
      ->where('period_key', $periodKey)
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkDate = date('Y-m-d');
    $checkedBy = session()->get('name');

    foreach ($inventoryIds as $inventoryId) {
      foreach ($questionIds as $questionId) {
        $existing = $existingMap[$inventoryId][$questionId] ?? null;
        if ($existing) {
          $logModel->update($existing['id'], [
            'status' => 'ok',
            'checked_by' => $checkedBy,
            'check_date' => $checkDate,
            'updated_at' => $now,
          ]);
          continue;
        }

        $logModel->insert([
          'inventory_id' => $inventoryId,
          'item_type_id' => self::SMOKE_DETECTOR_ITEM_TYPE_ID,
          'checklist_template_id' => $questionId,
          'check_date' => $checkDate,
          'period_key' => $periodKey,
          'time_slot' => null,
          'status' => 'ok',
          'remark' => null,
          'photo' => null,
          'checked_by' => $checkedBy,
          'created_at' => $now,
        ]);
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'message' => 'Semua checklist Smoke Detector berhasil dicentang.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function heatDetectorGrid()
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    page('Checklist Heat Detector');

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    $focusId = (int) ($this->request->getGet('focus_id') ?: 0);

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::HEAT_DETECTOR_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist Heat Detector belum tersedia.');
    }

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, asset_item_types.name AS item_display_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.item_type_id', self::HEAT_DETECTOR_ITEM_TYPE_ID)
      ->orderBy('TRIM(compliance_inventory.specific_area)', 'ASC', false)
      ->orderBy('compliance_inventory.asset_code', 'ASC')
      ->findAll();

    $inventoryIds = array_column($inventories, 'id');
    $logMap = [];

    if (! empty($inventoryIds)) {
      $logs = (new ChecklistLogModel())
        ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
        ->whereIn('inventory_id', $inventoryIds)
        ->where('item_type_id', self::HEAT_DETECTOR_ITEM_TYPE_ID)
        ->where('period_key', $ym)
        ->findAll();

      foreach ($logs as $log) {
        $logMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
      }
    }

    $rows = [];
    foreach ($inventories as $index => $inventory) {
      $inventoryId = (int) ($inventory['id'] ?? 0);
      $rows[] = [
        'id' => $inventoryId,
        'no' => $index + 1,
        'location' => trim((string) ($inventory['specific_area'] ?? '')) !== '' ? trim((string) $inventory['specific_area']) : '-',
        'checks' => $logMap[$inventoryId] ?? [],
        'detail_url' => '/compliance/checklist/' . $inventoryId . '?ym=' . rawurlencode($ym),
      ];
    }

    return view('compliance/checklist/heat_detector_grid', [
      'title' => 'Checklist Heat Detector',
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'rows' => $rows,
      'questions' => $questions,
      'saveUrl' => '/compliance/checklist/heat-detector-grid/save',
      'bulkUrl' => '/compliance/checklist/heat-detector-grid/mark-all',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
      'focusId' => $focusId,
      'itemLabel' => 'Heat Detector',
    ]);
  }

  public function gateGrid($inventoryId)
  {
    if (! hasRole(['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    helper('checklist');
    page('Checklist Gerbang');

    $inventoryId = (int) $inventoryId;
    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    [$year, $month] = explode('-', $ym);
    $monthStart = $ym . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    $inventory = $this->inventoryModel
      ->select('compliance_inventory.*, asset_item_types.name AS item_display_name, asset_item_types.checklist_frequency, asset_item_types.allow_na')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::GATE_INSPECTION_ITEM_TYPE_ID) {
      return redirect()->to('/compliance/inventory')->with('error', 'Inventory Gerbang tidak ditemukan.');
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::GATE_INSPECTION_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory')->with('error', 'Pertanyaan checklist Gerbang belum tersedia.');
    }

    $logMap = [];

    $logs = (new ChecklistLogModel())
      ->select('id, inventory_id, checklist_template_id, period_key, status, checked_by, check_date')
      ->where('inventory_id', $inventoryId)
      ->where('item_type_id', self::GATE_INSPECTION_ITEM_TYPE_ID)
      ->like('period_key', $ym . '-', 'after')
      ->findAll();

    foreach ($logs as $log) {
      $logMap[(int) $log['checklist_template_id']][(string) $log['period_key']] = $log;
    }

    $holidayDates = holiday_dates_between($monthStart, $monthEnd);
    $days = generate_calendar_periods('daily', (int) $year, (int) $month);
    foreach ($days as &$day) {
      $day['is_offday'] = is_date_offday((string) ($day['period_key'] ?? ''), $holidayDates);
    }
    unset($day);

    $rows = [];
    $rowNo = 1;
    foreach ($questions as $question) {
      $templateId = (int) ($question['id'] ?? 0);
      $rows[] = [
        'row_no' => $rowNo++,
        'inventory_id' => $inventoryId,
        'asset_code' => (string) ($inventory['asset_code'] ?? '-'),
        'location' => trim((string) ($inventory['specific_area'] ?? '')) !== '' ? trim((string) $inventory['specific_area']) : '-',
        'question_id' => $templateId,
        'question' => trim((string) ($question['question'] ?? '')) !== '' ? trim((string) $question['question']) : '-',
        'checks' => $logMap[$templateId] ?? [],
        'detail_url' => '/compliance/checklist/' . $inventoryId . '?ym=' . rawurlencode($ym),
      ];
    }

    return view('compliance/checklist/gate_grid', [
      'title' => 'Checklist Gerbang',
      'inventory' => $inventory,
      'ym' => $ym,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'rows' => $rows,
      'days' => $days,
      'saveUrl' => '/compliance/checklist/gate-grid/save',
      'bulkUrl' => '/compliance/checklist/gate-grid/mark-all',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
      'itemLabel' => 'Gerbang',
    ]);
  }

  public function genericGrid($inventoryId)
  {
    if (! hasRole(['admin', 'compliance', 'staff'])) {
      return redirect()->to('/unauthorized');
    }

    helper('checklist');
    page('Checklist Grid');

    $inventoryId = (int) $inventoryId;
    $inventory = $this->inventoryModel
      ->select('
        compliance_inventory.*,
        asset_item_types.name AS item_display_name,
        asset_item_types.checklist_frequency,
        asset_item_types.allow_na
      ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory) {
      return redirect()->to('/compliance/inventory')->with('error', 'Inventory tidak ditemukan.');
    }

    $frequency = strtolower((string) ($inventory['checklist_frequency'] ?? 'monthly'));
    $isSlotChecklist = (int) ($inventory['item_type_id'] ?? 0) === self::TOILET_CHECKLIST_ITEM_TYPE_ID;

    $ym = (string) $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    [$year, $month] = explode('-', $ym);
    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    if (empty($questions)) {
      return redirect()->to('/compliance/inventory/detail/' . $inventoryId)->with('error', 'Pertanyaan checklist belum tersedia.');
    }

    $columns = [];
    $columnMap = [];
    $holidayDates = [];
    $periodPrefix = null;

    if ($frequency === 'daily') {
      $holidayDates = holiday_dates_between($ym . '-01', date('Y-m-t', strtotime($ym . '-01')));
      $columns = generate_calendar_periods('daily', (int) $year, (int) $month);
      foreach ($columns as &$column) {
        $column['is_offday'] = is_date_offday((string) ($column['period_key'] ?? ''), $holidayDates);
      }
      unset($column);
      $periodPrefix = $ym . '-';
    } elseif ($frequency === 'weekly') {
      $columns = array_map(static fn(int $week): array => [
        'period_key' => sprintf('%s-W%d', $ym, $week),
        'label' => 'W' . $week,
        'is_offday' => false,
      ], [1, 2, 3, 4]);
      $periodPrefix = $ym . '-W';
    } else {
      for ($m = 1; $m <= 12; $m++) {
        $periodKey = sprintf('%04d-%02d', (int) $year, $m);
        $columns[] = [
          'period_key' => $periodKey,
          'label' => date('M', strtotime($periodKey . '-01')),
          'is_offday' => false,
        ];
      }
      $periodPrefix = $year . '-';
    }

    foreach ($columns as $column) {
      $columnMap[(string) $column['period_key']] = $column;
    }

    $logQuery = (new ChecklistLogModel())
      ->select('id, inventory_id, checklist_template_id, period_key, status, time_slot')
      ->where('inventory_id', $inventoryId)
      ->where('item_type_id', (int) $inventory['item_type_id']);

    if ($frequency === 'monthly') {
      $logQuery->like('period_key', $periodPrefix, 'after');
    } else {
      $logQuery->like('period_key', $periodPrefix, 'after');
    }

    $logs = $logQuery->findAll();
    $logMap = [];
    foreach ($logs as $log) {
      $templateId = (int) ($log['checklist_template_id'] ?? 0);
      $periodKey = (string) ($log['period_key'] ?? '');
      $slot = trim((string) ($log['time_slot'] ?? ''));
      $logMap[$templateId][$slot][$periodKey] = $log;
    }

    $slotLabels = [
      'PG' => 'Pagi',
      'SI' => 'Siang',
      'SO' => 'Sore',
    ];

    $rows = [];
    $rowNo = 1;
    if ($isSlotChecklist && $frequency === 'daily') {
      foreach ($slotLabels as $slotCode => $slotLabel) {
        foreach ($questions as $question) {
          $templateId = (int) ($question['id'] ?? 0);
          $rows[] = [
            'row_no' => $rowNo++,
            'template_id' => $templateId,
            'slot_code' => $slotCode,
            'slot_label' => $slotLabel,
            'question' => trim((string) ($question['question'] ?? '')) !== '' ? trim((string) ($question['question'])) : '-',
            'checks' => $logMap[$templateId][$slotCode] ?? [],
          ];
        }
      }
    } else {
      foreach ($questions as $question) {
        $templateId = (int) ($question['id'] ?? 0);
        $rows[] = [
          'row_no' => $rowNo++,
          'template_id' => $templateId,
          'slot_code' => '',
          'slot_label' => '',
          'question' => trim((string) ($question['question'] ?? '')) !== '' ? trim((string) ($question['question'])) : '-',
          'checks' => $logMap[$templateId][''] ?? [],
        ];
      }
    }

    return view('compliance/checklist/generic_grid', [
      'title' => 'Checklist Grid',
      'inventory' => $inventory,
      'frequency' => $frequency,
      'ym' => $ym,
      'year' => (int) $year,
      'monthLabel' => date('F Y', strtotime($ym . '-01')),
      'columns' => $columns,
      'rows' => $rows,
      'isSlotChecklist' => $isSlotChecklist,
      'saveUrl' => '/compliance/checklist/generic-grid/save',
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function saveGenericGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/inventory');
    }

    if (! hasRole(['admin', 'compliance', 'staff'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    helper('checklist');

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $templateId = (int) $this->request->getPost('template_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $mode = strtolower(trim((string) $this->request->getPost('mode')));
    $timeSlot = trim((string) $this->request->getPost('time_slot'));

    if ($inventoryId <= 0 || $templateId <= 0 || ! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist grid tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.item_type_id, asset_item_types.checklist_frequency')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $frequency = strtolower((string) ($inventory['checklist_frequency'] ?? 'monthly'));
    $isSlotChecklist = (int) ($inventory['item_type_id'] ?? 0) === self::TOILET_CHECKLIST_ITEM_TYPE_ID;

    if (
      ($frequency === 'daily' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodKey)) ||
      ($frequency === 'weekly' && ! preg_match('/^\d{4}-\d{2}-W[1-4]$/', $periodKey)) ||
      ($frequency === 'monthly' && ! preg_match('/^\d{4}-\d{2}$/', $periodKey))
    ) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode checklist grid tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($frequency === 'daily') {
      $holidayDates = holiday_dates_between($periodKey, $periodKey);
      if (is_date_offday($periodKey, $holidayDates)) {
        return $this->response->setStatusCode(422)->setJSON([
          'ok' => false,
          'message' => 'Checklist tidak dapat diisi pada hari libur.',
          'csrfHash' => csrf_hash(),
        ]);
      }
    } elseif (is_period_future($frequency, $periodKey) || ! is_period_editable($frequency, $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode checklist tidak bisa diedit.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! $isSlotChecklist) {
      $timeSlot = '';
    } elseif (! in_array($timeSlot, ['PG', 'SI', 'SO'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Slot checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', (int) $inventory['item_type_id'])
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existingQuery = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId);

    if ($isSlotChecklist) {
      $existingQuery->where('time_slot', $timeSlot);
    }

    $existing = $existingQuery->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist grid dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $payload = [
      'inventory_id' => $inventoryId,
      'item_type_id' => (int) $inventory['item_type_id'],
      'checklist_template_id' => $templateId,
      'check_date' => $frequency === 'daily'
        ? $periodKey
        : ($frequency === 'weekly'
          ? preg_replace('/-W[1-4]$/', '-01', $periodKey)
          : ($periodKey . '-01')),
      'period_key' => $periodKey,
      'time_slot' => $isSlotChecklist ? $timeSlot : null,
      'status' => $mode,
      'remark' => null,
      'photo' => null,
      'checked_by' => session()->get('name'),
      'created_at' => date('Y-m-d H:i:s'),
    ];

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'check_date' => $payload['check_date'],
        'time_slot' => $payload['time_slot'],
        'checked_by' => $payload['checked_by'],
      ]);
    } else {
      $logModel->insert($payload);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist grid tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllGenericGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/inventory');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    helper('checklist');

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $ym = trim((string) $this->request->getPost('ym'));

    if ($inventoryId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode checklist grid tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel
      ->select('compliance_inventory.id, compliance_inventory.item_type_id, asset_item_types.checklist_frequency')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $frequency = strtolower((string) ($inventory['checklist_frequency'] ?? 'monthly'));
    $isSlotChecklist = (int) ($inventory['item_type_id'] ?? 0) === self::TOILET_CHECKLIST_ITEM_TYPE_ID;
    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', (int) $inventory['item_type_id'])
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $questionIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $questions)));
    if (empty($questionIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada pertanyaan checklist aktif.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    [$year, $month] = explode('-', $ym);
    $periods = [];
    $periodPrefix = '';
    $holidayDates = [];

    if ($frequency === 'daily') {
      $periodPrefix = $ym . '-';
      $monthStart = $ym . '-01';
      $monthEnd = date('Y-m-t', strtotime($monthStart));
      $holidayDates = holiday_dates_between($monthStart, $monthEnd);

      foreach (generate_calendar_periods('daily', (int) $year, (int) $month) as $day) {
        $periodKey = (string) ($day['period_key'] ?? '');
        if ($periodKey === '' || is_date_offday($periodKey, $holidayDates) || is_period_future('daily', $periodKey)) {
          continue;
        }
        $periods[] = $periodKey;
      }
    } elseif ($frequency === 'weekly') {
      $periodPrefix = $ym . '-W';
      for ($week = 1; $week <= 4; $week++) {
        $periodKey = sprintf('%s-W%d', $ym, $week);
        if (is_period_future('weekly', $periodKey) || ! is_period_editable('weekly', $periodKey)) {
          continue;
        }
        $periods[] = $periodKey;
      }
    } else {
      $periodPrefix = $year . '-';
      for ($monthNumber = 1; $monthNumber <= 12; $monthNumber++) {
        $periodKey = sprintf('%04d-%02d', (int) $year, $monthNumber);
        if (is_period_future('monthly', $periodKey) || ! is_period_editable('monthly', $periodKey)) {
          continue;
        }
        $periods[] = $periodKey;
      }
    }

    if (empty($periods)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada periode checklist yang bisa dicentang.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $slots = $isSlotChecklist ? ['PG', 'SI', 'SO'] : [''];
    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('item_type_id', (int) $inventory['item_type_id'])
      ->whereIn('checklist_template_id', $questionIds)
      ->like('period_key', $periodPrefix, 'after')
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $templateId = (int) ($log['checklist_template_id'] ?? 0);
      $slot = trim((string) ($log['time_slot'] ?? ''));
      $periodKey = (string) ($log['period_key'] ?? '');
      $existingMap[$templateId][$slot][$periodKey] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkedBy = session()->get('name');
    $inserted = 0;

    foreach ($questionIds as $questionId) {
      foreach ($slots as $slot) {
        foreach ($periods as $periodKey) {
          if (isset($existingMap[$questionId][$slot][$periodKey])) {
            continue;
          }

          $checkDate = $frequency === 'daily'
            ? $periodKey
            : ($frequency === 'weekly'
              ? preg_replace('/-W[1-4]$/', '-01', $periodKey)
              : ($periodKey . '-01'));

          $logModel->insert([
            'inventory_id' => $inventoryId,
            'item_type_id' => (int) $inventory['item_type_id'],
            'checklist_template_id' => $questionId,
            'check_date' => $checkDate,
            'period_key' => $periodKey,
            'time_slot' => $isSlotChecklist ? $slot : null,
            'status' => 'ok',
            'remark' => null,
            'photo' => null,
            'checked_by' => $checkedBy,
            'created_at' => $now,
          ]);
          $inserted++;
        }
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'inserted' => $inserted,
      'message' => 'Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function saveGateGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/gate-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist Gerbang tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel->where('id', $inventoryId)->first();
    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::GATE_INSPECTION_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory Gerbang tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $holidayDates = holiday_dates_between($periodKey, $periodKey);
    if (is_date_offday($periodKey, $holidayDates)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Checklist tidak dapat diisi pada hari libur.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::GATE_INSPECTION_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist Gerbang dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::GATE_INSPECTION_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist Gerbang tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllGateGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/inventory');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    helper('checklist');

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $ym = trim((string) $this->request->getPost('ym'));
    if ($inventoryId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode Gerbang tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel->where('id', $inventoryId)->first();
    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::GATE_INSPECTION_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory Gerbang tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::GATE_INSPECTION_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $questionIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $questions)));

    if (empty($questionIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada data Gerbang untuk diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->where('inventory_id', $inventoryId)
      ->whereIn('checklist_template_id', $questionIds)
      ->where('item_type_id', self::GATE_INSPECTION_ITEM_TYPE_ID)
      ->like('period_key', $ym . '-', 'after')
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']][(string) $log['period_key']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkDate = date('Y-m-d');
    $checkedBy = session()->get('name');
    $inserted = 0;
    $monthStart = $ym . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $holidayDates = holiday_dates_between($monthStart, $monthEnd);
    $days = generate_calendar_periods('daily', (int) substr($ym, 0, 4), (int) substr($ym, 5, 2));

    foreach ($questionIds as $questionId) {
      foreach ($days as $day) {
        $periodKey = (string) ($day['period_key'] ?? '');
        if ($periodKey === '' || is_date_offday($periodKey, $holidayDates)) {
          continue;
        }

        if (isset($existingMap[$inventoryId][$questionId][$periodKey])) {
          continue;
        }

        $logModel->insert([
          'inventory_id' => $inventoryId,
          'item_type_id' => self::GATE_INSPECTION_ITEM_TYPE_ID,
          'checklist_template_id' => $questionId,
          'check_date' => $checkDate,
          'period_key' => $periodKey,
          'time_slot' => null,
          'status' => 'ok',
          'remark' => null,
          'photo' => null,
          'checked_by' => $checkedBy,
          'created_at' => $now,
        ]);
        $inserted++;
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'inserted' => $inserted,
      'message' => 'Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function saveHeatDetectorGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/heat-detector-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventoryId = (int) $this->request->getPost('inventory_id');
    $periodKey = trim((string) $this->request->getPost('period_key'));
    $templateId = (int) $this->request->getPost('template_id');
    $mode = strtolower(trim((string) $this->request->getPost('mode')));

    if ($inventoryId <= 0 || $templateId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Data checklist Heat Detector tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if (! in_array($mode, ['ok', 'not_ok', 'clear'], true)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Status checklist tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $inventory = $this->inventoryModel->where('id', $inventoryId)->first();
    if (! $inventory || (int) ($inventory['item_type_id'] ?? 0) !== self::HEAT_DETECTOR_ITEM_TYPE_ID) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Inventory Heat Detector tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $question = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::HEAT_DETECTOR_ITEM_TYPE_ID)
      ->where('active', 1)
      ->where('id', $templateId)
      ->first();

    if (! $question) {
      return $this->response->setStatusCode(404)->setJSON([
        'ok' => false,
        'message' => 'Pertanyaan checklist tidak ditemukan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existing = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->where('checklist_template_id', $templateId)
      ->first();

    if ($mode === 'clear') {
      if ($existing) {
        $logModel->delete($existing['id']);
      }

      return $this->response->setJSON([
        'ok' => true,
        'state' => 'empty',
        'message' => 'Checklist Heat Detector dibersihkan.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existing) {
      $logModel->update($existing['id'], [
        'status' => $mode,
        'checked_by' => session()->get('name'),
        'check_date' => date('Y-m-d'),
        'updated_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      $logModel->insert([
        'inventory_id' => $inventoryId,
        'item_type_id' => self::HEAT_DETECTOR_ITEM_TYPE_ID,
        'checklist_template_id' => $templateId,
        'check_date' => date('Y-m-d'),
        'period_key' => $periodKey,
        'time_slot' => null,
        'status' => $mode,
        'remark' => null,
        'photo' => null,
        'checked_by' => session()->get('name'),
        'created_at' => date('Y-m-d H:i:s'),
      ]);
    }

    return $this->response->setJSON([
      'ok' => true,
      'state' => $mode,
      'message' => 'Checklist Heat Detector tersimpan.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function markAllHeatDetectorGrid()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->to('/compliance/checklist/heat-detector-grid');
    }

    if (! hasRole(['admin', 'compliance'])) {
      return $this->response->setStatusCode(403)->setJSON([
        'ok' => false,
        'message' => 'Anda tidak memiliki akses.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $periodKey = trim((string) $this->request->getPost('period_key'));
    if (! preg_match('/^\d{4}-\d{2}$/', $periodKey)) {
      return $this->response->setStatusCode(422)->setJSON([
        'ok' => false,
        'message' => 'Periode Heat Detector tidak valid.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', self::HEAT_DETECTOR_ITEM_TYPE_ID)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $inventories = $this->inventoryModel
      ->select('id')
      ->where('item_type_id', self::HEAT_DETECTOR_ITEM_TYPE_ID)
      ->findAll();

    $inventoryIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $inventories)));
    $questionIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $questions)));

    if (empty($inventoryIds) || empty($questionIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada data Heat Detector untuk diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->whereIn('inventory_id', $inventoryIds)
      ->whereIn('checklist_template_id', $questionIds)
      ->where('item_type_id', self::HEAT_DETECTOR_ITEM_TYPE_ID)
      ->where('period_key', $periodKey)
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkDate = date('Y-m-d');
    $checkedBy = session()->get('name');

    foreach ($inventoryIds as $inventoryId) {
      foreach ($questionIds as $questionId) {
        $existing = $existingMap[$inventoryId][$questionId] ?? null;
        if ($existing) {
          $logModel->update($existing['id'], [
            'status' => 'ok',
            'checked_by' => $checkedBy,
            'check_date' => $checkDate,
            'updated_at' => $now,
          ]);
          continue;
        }

        $logModel->insert([
          'inventory_id' => $inventoryId,
          'item_type_id' => self::HEAT_DETECTOR_ITEM_TYPE_ID,
          'checklist_template_id' => $questionId,
          'check_date' => $checkDate,
          'period_key' => $periodKey,
          'time_slot' => null,
          'status' => 'ok',
          'remark' => null,
          'photo' => null,
          'checked_by' => $checkedBy,
          'created_at' => $now,
        ]);
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'message' => 'Semua checklist Heat Detector berhasil dicentang.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  private function resolveEmergencyLightGridColumns(array $masters): array
  {
    $groups = [
      'lampu_darurat' => $this->buildEmergencyLampGroup('lampu_darurat', 'Lampu Darurat'),
    ];

    foreach ($masters as $master) {
      $templateId = (int) ($master['id'] ?? 0);
      $question = strtolower(trim((string) ($master['question'] ?? '')));

      if ($templateId < 1 || $question === '') {
        continue;
      }

      $groupKey = null;
      if (strpos($question, 'darurat') !== false || strpos($question, 'emergency') !== false) {
        $groupKey = 'lampu_darurat';
      }

      if ($groupKey === null || ! isset($groups[$groupKey])) {
        continue;
      }

      foreach ($groups[$groupKey]['columns'] as &$column) {
        if (($column['type'] ?? '') !== 'question') {
          continue;
        }

        $slot = (string) ($column['slot'] ?? '');
        if ($slot === 'berfungsi' && strpos($question, 'berfun') !== false) {
          $column['id'] = $templateId;
        } elseif ($slot === 'pecah' && strpos($question, 'pecah') !== false) {
          $column['id'] = $templateId;
        } elseif ($slot === 'kabel' && strpos($question, 'kabel') !== false) {
          $column['id'] = $templateId;
        }
      }
      unset($column);
    }

    return array_values($groups);
  }

  private function resolveEmergencyExitLightGridColumns(array $masters): array
  {
    $groups = [
      'lampu_exit' => $this->buildEmergencyLampGroup('lampu_exit', 'Lampu EXIT'),
    ];

    foreach ($masters as $master) {
      $templateId = (int) ($master['id'] ?? 0);
      $question = strtolower(trim((string) ($master['question'] ?? '')));

      if ($templateId < 1 || $question === '') {
        continue;
      }

      if (strpos($question, 'jenis') !== false) {
        continue;
      }

      foreach ($groups['lampu_exit']['columns'] as &$column) {
        if (($column['type'] ?? '') !== 'question') {
          continue;
        }

        $slot = (string) ($column['slot'] ?? '');
        if ($slot === 'berfungsi' && strpos($question, 'berfun') !== false) {
          $column['id'] = $templateId;
        } elseif ($slot === 'pecah' && strpos($question, 'pecah') !== false) {
          $column['id'] = $templateId;
        } elseif ($slot === 'kabel' && strpos($question, 'kabel') !== false) {
          $column['id'] = $templateId;
        }
      }
      unset($column);
    }

    return array_values($groups);
  }

  private function buildEmergencyLampGroup(string $groupKey, string $label): array
  {
    return [
      'group_key' => $groupKey,
      'label' => $label,
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
    ];
  }

  private function bulkMarkEmergencyLampGrid(int $itemTypeId, string $periodKey, string $itemLabel)
  {
    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', $itemTypeId)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    $inventories = $this->inventoryModel
      ->select('id')
      ->where('item_type_id', $itemTypeId)
      ->findAll();

    $inventoryIds = array_values(array_filter(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $inventories)));
    $questionIds = array_values(array_filter(array_map(static function (array $row): int {
      $question = strtolower(trim((string) ($row['question'] ?? '')));
      if (strpos($question, 'jenis') !== false) {
        return 0;
      }

      return (int) ($row['id'] ?? 0);
    }, $questions)));

    if (empty($inventoryIds) || empty($questionIds)) {
      return $this->response->setJSON([
        'ok' => true,
        'message' => 'Tidak ada data ' . $itemLabel . ' untuk diperbarui.',
        'csrfHash' => csrf_hash(),
      ]);
    }

    $logModel = new ChecklistLogModel();
    $existingLogs = $logModel
      ->whereIn('inventory_id', $inventoryIds)
      ->whereIn('checklist_template_id', $questionIds)
      ->where('item_type_id', $itemTypeId)
      ->where('period_key', $periodKey)
      ->findAll();

    $existingMap = [];
    foreach ($existingLogs as $log) {
      $existingMap[(int) $log['inventory_id']][(int) $log['checklist_template_id']] = $log;
    }

    $now = date('Y-m-d H:i:s');
    $checkDate = date('Y-m-d');
    $checkedBy = session()->get('name');
    $inserted = 0;

    foreach ($inventoryIds as $inventoryId) {
      foreach ($questionIds as $questionId) {
        if (isset($existingMap[$inventoryId][$questionId])) {
          continue;
        }

        $logModel->insert([
          'inventory_id' => $inventoryId,
          'item_type_id' => $itemTypeId,
          'checklist_template_id' => $questionId,
          'check_date' => $checkDate,
          'period_key' => $periodKey,
          'time_slot' => null,
          'status' => 'ok',
          'remark' => null,
          'photo' => null,
          'checked_by' => $checkedBy,
          'created_at' => $now,
        ]);
        $inserted++;
      }
    }

    return $this->response->setJSON([
      'ok' => true,
      'inserted' => $inserted,
      'message' => 'Centang semua hanya mengisi sel kosong. Data yang sudah terisi tidak ditimpa.',
      'csrfHash' => csrf_hash(),
    ]);
  }

  private function resolveWeeklyAlarmGridColumns(array $masters): array
  {
    $groupMap = [];
    foreach ($masters as $master) {
      $question = trim((string) ($master['question'] ?? ''));
      if ($question === '') {
        continue;
      }

      $groupMap[$question] = [
        'label' => $question,
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

    return array_values($groupMap);
  }

  private function resolveHydrantLabel(array $inventory): string
  {
    $assetCode = trim((string) ($inventory['asset_code'] ?? ''));
    if ($assetCode !== '' && preg_match('/(\d+)\s*$/', $assetCode, $matches)) {
      return 'Hydrant ' . ((int) $matches[1]);
    }

    $location = trim((string) ($inventory['specific_area'] ?? ''));
    if ($location !== '' && preg_match('/hidr?an?t?\s*(\d+)/i', $location, $matches)) {
      return 'Hydrant ' . ((int) $matches[1]);
    }

    return $assetCode !== '' ? $assetCode : 'Hydrant';
  }

  private function resolveFireExtinguisherGridColumns(array $masters): array
  {
    $columnMap = [];
    foreach ($masters as $master) {
      $question = trim((string) ($master['question'] ?? ''));
      $columnMap[$question] = [
        'type' => 'question',
        'id' => (int) ($master['id'] ?? 0),
        'label' => $this->resolveFireExtinguisherGridLabel($question),
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

    $primaryQuestions = $this->takeOrderedGridQuestions($columnMap, $primaryOrder);
    $secondaryQuestions = $this->takeOrderedGridQuestions($columnMap, $secondaryOrder);

    foreach ($columnMap as $column) {
      if ($this->isFireExtinguisherConditionGridQuestion((string) ($column['label'] ?? ''))) {
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

  private function takeOrderedGridQuestions(array &$columnMap, array $orderedQuestions): array
  {
    $ordered = [];

    foreach ($orderedQuestions as $question) {
      if (! isset($columnMap[$question])) {
        continue;
      }

      $ordered[] = $columnMap[$question];
      unset($columnMap[$question]);
    }

    return $ordered;
  }

  private function isFireExtinguisherConditionGridQuestion(string $label): bool
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

  private function resolveFireExtinguisherGridLabel(string $question): string
  {
    $labelMap = [
      'Pin/Segel' => 'Kondisi Segel',
      'Tidak Terhalang' => 'Terhalang',
    ];

    return $labelMap[$question] ?? $question;
  }

  public function calendar($inventoryId)
  {
    helper('checklist');

    $ym = $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      return $this->response->setStatusCode(400);
    }

    [$year, $month] = explode('-', $ym);

    $inventory = $this->inventoryModel
      ->select('
      compliance_inventory.id,
      asset_item_types.checklist_frequency
    ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory) {
      return $this->response->setStatusCode(404);
    }

    $frequency = $inventory['checklist_frequency'] ?? 'monthly';

    $requestPeriodKey = $this->request->getGet('period_key');

    if ($frequency === 'daily') {

      $daysInMonth = date('t', strtotime($ym . '-01'));
      $holidayDates = holiday_dates_between($ym . '-01', date('Y-m-t', strtotime($ym . '-01')));

      $firstValidDate = null;

      for ($d = 1; $d <= $daysInMonth; $d++) {

        $date = $ym . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);

        if (!is_date_offday($date, $holidayDates)) {
          $firstValidDate = $date;
          break;
        }
      }

      $defaultPeriodKey = $firstValidDate ?? ($ym . '-01');
    } elseif ($frequency === 'weekly') {

      $defaultPeriodKey = $ym . '-W1';
    } else {

      $defaultPeriodKey = $ym;
    }

    if (! $requestPeriodKey) {
      $periodKey = $defaultPeriodKey;
    } else {
      if (
        ($frequency === 'daily'   && ! str_starts_with($requestPeriodKey, $ym . '-')) ||
        ($frequency === 'weekly'  && ! str_starts_with($requestPeriodKey, $ym . '-W')) ||
        ($frequency === 'monthly' && $requestPeriodKey !== $ym)
      ) {
        $periodKey = $defaultPeriodKey;
      } else {
        $periodKey = $requestPeriodKey;
      }
    }

    $periods = generate_calendar_periods($frequency, (int)$year, (int)$month);

    foreach ($periods as &$p) {
      $p['allowed']   = is_period_editable($frequency, $p['period_key']);
      $p['status']    = resolve_period_status(
        $inventory['id'],
        $frequency,
        $p['period_key']
      );
      $p['is_active'] = ($p['period_key'] === $periodKey);
    }
    unset($p);

    return view('compliance/checklist/_calendar', [
      'inventory'   => $inventory,
      'inventoryId' => $inventoryId,
      'periods'     => $periods,
      'frequency'   => $frequency,
      'navYM'       => $ym,
      'period_key'  => $periodKey,
    ]);
  }


  public function get($id)
  {
    $inv = $this->inventoryModel
      ->select('
      compliance_inventory.*,
      inventory_categories.name as category_name,
      areas.name as area_name,
      asset_item_types.name as item_name
    ')
      ->join('inventory_categories', 'inventory_categories.id = compliance_inventory.category_id', 'left')
      ->join('areas', 'areas.id = compliance_inventory.area_id', 'left')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->find($id);

    return $this->response->setJSON($inv);
  }

  public function regenerateQr($id)
  {
    if (!hasRole(['admin', 'compliance'])) {
      return $this->response->setJSON(['status' => 'error']);
    }

    $inventory = $this->inventoryModel->find($id);
    if (!$inventory) {
      return $this->response->setJSON(['status' => 'error']);
    }

    try {

      $qrFile = service('qr')->generate($id, $inventory['asset_code']);

      $this->inventoryModel->update($id, ['qr_image' => $qrFile]);

      return $this->response->setJSON([
        'status' => 'success',
        'qr_image' => $qrFile
      ]);
    } catch (\Throwable $e) {

      log_message('error', $e->getMessage());

      return $this->response->setJSON([
        'status' => 'error',
        'message' => 'Gagal melakukan regenerate QR.'
      ]);
    }
  }

  public function qrCenter()
  {
    $list = $this->inventoryModel
      ->select('compliance_inventory.*, asset_item_types.name as item_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->where('asset_code IS NOT NULL')
      ->where('qr_image IS NOT NULL')
      ->orderBy('asset_item_types.name', 'ASC')
      ->orderBy('asset_code', 'ASC')
      ->findAll();

    $albums = [];

    foreach ($list as $row) {

      $name = $row['item_name'];

      if (!isset($albums[$name])) {
        $albums[$name] = [
          'cover' => $row['qr_image'],
          'count' => 0,
          'rows' => []
        ];
      }

      $albums[$name]['rows'][] = $row;
      $albums[$name]['count']++;
    }

    return view('compliance/inventory/qr_center', [
      'albums' => $albums,
      'totalQr' => count($list),
      'totalAlbums' => count($albums),
      'title' => 'QR Center',
    ]);
  }

  public function qrBatch()
  {
    $ids = explode(',', $this->request->getGet('ids'));

    $items = $this->inventoryModel
      ->whereIn('id', $ids)
      ->findAll();

    $zip = new \ZipArchive();
    $file = WRITEPATH . 'qr.zip';

    $zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    foreach ($items as $it) {
      $path = FCPATH . 'uploads/qr/' . $it['qr_image'];
      if (file_exists($path)) {
        $zip->addFile($path, $it['asset_code'] . '.png');
      }
    }

    $zip->close();

    return $this->response
      ->download($file, null)
      ->setFileName('qr-gallery.zip');
  }

  public function qrAlbumAjax($itemName)
  {
    $rows = $this->inventoryModel
      ->select('compliance_inventory.*, asset_item_types.name as item_name')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->where('asset_item_types.name', $itemName)
      ->where('qr_image IS NOT NULL')
      ->orderBy('asset_code', 'ASC')
      ->findAll();

    return view('compliance/inventory/_qr_album_grid', [
      'rows' => $rows,
      'itemName' => $itemName,
    ]);
  }

  public function qrAlbumDownload($itemName)
  {
    $rows = $this->inventoryModel
      ->select('*')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->where('asset_item_types.name', $itemName)
      ->where('qr_image IS NOT NULL')
      ->findAll();

    $zip = new \ZipArchive();
    $file = WRITEPATH . 'qr-' . $itemName . '.zip';

    $zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    foreach ($rows as $r) {
      $path = FCPATH . 'uploads/qr/' . $r['qr_image'];
      if (file_exists($path)) {
        $zip->addFile($path, $r['asset_code'] . '.png');
      }
    }

    $zip->close();

    return $this->response->download($file, null);
  }

  public function qrAlbumRegen($itemName)
  {
    set_time_limit(300);

    if (!hasRole(['admin', 'compliance'])) {
      return $this->response->setJSON(['status' => false]);
    }

    $rows = $this->inventoryModel
      ->select('compliance_inventory.id,compliance_inventory.asset_code')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->where('asset_item_types.name', $itemName)
      ->findAll();

    foreach ($rows as $r) {

      $qrFile = service('qr')->generate($r['id'], $r['asset_code']);

      $this->inventoryModel->update($r['id'], [
        'qr_image' => $qrFile
      ]);
    }

    return $this->response->setJSON([
      'status' => true,
      'message' => 'QR album berhasil diregenerate'
    ]);
  }

  public function qrAlbumPrint($itemName)
  {
    $rows = $this->inventoryModel
      ->select('compliance_inventory.asset_code,compliance_inventory.qr_image,compliance_inventory.specific_area')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->where('asset_item_types.name', $itemName)
      ->where('qr_image IS NOT NULL')
      ->orderBy('asset_code', 'ASC')
      ->findAll();

    return view('compliance/inventory/qr_print_album', [
      'rows' => $rows,
      'itemName' => $itemName
    ]);
  }

  private function buildCctvDisplayName(array $inventory): string
  {
    $remark = trim((string) ($inventory['remark'] ?? ''));
    if ($remark !== '') {
      return $remark;
    }

    $assetCode = (string) ($inventory['asset_code'] ?? '');
    if (preg_match('/(\d+)$/', $assetCode, $match)) {
      return 'Camera ' . ltrim($match[1], '0');
    }

    return 'CCTV';
  }
}
