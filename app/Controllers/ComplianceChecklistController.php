<?php

namespace App\Controllers;

use App\Models\ComplianceInventoryModel;
use App\Models\ChecklistMasterModel;
use App\Models\ChecklistLogModel;

class ComplianceChecklistController extends BaseController
{
  protected $inventoryModel;
  protected $checklistLogModel;
  protected $checklistMasterModel;

  public function __construct()
  {
    $this->inventoryModel = new ComplianceInventoryModel();
    $this->checklistLogModel = new ChecklistLogModel();
    $this->checklistMasterModel = new ChecklistMasterModel();
  }

  public function index($inventoryId)
  {
    // =============================
    // AMBIL INVENTORY + ITEM TYPE
    // =============================
    $inventory = (new ComplianceInventoryModel())
      ->select('
                compliance_inventory.*,
                asset_item_types.name AS item_display_name
            ')
      ->join(
        'asset_item_types',
        'asset_item_types.id = compliance_inventory.item_type_id',
        'left'
      )
      ->where('compliance_inventory.id', $inventoryId)
      ->first();

    if (!$inventory) {
      throw new \CodeIgniter\Exceptions\PageNotFoundException('Inventory tidak ditemukan');
    }

    // =============================
    // TENTUKAN PERIODE AKTIF
    // =============================
    // default: daily
    $period = 'daily';

    // jika ingin weekly (contoh: hari Senin)
    if (date('N') == 1) {
      $period = 'weekly';
    }

    // jika tanggal 1 → monthly
    if (date('j') == 1) {
      $period = 'monthly';
    }

    // =============================
    // AMBIL PERTANYAAN CHECKLIST
    // =============================
    $questions = (new ChecklistMasterModel())
      ->where('item_type_id', $inventory['item_type_id'])
      ->where('frequency', $period)
      ->where('active', 1)
      ->orderBy('id', 'ASC')
      ->findAll();

    // =============================
    // KIRIM KE VIEW
    // =============================
    return view('compliance/checklist/index', [
      'inventory' => $inventory,
      'period'    => $period,
      'questions' => $questions
    ]);
  }

  public function store()
  {
    $logModel = new \App\Models\ChecklistLogModel();

    $inventoryId = $this->request->getPost('inventory_id');
    $itemTypeId  = $this->request->getPost('item_type_id');
    $periodKey   = $this->request->getPost('period_key');
    $checkDate   = $this->request->getPost('check_date');
    $statuses    = $this->request->getPost('status'); // array

    foreach ($statuses as $templateId => $status) {
      $logModel->insert([
        'inventory_id'          => $inventoryId,
        'item_type_id'          => $itemTypeId,
        'checklist_template_id' => $templateId,
        'check_date'            => $checkDate,
        'period_key'            => $periodKey,
        'status'                => $status,
        'checked_by'            => session()->get('username')
      ]);
    }

    return redirect()
      ->to('/compliance/inventory/detail/' . $inventoryId)
      ->with('success', 'Checklist berhasil disimpan');
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

    // 🔒 DEFAULT AMAN (ANTI ERROR)
    $frequency  = 'daily';              // nanti kita buat dinamis
    $period_key = date('Y-m-d');         // key sementara
    $questions  = [];                   // aman walau kosong

    return view('compliance/checklist/index', [
      'inventory'  => $inventory,
      'frequency'  => $frequency,
      'period_key' => $period_key,
      'questions'  => $questions
    ]);
  }
}
