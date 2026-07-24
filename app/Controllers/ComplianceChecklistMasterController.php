<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InventoryCategoryModel;
use App\Models\AssetItemTypeModel;
use App\Models\ChecklistMasterModel;

class ComplianceChecklistMasterController extends BaseController
{
  protected $inventoryCategoryModel;
  protected $assetItemTypeModel;
  protected $checklistMasterModel;

  public function __construct()
  {
    $this->inventoryCategoryModel = new InventoryCategoryModel();
    $this->assetItemTypeModel     = new AssetItemTypeModel();
    $this->checklistMasterModel   = new ChecklistMasterModel();
  }

  /**
   * LEVEL 1
   * Index Checklist Master
   * Card Kategori
   * URL: /compliance/checklist/master
   */
  public function masterIndex()
  {
    $categories = $this->inventoryCategoryModel
      ->where('active', 1)
      ->orderBy('name')
      ->findAll();

    return view('compliance/checklist_master/index', [
      'categories' => $categories,
    ]);
  }

  /**
   * LEVEL 2
   * Card Item berdasarkan kategori
   * URL: /compliance/checklist/master/category/{id}
   */
  public function masterByCategory($categoryId)
  {
    $category = $this->inventoryCategoryModel->find($categoryId);

    if (! $category) {
      return redirect()->back();
    }

    $items = $this->assetItemTypeModel
      ->where('inventory_category_id', $categoryId)
      ->where('active', 1)
      ->orderBy('name')
      ->findAll();

    return view('compliance/checklist_master/items', [
      'category' => $category,
      'items'    => $items,
    ]);
  }

  /**
   * LEVEL 3
   * Detail Item (Pertanyaan Checklist)
   * URL: /compliance/checklist/master/item/{id}
   */
  public function masterItem($itemTypeId)
  {
    $item = $this->assetItemTypeModel
      ->select('id, name, inventory_category_id, checklist_frequency')
      ->find($itemTypeId);

    if (! $item) {
      return redirect()->back();
    }

    $questions = $this->checklistMasterModel
      ->where('item_type_id', $itemTypeId)
      ->orderBy('id')
      ->findAll();

    // ambil frequency dari item
    $frequency = $item['checklist_frequency'];

    return view('compliance/checklist_master/detail', [
      'item'      => $item,
      'questions' => $questions,
      'frequency' => $frequency
    ]);
  }


  /**
   * STORE PERTANYAAN CHECKLIST
   * AJAX only
   */
  public function store()
  {
    $itemTypeId = (int) $this->request->getPost('item_type_id');
    $question = trim((string) $this->request->getPost('question'));

    if ($itemTypeId <= 0 || $question === '') {
      if ($this->request->isAJAX()) {
        return $this->response->setJSON([
          'status' => 'error',
          'message' => 'Data pertanyaan tidak valid.',
        ]);
      }
      return redirect()->back()->with('error', 'Data pertanyaan tidak valid.');
    }

    $this->checklistMasterModel->insert([
      'item_type_id' => $itemTypeId,
      'question'     => $question,
      'require_photo' => $this->request->getPost('require_photo') ? 1 : 0,
      'active'       => $this->request->getPost('active') ? 1 : 0,
    ]);

    if ($this->request->isAJAX()) {
      return $this->response->setJSON(['status' => 'success']);
    }

    return redirect()->back();
  }

  /**
   * UPDATE PERTANYAAN CHECKLIST
   * AJAX only
   */
  public function update($id)
  {
    $question = trim((string) $this->request->getPost('question'));
    if ($question === '') {
      if ($this->request->isAJAX()) {
        return $this->response->setJSON([
          'status' => 'error',
          'message' => 'Pertanyaan tidak boleh kosong.',
        ]);
      }
      return redirect()->back()->with('error', 'Pertanyaan tidak boleh kosong.');
    }

    $this->checklistMasterModel->update($id, [
      'question'      => $question,
      'require_photo' => $this->request->getPost('require_photo') ? 1 : 0,
      'active'        => $this->request->getPost('active') ? 1 : 0,
    ]);

    if ($this->request->isAJAX()) {
      return $this->response->setJSON(['status' => 'success']);
    }

    return redirect()->back();
  }

  public function updateItemFrequency($itemTypeId)
  {
    $frequency = $this->request->getPost('frequency');

    if (! in_array($frequency, ['daily', 'weekly', 'monthly'])) {
      return $this->response->setJSON([
        'status' => 'error',
        'message' => 'Frekuensi tidak valid'
      ]);
    }

    $this->assetItemTypeModel->update($itemTypeId, [
      'checklist_frequency' => $frequency
    ]);

    return $this->response->setJSON([
      'status' => 'success'
    ]);
  }

  public function exportPeriodePage()
  {
    return view('checklist/export_periode');
  }

  public function delete($id)
  {
    if ($this->request->isAJAX()) {

      $this->checklistMasterModel->delete($id);

      return $this->response->setJSON([
        'status' => 'success'
      ]);
    }

    return redirect()->back();
  }
}
