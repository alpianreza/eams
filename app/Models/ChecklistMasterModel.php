<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistMasterModel extends Model
{
  protected $table = 'checklist_master';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'item_type_id',
    'question',
    'frequency',
    'require_photo',
    'active'
  ];

  public function getByItemType($itemTypeId)
  {
    return $this->where([
      'item_type_id' => $itemTypeId,
      'active'       => 1
    ])->findAll();
  }
}
