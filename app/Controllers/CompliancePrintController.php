<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class CompliancePrintController extends BaseController
{
  public function index()
  {
    if (!hasRole(['admin', 'compliance', 'auditor'])) {
      return redirect()->back();
    }

    $data = [
      'title' => 'Print Center'
    ];

    return view('compliance/print/index', $data);
  }

  public function item()
  {
    $db = \Config\Database::connect();

    $itemTypes = $db->table('compliance_inventory')
      ->distinct()
      ->select('asset_item_types.id, asset_item_types.name, asset_item_types.checklist_frequency')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->orderBy('asset_item_types.name', 'ASC')
      ->get()
      ->getResultArray();

    return view('compliance/print/item', [
      'itemTypes' => $itemTypes
    ]);
  }

  public function itemPreview()
  {
    $inventoryIds = explode(',', $this->request->getGet('inventory'));
    $years  = explode(',', $this->request->getGet('year'));
    $months = explode(',', $this->request->getGet('month'));

    $inventoryModel = new \App\Models\ComplianceInventoryModel();
    $itemTypeModel  = new \App\Models\AssetItemTypeModel();

    $inventories = $inventoryModel
      ->whereIn('id', $inventoryIds)
      ->findAll();

    $data = [
      'inventories' => $inventories,
      'years' => $years,
      'months' => $months
    ];

    return view('compliance/print/preview', $data);
  }

  public function inventoryByType($itemTypeId)
  {
    $inventoryModel = new \App\Models\ComplianceInventoryModel();

    $inventories = $inventoryModel
      ->select('id, asset_code, specific_area')
      ->where('item_type_id', $itemTypeId)
      ->orderBy('asset_code', 'ASC')
      ->findAll();

    return view('compliance/print/_inventory_list', [
      'inventories' => $inventories
    ]);
  }
}
