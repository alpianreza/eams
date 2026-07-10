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
  protected $allowedFollowUps = ['open', 'monitoring', 'closed'];

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
      'title' => 'Evidence Center',
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
        ->where('checklist_logs.id', (int) $id)
        ->first();

      return view('compliance/evidence/_detail', $data);
    } catch (\Throwable $e) {
      log_message('error', 'ComplianceEvidenceController::detail failed: {message}', [
        'message' => $e->getMessage(),
      ]);

      return $this->response
        ->setStatusCode(500)
        ->setBody('Terjadi kesalahan saat memuat detail evidence.');
    }
  }

  public function getEvidenceAjax()
  {
    try {
      $year = $this->request->getGet('year');
      $itemType = $this->request->getGet('item_type');
      $followUp = strtolower(trim((string) $this->request->getGet('follow_up')));

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

      if (is_numeric($year)) {
        $builder->where('YEAR(checklist_logs.check_date)', (int) $year);
      }

      if (is_numeric($itemType)) {
        $builder->where('checklist_logs.item_type_id', (int) $itemType);
      }

      if (in_array($followUp, $this->allowedFollowUps, true)) {
        $builder->where('checklist_logs.follow_up_status', $followUp);
      }

      $data['evidences'] = $builder
        ->orderBy('checklist_logs.check_date', 'DESC')
        ->paginate(12);

      $data['pager'] = $builder->pager;

      return view('compliance/evidence/_grid', $data);
    } catch (\Throwable $e) {
      log_message('error', 'ComplianceEvidenceController::getEvidenceAjax failed: {message}', [
        'message' => $e->getMessage(),
      ]);

      return $this->response
        ->setStatusCode(500)
        ->setBody('Terjadi kesalahan saat memuat evidence.');
    }
  }

  public function updateFollowUp()
  {
    helper('audit');

    if (! $this->request->isAJAX()) {
      return redirect()->back();
    }

    try {
      $id = (int) $this->request->getPost('id');
      if (!$id) {
        return $this->response->setJSON([
          'status' => 'error',
          'message' => 'ID tidak valid.',
        ]);
      }

      $status = strtolower(trim((string) $this->request->getPost('follow_up_status')));
      if (!in_array($status, $this->allowedFollowUps, true)) {
        return $this->response->setJSON([
          'status' => 'error',
          'message' => 'Status tindak lanjut tidak valid.',
        ]);
      }

      $exists = $this->logModel->find($id);
      if (!$exists) {
        return $this->response->setJSON([
          'status' => 'error',
          'message' => 'Data evidence tidak ditemukan.',
        ]);
      }

      $followUpNote = trim((string) $this->request->getPost('follow_up_note'));
      $safeFollowUpNote = function_exists('mb_substr')
        ? mb_substr($followUpNote, 0, 1000)
        : substr($followUpNote, 0, 1000);

      $data = [
        'follow_up_status' => $status,
        'follow_up_note' => $safeFollowUpNote,
        'follow_up_date' => date('Y-m-d'),
      ];

      $this->logModel->update($id, $data);

      helper('audit');
      audit_log('evidence_update_followup', 'Status tindak lanjut evidence ID: ' . $id . ' diubah ke: ' . $status);

      return $this->response->setJSON([
        'status' => 'success',
        'message' => 'Status berhasil diperbarui.',
      ]);
    } catch (\Throwable $e) {
      log_message('error', 'ComplianceEvidenceController::updateFollowUp failed: {message}', [
        'message' => $e->getMessage(),
      ]);

      return $this->response->setJSON([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memperbarui status.',
      ]);
    }
  }
}
