<?php

namespace App\Controllers;

use App\Models\ComplianceInventoryModel;
use App\Models\InventoryCategoryModel;
use App\Models\AreaModel;
use App\Models\ChecklistLogModel;
use App\Models\ChecklistMasterModel;


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
    $this->inventoryModel->update($id, [
      'category_id'      => $this->request->getPost('category_id'),
      'area_id'          => $this->request->getPost('area_id'),
      'item_type_id'     => $this->request->getPost('item_type_id'),
      'asset_code'       => $this->request->getPost('asset_code'),
      'type_description' => $this->request->getPost('type_description'),
      'pic'              => $this->request->getPost('pic'),
      'status'           => $this->request->getPost('status'),
      'remark'           => $this->request->getPost('remark')
    ]);

    return $this->response->setJSON([
      'status' => 'success'
    ]);
  }

  public function delete($id)
  {
    $this->inventoryModel->delete($id);
    return redirect()->back();
  }

  public function store()
  {
    if (! $this->validate([
      'category_id' => 'required|integer',
      'area_id'     => 'required|integer',
      'item_type_id' => 'required|integer',
      'qty'         => 'required|is_natural_no_zero'
    ])) {
      return redirect()->back()->withInput();
    }

    $area = $this->areaModel->find($this->request->getPost('area_id'));

    $expiredDate = null;
    if ($area && strtolower($area['name']) === 'fire safety') {
      $expiredDate = $this->request->getPost('expired_date') ?: null;
    }

    // FOTO
    $photoName = null;
    $photo = $this->request->getFile('photo');
    if ($photo && $photo->isValid() && ! $photo->hasMoved()) {
      $photoName = $photo->getRandomName();
      $photo->move(FCPATH . 'uploads/inventory', $photoName);
    }

    // ASSET CODE
    $assetCode = $this->request->getPost('asset_code') ?: 'INV-' . time();

    $data = [
      'category_id'      => $this->request->getPost('category_id'),
      'area_id'          => $this->request->getPost('area_id'),
      'item_type_id'     => $this->request->getPost('item_type_id'),
      'asset_code'       => $assetCode,
      'type_description' => $this->request->getPost('type_description'),
      'specific_area'    => $this->request->getPost('specific_area'),
      'pic'              => $this->request->getPost('pic'),
      'status'           => $this->request->getPost('status'),
      'qty'              => $this->request->getPost('qty'),
      'remark'           => $this->request->getPost('remark'),
      'expired_date'     => $expiredDate,
      'photo'            => $photoName
    ];

    $this->inventoryModel->insert($data);
    $inventoryId = $this->inventoryModel->getInsertID();

    // QR
    $detailUrl = base_url('compliance/inventory/detail/' . $inventoryId);
    $qrFile = 'qr_inv_' . $inventoryId . '.png';
    $qrPath = FCPATH . 'uploads/qr/' . $qrFile;

    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='
      . urlencode($detailUrl);

    file_put_contents($qrPath, file_get_contents($qrApiUrl));

    $this->inventoryModel->update($inventoryId, [
      'qr_image' => $qrFile
    ]);

    return redirect()->to('/compliance/inventory')
      ->with('success', 'Inventory & QR Code berhasil ditambahkan');
  }

  public function detail($id)
  {
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
    // CHECKLIST HISTORY
    // =========================
    $checklists = (new \App\Models\ChecklistLogModel())
      ->select('
    period_key,
    MAX(check_date) as check_date,
    MAX(checked_by) as checked_by')
      ->where('inventory_id', $id)
      ->groupBy('period_key')
      ->orderBy('check_date', 'DESC')
      ->findAll();

    // =========================
    // BULAN AKTIF
    // =========================
    $ym = $this->request->getGet('ym');
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    // =========================
    // REKAP BULANAN
    // =========================

    $rekap = [
      'total' => 0,
      'ok'    => 0,
      'ng'    => 0,
      'late'  => 0,
    ];

    foreach ($checklists as $row) {

      // hanya bulan aktif
      if (strpos($row['period_key'], $ym) !== 0) {
        continue;
      }

      $rekap['total']++;

      $state = resolve_period_status(
        $inventory['id'],
        $inventory['checklist_frequency'],
        $row['period_key']
      );

      if ($state === 'done') {
        $rekap['ok']++;
      } elseif ($state === 'late') {
        $rekap['late']++;
      } else {
        $rekap['ng']++;
      }
    }

    $nowYM = date('Y-m');
    $isFutureMonth = $ym > $nowYM;



    $data = [
      'inventory'  => $inventory,
      'checklists' => $checklists,
      'rekap'      => $rekap,
      'ym'         => $ym,
      'nowYM'      => $nowYM,
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
    helper('checklist');

    // ================= INVENTORY =================
    $inventory = $this->inventoryModel
      ->select('
      compliance_inventory.*,
      asset_item_types.name AS item_display_name,
      asset_item_types.checklist_frequency
    ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory) {
      throw new \CodeIgniter\Exceptions\PageNotFoundException();
    }

    $frequency = $inventory['checklist_frequency'] ?? 'monthly';

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
      $defaultPeriodKey = $ym . '-01';
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

    // ================= LOCK =================
    $logModel = new \App\Models\ChecklistLogModel();

    $exists = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->first();

    $isLocked = false;
    $lockReason = null;

    if ($exists) {
      $isLocked = true;
      $lockReason = 'done';
    } elseif (is_period_future($frequency, $periodKey)) {
      $isLocked = true;
      $lockReason = 'future';
    } elseif (! is_period_editable($frequency, $periodKey)) {
      $isLocked = true;
      $lockReason = 'expired';
    }

    // ================= QUESTIONS =================
    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

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
    ]);
  }



  public function submitChecklist()
  {
    $inventoryId = $this->request->getPost('inventory_id');
    $periodKey   = $this->request->getPost('period_key');
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
      asset_item_types.checklist_frequency
    ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory) {
      return redirect()->back()->with('error', 'Inventory tidak ditemukan.');
    }

    $frequency = $inventory['checklist_frequency'] ?? 'monthly';

    $logModel = new ChecklistLogModel();

    // === LOCK PER INVENTORY + PERIOD ===
    $exists = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->first();

    if ($exists) {
      return redirect()->back()
        ->with('error', 'Checklist untuk periode ini sudah diisi.');
    }

    foreach ($questions as $templateId => $status) {

      // 🔥 MAPPING STATUS
      $statusDb = match ($status) {
        'ok' => 'ok',
        'ng' => 'not_ok',
        'na' => 'na',
        default => 'na'
      };

      $remarkValue = trim($remarks[$templateId] ?? '');
      $hasPhoto = isset($photos[$templateId]) && $photos[$templateId]->isValid();

      if ($status === 'ng' && $remarkValue === '' && ! $hasPhoto) {
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
        'status'                => $statusDb, // 🔥 INI YANG DIGANTI
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
      $defaultPeriodKey = $ym . '-01';
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


  private function isPeriodKeyValidForYM(
    string $frequency,
    string $periodKey,
    string $ym
  ): bool {
    if ($frequency === 'daily') {
      return str_starts_with($periodKey, $ym . '-');
    }

    if ($frequency === 'weekly') {
      return str_starts_with($periodKey, $ym . '-W');
    }

    // monthly
    return $periodKey === $ym;
  }
}
