<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\EamsPdf;
use App\Models\ComplianceInventoryModel;
use App\Models\ChecklistLogModel;
use App\Models\ChecklistMasterModel;
use App\Models\HolidayModel;

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

  /*
    |--------------------------------------------------------------------------
    | SINGLE ITEM EXPORT
    |--------------------------------------------------------------------------
    */
  public function single($inventoryId, $periodKey)
  {
    $inventory = $this->inventoryModel
      ->select('
        compliance_inventory.*,
        asset_item_types.name as item_name,
        asset_item_types.checklist_frequency
    ')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id', 'left')
      ->where('compliance_inventory.id', $inventoryId)
      ->first();


    if (!$inventory) {
      return redirect()->back()->with('error', 'Inventory tidak ditemukan.');
    }

    $questions = $this->masterModel
      ->where('item_type_id', $inventory['item_type_id'])
      ->orderBy('id', 'ASC')
      ->findAll();

    $logs = $this->logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->findAll();

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
      'title'       => 'CHECKLIST PENGECEKAN ' . strtoupper($inventory['item_name']),
      'itemName'    => $inventory['item_name'],
      'inventoryNo' => $inventory['asset_code'] ?? '-',
      'location'    => $inventory['specific_area'] ?? '-',
      'periodLabel' => function_exists('period_label')
        ? period_label($periodKey, $inventory['checklist_frequency'])
        : $periodKey,

      'questions'   => $finalQuestions,
      'filename'    => 'Checklist-' . ($inventory['asset_code'] ?? $inventoryId) . '.pdf',
    ];

    $pdf = new EamsPdf();
    return $pdf->export('single', $data);
  }

  /*
    |--------------------------------------------------------------------------
    | RECAP EXPORT (AUTO DAILY / WEEKLY)
    |--------------------------------------------------------------------------
    */
  public function recap($inventoryId, $year, $month)
  {
    $reportController = new \App\Controllers\ComplianceReportController();

    $data = $reportController->buildReportData($inventoryId, $year, $month);

    if (empty($data)) {
      return redirect()->back()->with('error', 'Data tidak ditemukan');
    }

    $frequency = $data['frequency'];

    switch ($frequency) {

      case 'daily':
        $type = 'daily';
        break;

      case 'weekly':
        $type = 'weekly';
        break;

      case 'monthly':
        $type = 'recap_year_item';
        break;

      default:
        return redirect()->back()->with('error', 'Frekuensi tidak didukung');
    }

    $data['inventoryNo'] = $data['inventory']['asset_code'] ?? '-';
    $data['filename'] = 'Checklist-' .
      $data['inventoryNo'] . '-' . $year . '-' . $month . '.pdf';

    $pdf = new \App\Libraries\EamsPdf();
    return $pdf->export($type, $data);
  }
}
