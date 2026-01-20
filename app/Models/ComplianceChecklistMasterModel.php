<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceChecklistMasterModel extends Model
{
  protected $table      = 'compliance_checklist_master';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'category',
    'code',
    'name',
    'period',
    'require_photo',
    'active'
  ];

  protected $useTimestamps = false;

  public function getByCategory(string $category)
  {
    return $this->where([
      'category' => $category,
      'active'   => 1
    ])->findAll();
  }
}
