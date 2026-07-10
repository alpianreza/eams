<?php

namespace App\Controllers;

use App\Models\AssetItemTypeModel;
use App\Models\InventoryCategoryModel;

class ComplianceItemTypeController extends BaseController
{
  protected $itemTypeModel;
  protected $categoryModel;

  public function __construct()
  {
    $this->itemTypeModel = new AssetItemTypeModel();
    $this->categoryModel = new InventoryCategoryModel();
  }

  public function create()
  {
    return view('compliance/item_type/create', [
      'categories' => $this->categoryModel
        ->where('active', 1)
        ->orderBy('name', 'ASC')
        ->findAll()
    ]);
  }

  public function store()
  {
    helper('audit');

    $this->itemTypeModel->insert([
      'inventory_category_id' => $this->request->getPost('inventory_category_id'),
      'name'                  => $this->request->getPost('name'),
      'code'                  => $this->request->getPost('code'),
      'active'                => 1,
    ]);

    audit_log('item_type_create', 'Menambah item type: ' . $this->request->getPost('name') . ' kode ' . $this->request->getPost('code'));

    return redirect()
      ->to(site_url('compliance/checklist/master'))
      ->with('success', 'Item berhasil ditambahkan');
  }
}
