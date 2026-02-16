<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\EamsPdf;
use App\Models\ComplianceInventoryModel;
use App\Models\ChecklistLogModel;
use App\Models\ChecklistMasterModel;

class ExportPdfController extends BaseController
{
  protected $inventoryModel;
  protected $logModel;
  protected $masterModel;

  public function __construct()
  {
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->logModel       = new ChecklistLogModel();
    $this->masterModel    = new ChecklistMasterModel();
  }

  /**
   * EXPORT SINGLE ITEM
   */
  public function single($inventoryId, $periodKey)
  {
    // Ambil inventory + item info
    $inventory = $this->inventoryModel
      ->select('
                compliance_inventory.*,
                asset_item_types.name as item_name
            ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (!$inventory) {
      return redirect()->back()->with('error', 'Inventory tidak ditemukan.');
    }

    // Ambil pertanyaan
    $questions = $this->masterModel
      ->where('item_type_id', $inventory['item_type_id'])
      ->orderBy('id', 'ASC')
      ->findAll();

    // Ambil log periode
    $logs = $this->logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->findAll();

    // Mapping log by template
    $logMap = [];
    foreach ($logs as $log) {
      $logMap[$log['checklist_template_id']] = $log;
    }

    $finalQuestions = [];
    foreach ($questions as $q) {
      $log = $logMap[$q['id']] ?? null;

      $finalQuestions[] = [
        'question' => $q['question'],
        'status'   => $log['status'] ?? null,
      ];
    }

    $data = [
      'title'        => 'CHECKLIST PENGECEKAN ' . strtoupper($inventory['item_name']),
      'itemName'     => $inventory['item_name'],
      'inventoryNo'  => $inventory['inventory_no'], // sesuai keputusan: hanya ini
      'location'     => $inventory['specific_area'],
      'periodLabel'  => $periodKey, // nanti bisa pakai helper period_label
      'questions'    => $finalQuestions,
      'filename'     => 'Checklist-' . $inventory['inventory_no'] . '.pdf',
    ];

    $pdf = new EamsPdf();
    return $pdf->export('single', $data);
  }
}
