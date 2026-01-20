<?php

namespace App\Controllers;

use App\Models\ComplianceInventoryModel;
use App\Models\InventoryCategoryModel;
use App\Models\AreaModel;
use App\Models\AssetItemTypeModel;

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

    if (! $inventory) {
      throw new \CodeIgniter\Exceptions\PageNotFoundException('Inventory tidak ditemukan');
    }

    return view('compliance/inventory/detail', [
      'inventory' => $inventory
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
}
