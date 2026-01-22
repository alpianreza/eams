<?php

namespace App\Controllers;

use App\Models\ComplianceInventoryModel;
use App\Models\InventoryCategoryModel;
use App\Models\AreaModel;


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

    $category = $request->getGet('category');
    $area     = $request->getGet('area');
    $keyword  = $request->getGet('q');
    $perPage  = $request->getGet('perPage') ?? 20;

    $query = $this->inventoryModel
      ->select('
            compliance_inventory.*,
            inventory_categories.name AS category_name,
            asset_item_types.name AS item_display_name,
            areas.name AS area_name
        ')
      ->join('inventory_categories', 'inventory_categories.id = compliance_inventory.category_id')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->join('areas', 'areas.id = compliance_inventory.area_id');

    // FILTER KATEGORI
    if ($category) {
      $query->where('inventory_categories.name', $category);
    }

    // FILTER AREA
    if ($area) {
      $query->where('areas.name', $area);
    }

    // SEARCH (PAKAI NAMA ITEM SEBENARNYA)
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
      'category'    => $category,
      'area'        => $area,
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
      ->join('inventory_categories', 'inventory_categories.id = compliance_inventory.category_id')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->join('areas', 'areas.id = compliance_inventory.area_id')
      ->where('compliance_inventory.id', $id)
      ->first();

    if (!$inventory) {
      throw new \CodeIgniter\Exceptions\PageNotFoundException('Inventory tidak ditemukan');
    }

    // ✅ SUMMARY CHECKLIST PER PERIODE
    $checklists = (new \App\Models\ChecklistLogModel())
      ->select('
            check_date,
            period_key,
            checked_by,
            CASE
              WHEN SUM(status = "ng") > 0 THEN "ng"
              ELSE "ok"
            END AS final_status
        ')
      ->where('inventory_id', $id)
      ->groupBy('period_key, check_date, checked_by')
      ->orderBy('check_date', 'DESC')
      ->findAll();

    return view('compliance/inventory/detail', [
      'inventory'  => $inventory,
      'checklists' => $checklists
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

  private function generatePeriod(string $frequency): array
  {
    $today = date('Y-m-d');

    if ($frequency === 'daily') {
      return [
        'period_key' => $today,
        'label'      => $today
      ];
    }

    if ($frequency === 'weekly') {
      return [
        'period_key' => date('o-\WW'), // contoh: 2026-W04
        'label'      => 'Week ' . date('W') . ' ' . date('Y')
      ];
    }

    if ($frequency === 'monthly') {
      return [
        'period_key' => date('Y-m'),
        'label'      => date('F Y') // January 2026
      ];
    }

    throw new \Exception('Invalid checklist frequency');
  }


  public function checklist($inventoryId)
  {
    $inventory = $this->inventoryModel
      ->select('
            compliance_inventory.*,
            asset_item_types.name AS item_display_name
        ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (!$inventory) {
      throw new \CodeIgniter\Exceptions\PageNotFoundException();
    }
    $checklistMasterModel = new \App\Models\ChecklistMasterModel();

    $frequencyRow = $checklistMasterModel
      ->select('frequency')
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('active', 1)
      ->groupBy('frequency')
      ->first();

    if (!$frequencyRow) {
      return redirect()->back()
        ->with('error', 'Checklist belum diatur untuk item ini.');
    }

    $frequency = $frequencyRow['frequency'];

    // === GENERATE PERIOD KEY (SEMENTARA, FIXED LOGIC) ===
    $today = date('Y-m-d');
    if ($frequency === 'daily') {
      $periodKey = $today;
    } elseif ($frequency === 'weekly') {
      $periodKey = date('o-\WW'); // contoh: 2026-W04
    } else { // monthly
      $periodKey = date('Y-m'); // 2026-01
    }

    // === CEK SUDAH ADA CHECKLIST ATAU BELUM ===
    $logModel = new \App\Models\ChecklistLogModel();
    $exists = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->first();

    $isLocked = $exists ? true : false;

    // === AMBIL PERTANYAAN SESUAI ITEM TYPE & FREQUENCY ===
    $questions = (new \App\Models\ChecklistMasterModel())
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('frequency', $frequency)
      ->where('active', 1)
      ->findAll();

    return view('compliance/checklist/index', [
      'inventory'  => $inventory,
      'questions'  => $questions,
      'frequency'  => $frequency,
      'period_key' => $periodKey,
      'isLocked'   => $isLocked
    ]);
  }



  public function submitChecklist()
  {
    $inventoryId = $this->request->getPost('inventory_id');
    $periodKey   = $this->request->getPost('period_key');
    $frequency   = $this->request->getPost('frequency');
    $itemTypeId  = $this->request->getPost('item_type_id');
    $questions   = $this->request->getPost('questions');

    $user = session()->get('username') ?? 'system';

    $logModel = new \App\Models\ChecklistLogModel();

    // 🔒 LOCK PER PERIODE
    $exists = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->first();

    if ($exists) {
      return redirect()->back()
        ->with('error', 'Checklist periode ini sudah diisi.');
    }

    foreach ($questions as $templateId => $answer) {
      $logModel->insert([
        'inventory_id'         => $inventoryId,
        'item_type_id'         => $itemTypeId,
        'checklist_template_id' => $templateId,
        'check_date'           => date('Y-m-d'),
        'period_key'           => $periodKey,
        'status'               => $answer, // ok / not_ok
        'checked_by'           => $user,
        'created_at'           => date('Y-m-d H:i:s')
      ]);
    }

    return redirect()
      ->to(base_url('compliance/inventory/detail/' . $inventoryId))
      ->with('success', 'Checklist berhasil disimpan.');
  }
}
