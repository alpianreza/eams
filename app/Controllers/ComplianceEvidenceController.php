<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ChecklistLogModel;
use App\Models\ComplianceInventoryModel;
use App\Models\AssetItemTypeModel;

class ComplianceEvidenceController extends BaseController
{
  protected $logModel;
  protected $inventoryModel;
  protected $itemTypeModel;

  public function __construct()
  {
    $this->logModel       = new ChecklistLogModel();
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->itemTypeModel  = new AssetItemTypeModel();
  }

  public function index()
  {
    $data = [
      'itemTypes' => $this->itemTypeModel
        ->where('active', 1)
        ->findAll(),
      'title'     => 'Evidence Center'
    ];

    return view('compliance/evidence/index', $data);
  }

  public function detail($id)
  {
    try {

      $data['ev'] = $this->logModel
        ->select('checklist_logs.*, 
          compliance_inventory.asset_code,
          compliance_inventory.specific_area,
          asset_item_types.name as item_name')

        ->join(
          'compliance_inventory',
          'compliance_inventory.id = checklist_logs.inventory_id'
        )
        ->join(
          'asset_item_types',
          'asset_item_types.id = checklist_logs.item_type_id'
        )
        ->where('checklist_logs.id', $id)
        ->first();

      return view('compliance/evidence/_detail', $data);
    } catch (\Throwable $e) {
      return $e->getMessage(); // sementara debug
    }
  }

  public function getEvidenceAjax()
  {
    try {

      $year     = $this->request->getGet('year');
      $itemType = $this->request->getGet('item_type');
      $followUp = $this->request->getGet('follow_up'); // 🔥 ambil di awal

      $builder = $this->logModel
        ->select('checklist_logs.*,
        compliance_inventory.asset_code,
        compliance_inventory.specific_area,
        asset_item_types.name as item_name')
        ->join(
          'compliance_inventory',
          'compliance_inventory.id = checklist_logs.inventory_id'
        )
        ->join(
          'asset_item_types',
          'asset_item_types.id = checklist_logs.item_type_id'
        )
        ->where('checklist_logs.status', 'not_ok')
        ->where('checklist_logs.photo IS NOT NULL')
        ->where('checklist_logs.photo !=', '');

      if (!empty($year)) {
        $builder->where('YEAR(checklist_logs.check_date)', $year);
      }

      if (!empty($itemType)) {
        $builder->where('checklist_logs.item_type_id', $itemType);
      }

      // 🔥 FOLLOW UP FILTER HARUS DI SINI
      if (!empty($followUp)) {
        $builder->where('checklist_logs.follow_up_status', $followUp);
      }

      $data['evidences'] = $builder
        ->orderBy('checklist_logs.check_date', 'DESC')
        ->paginate(12);

      $data['pager'] = $builder->pager;

      return view('compliance/evidence/_grid', $data);
    } catch (\Throwable $e) {
      return $e->getMessage();
    }
  }

  public function updateFollowUp()
  {
    if (! $this->request->isAJAX()) {
      return redirect()->back();
    }

    try {

      $id = (int) $this->request->getPost('id');

      if (!$id) {
        return $this->response->setJSON([
          'status'  => 'error',
          'message' => 'ID tidak valid.'
        ]);
      }

      $data = [
        'follow_up_status' => $this->request->getPost('follow_up_status'),
        'follow_up_note'   => $this->request->getPost('follow_up_note'),
        'follow_up_date'   => date('Y-m-d')
      ];

      $this->logModel->update($id, $data);

      return $this->response->setJSON([
        'status'  => 'success',
        'message' => 'Status berhasil diperbarui.'
      ]);
    } catch (\Throwable $e) {

      return $this->response->setJSON([
        'status'  => 'error',
        'message' => $e->getMessage()
      ]);
    }
  }
}
