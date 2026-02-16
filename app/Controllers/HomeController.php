<?php

class HomeController extends BaseController
{
  protected $inventoryModel;
  protected $logModel;

  public function __construct()
  {
    $this->inventoryModel = new \App\Models\ComplianceInventoryModel();
    $this->logModel = new \App\Models\ChecklistLogModel();
  }

  public function index()
  {
    $userName = session('name');

    $inventories = $this->inventoryModel
      ->select('compliance_inventory.*, asset_item_types.checklist_frequency')
      ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
      ->where('pic', $userName)
      ->findAll();

    $summary = [
      'total' => 0,
      'pending' => 0,
      'late' => 0,
      'not_ok' => 0,
    ];

    $pendingList = [];

    foreach ($inventories as $inv) {

      $summary['total']++;

      $periodKey = get_active_period_key($inv['checklist_frequency']);

      $log = $this->logModel
        ->where('inventory_id', $inv['id'])
        ->where('period_key', $periodKey)
        ->first();

      if (!$log) {
        $summary['pending']++;
        $pendingList[] = $inv;
        continue;
      }

      if (is_period_late($log['check_date'], $inv['checklist_frequency'])) {
        $summary['late']++;
      }

      if ($log['status'] === 'not_ok') {
        $summary['not_ok']++;
      }
    }

    return view('home/index', [
      'summary' => $summary,
      'pendingList' => $pendingList
    ]);
  }
}
