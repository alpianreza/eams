<?php

namespace App\Controllers;

use App\Models\ComplianceInventoryModel;
use App\Models\InventoryCategoryModel;
use App\Models\AreaModel;
use App\Models\ChecklistLogModel;


class ComplianceInventoryController extends BaseController
{
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

    return view('compliance/inventory/index', [
      'inventories' => $query->paginate($perPage),
      'pager'       => $this->inventoryModel->pager,
      'categories'  => $this->categoryModel->findAll(),
      'areas'       => $this->areaModel->findAll(),
      'category'    => $categoryId,
      'area'        => $areaId,
      'keyword'     => $keyword,
      'perPage'     => $perPage,
      'isWritable'  => true
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

    $checklists = (new \App\Models\ChecklistLogModel())
      ->select('
        period_key,
        MAX(check_date) as check_date,
        MAX(checked_by) as checked_by
    ')
      ->where('inventory_id', $id)
      ->groupBy('period_key')
      ->orderBy('check_date', 'DESC')
      ->findAll();


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

      $holidayModel = new \App\Models\HolidayModel();
      $today = date('Y-m-d');

      // ===============================
      // 1️⃣ Kalau buka bulan sekarang
      // ===============================
      if ($ym === date('Y-m')) {

        $candidate = $today;

        // Kalau hari ini libur → mundur cari hari kerja terakhir
        while (true) {

          $isSunday = date('w', strtotime($candidate)) == 0;

          $isHoliday = $holidayModel
            ->where('holiday_date', $candidate)
            ->first() ? true : false;

          if (!$isSunday && !$isHoliday) {
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

          $isSunday = date('w', strtotime($date)) == 0;

          $isHoliday = $holidayModel
            ->where('holiday_date', $date)
            ->first() ? true : false;

          if (!$isSunday && !$isHoliday) {
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

    $holidayModel = new \App\Models\HolidayModel();

    foreach ($periods as &$p) {

      $p['is_offday'] = false;

      // ================= DAILY ONLY =================
      if ($frequency === 'daily') {

        $date = $p['period_key'];

        $isSunday = date('w', strtotime($date)) == 0;

        $isHoliday = $holidayModel
          ->where('holiday_date', $date)
          ->first() ? true : false;

        $isOffday = $isSunday || $isHoliday;

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
    $holidayModel = new \App\Models\HolidayModel();

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

      $isSunday = date('w', strtotime($periodKey)) == 0;

      $isHoliday = $holidayModel
        ->where('holiday_date', $periodKey)
        ->first() ? true : false;

      if ($isSunday || $isHoliday) {
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

      /* ================= OFFDAY VALIDATION KHUSUS DAILY ================= */
      if ($frequency === 'daily') {

        $holidayModel = new \App\Models\HolidayModel();

        $isSunday = date('w', strtotime($periodKey)) == 0;

        $isHoliday = $holidayModel
          ->where('holiday_date', $periodKey)
          ->first() ? true : false;

        if ($isSunday || $isHoliday) {
          return redirect()->back()
            ->with('error', 'Checklist tidak dapat diisi pada hari libur.');
        }
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
      $holidayModel = new \App\Models\HolidayModel();

      $firstValidDate = null;

      for ($d = 1; $d <= $daysInMonth; $d++) {

        $date = $ym . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);

        $isSunday = date('w', strtotime($date)) == 0;

        $isHoliday = $holidayModel
          ->where('holiday_date', $date)
          ->first() ? true : false;

        if (!$isSunday && !$isHoliday) {
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
      'albums' => $albums
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
      'rows' => $rows
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
}
