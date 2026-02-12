<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ChecklistLogModel;
use App\Models\ComplianceInventoryModel;
use App\Models\AssetItemTypeModel;
use App\Models\AreaModel;

class ComplianceEvidenceController extends BaseController
{
  protected $logModel;
  protected $inventoryModel;
  protected $itemTypeModel;
  protected $areaModel;

  public function __construct()
  {
    $this->logModel = new ChecklistLogModel();
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->itemTypeModel = new AssetItemTypeModel();
    $this->areaModel = new AreaModel();
  }

  public function index()
  {
    $data = [
      'itemTypes' => $this->itemTypeModel->findAll(),
      'areas' => $this->areaModel->findAll(),
      'title' => 'Evidence Center'
    ];

    return view('compliance/evidence/index', $data);
  }

  public function getEvidenceAjax()
  {
    $year = $this->request->getGet('year');
    $itemType = $this->request->getGet('item_type');
    $area = $this->request->getGet('area');

    $builder = $this->logModel
      ->select('checklist_logs.*, inventories.no_inventaris, areas.name as area_name, asset_item_types.name as item_name')
      ->join('inventories', 'inventories.id = checklist_logs.inventory_id')
      ->join('areas', 'areas.id = inventories.area_id')
      ->join('asset_item_types', 'asset_item_types.id = checklist_logs.item_type_id')
      ->where('checklist_logs.status', 'not_ok')
      ->where('checklist_logs.photo IS NOT NULL');

    if ($year) {
      $builder->where('YEAR(check_date)', $year);
    }

    if ($itemType) {
      $builder->where('checklist_logs.item_type_id', $itemType);
    }

    if ($area) {
      $builder->where('inventories.area_id', $area);
    }

    $data['evidences'] = $builder->orderBy('check_date', 'DESC')
      ->paginate(12);

    $data['pager'] = $builder->pager;

    return view('compliance/evidence/_grid', $data);
  }
}
