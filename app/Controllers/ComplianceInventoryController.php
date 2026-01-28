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
      areas.name AS area_name
    ')
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
      MAX(checked_by) as checked_by,
      MAX(status) as status
    ')
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
    $rekap = (new \App\Models\ChecklistLogModel())
      ->select("
      COUNT(DISTINCT period_key) as total,
      SUM(status = 'ok') as ok_count,
      SUM(status = 'ng') as ng_count,
      SUM(
        status = 'ok'
        AND created_at > DATE_ADD(check_date, INTERVAL 1 DAY)
      ) as late_count
    ")
      ->where('inventory_id', $id)
      ->like('period_key', $ym, 'after')
      ->first();

    return view('compliance/inventory/detail', [
      'inventory'  => $inventory,
      'checklists' => $checklists,
      'rekap'      => $rekap,
      'ym'         => $ym,
    ]);
  }


  public function updatePhoto($id)
  {
    $inventory = $this->inventoryModel->find($id);
    if (! $inventory) {
      return redirect()->back()->with('error', 'Data tidak ditemukan');
    }

    $photo = $this->request->getFile('photo');
    if ($photo && $photo->isValid() && ! $photo->hasMoved()) {

      if (!empty($inventory['photo']) && file_exists(FCPATH . 'uploads/inventory/' . $inventory['photo'])) {
        unlink(FCPATH . 'uploads/inventory/' . $inventory['photo']);
      }

      $newName = $photo->getRandomName();
      $photo->move(FCPATH . 'uploads/inventory', $newName);

      $this->inventoryModel->update($id, [
        'photo' => $newName
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
    // === INVENTORY ===
    $inventory = $this->inventoryModel
      ->select('
            compliance_inventory.*,
            asset_item_types.name AS item_display_name
        ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (! $inventory) {
      throw new \CodeIgniter\Exceptions\PageNotFoundException();
    }

    // === FREQUENCY ===
    $checklistMasterModel = new \App\Models\ChecklistMasterModel();

    $frequencyRow = $checklistMasterModel
      ->select('frequency')
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('active', 1)
      ->groupBy('frequency')
      ->first();

    if (! $frequencyRow) {
      return redirect()->back()
        ->with('error', 'Checklist belum diatur untuk item ini.');
    }

    $frequency = $frequencyRow['frequency'];

    // =====================================================
    // MONTH NAVIGATION (YYYY-MM) — SUMBER KEBENARAN BULAN
    // =====================================================
    $ym = $this->request->getGet('ym');

    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m'); // fallback aman
    }

    [$year, $month] = array_map('intval', explode('-', $ym));

    // batas awal
    $minYM  = '2026-01';
    $prevYM = date('Y-m', strtotime("$ym -1 month"));
    $nextYM = date('Y-m', strtotime("$ym +1 month"));

    $canPrev = $prevYM >= $minYM;
    $canNext = true; // navigasi bebas

    // =====================================================
    // PERIOD KEY (URL > DEFAULT)
    // =====================================================
    $requestPeriodKey = $this->request->getGet('period_key');

    if ($requestPeriodKey) {
      // pakai period_key dari klik
      $periodKey = $requestPeriodKey;
    } else {
      // DEFAULT SELALU IKUT BULAN NAVIGASI
      if ($frequency === 'daily') {
        $periodKey = $ym . '-01';
      } elseif ($frequency === 'weekly') {
        $periodKey = $ym . '-W1';
      } else { // monthly
        $periodKey = $ym;
      }
    }


    // === BARU DI SINI HITUNG LABEL ===
    $periodLabel = period_label($frequency, $periodKey);

    // =====================================================
    // GENERATE CALENDAR (PAKAI BULAN NAVIGASI)
    // =====================================================
    $periods = generate_calendar_periods(
      $frequency,
      $year,
      $month
    );

    foreach ($periods as &$p) {
      $p['allowed'] = ! is_period_future($frequency, $p['period_key']);
      $p['status']  = resolve_period_status(
        $inventory['id'],
        $frequency,
        $p['period_key']
      );
    }
    unset($p);

    // =====================================================
    // LOCK PER INVENTORY + PERIOD
    // =====================================================
    $logModel = new \App\Models\ChecklistLogModel();
    $isLocked = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->first() ? true : false;

    // === QUESTIONS ===
    $questions = $checklistMasterModel
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('frequency', $frequency)
      ->where('active', 1)
      ->findAll();

    // =====================================================
    // RESPONSE
    // =====================================================

    // AJAX → render partial (calendar + form)
    if ($this->request->isAJAX() || $this->request->getGet('ajax') == '1') {
      return view('compliance/checklist/_calendar', [
        'inventory'    => $inventory,
        'questions'    => $questions,
        'frequency'    => $frequency,
        'period_key'   => $periodKey,
        'period_label' => $periodLabel,
        'isLocked'     => $isLocked,
        'periods'      => $periods,
        'navYM'        => $ym,
        'prevYM'       => $prevYM,
        'nextYM'       => $nextYM,
        'canPrev'      => $canPrev,
        'canNext'      => $canNext,
      ]);
    }

    // NORMAL → render halaman full
    return view('compliance/checklist/index', [
      'inventory'    => $inventory,
      'questions'    => $questions,
      'frequency'    => $frequency,
      'period_key'   => $periodKey,
      'period_label' => $periodLabel,
      'isLocked'     => $isLocked,
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
    $frequency   = $this->request->getPost('frequency');
    $itemTypeId  = $this->request->getPost('item_type_id');
    $questions   = $this->request->getPost('questions');
    $remarks     = $this->request->getPost('remarks') ?? [];
    $photos      = $this->request->getFiles()['photos'] ?? [];
    $user        = session()->get('name');

    if (! is_array($questions)) {
      return redirect()->back()
        ->with('error', 'Checklist tidak valid.');
    }

    $logModel = new ChecklistLogModel();

    // === LOCK ===
    $exists = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->first();

    if ($exists) {
      return redirect()->back()
        ->with('error', 'Checklist untuk periode ini sudah diisi.');
    }

    foreach ($questions as $templateId => $status) {

      if ($status === 'ng') {
        if (empty($remarks[$templateId])) {
          return redirect()->back()
            ->with('error', 'Checklist NOT OK wajib diisi catatan.');
        }

        if (!isset($photos[$templateId]) || !$photos[$templateId]->isValid()) {
          return redirect()->back()
            ->with('error', 'Checklist NOT OK wajib disertai foto.');
        }
      }

      $photoName = null;
      if (isset($photos[$templateId]) && $photos[$templateId]->isValid()) {
        $photoName = $photos[$templateId]->getRandomName();
        $photos[$templateId]->move(FCPATH . 'uploads/checklist', $photoName);
      }

      $logModel->insert([
        'inventory_id'          => $inventoryId,
        'item_type_id'          => $itemTypeId,
        'checklist_template_id' => $templateId,
        'check_date'            => date('Y-m-d'),
        'period_key'            => $periodKey,
        'status'                => $status,
        'remark'                => $remarks[$templateId] ?? null,
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

    $ym        = $this->request->getGet('ym');
    $frequency = $this->request->getGet('frequency');

    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      return $this->response->setStatusCode(400);
    }

    [$year, $month] = array_map('intval', explode('-', $ym));

    $inventory = $this->inventoryModel->find($inventoryId);
    if (! $inventory) {
      return $this->response->setStatusCode(404);
    }

    $periods = generate_calendar_periods($frequency, $year, $month);

    foreach ($periods as &$p) {
      $p['allowed'] = ! is_period_future($frequency, $p['period_key']);
      $p['status']  = resolve_period_status(
        $inventoryId,
        $frequency,
        $p['period_key']
      );
    }
    unset($p);

    return view('compliance/checklist/_calendar', [
      'inventoryId' => $inventoryId,
      'periods'     => $periods,
      'frequency'   => $frequency,
      'ym'          => $ym
    ]);
  }
}
