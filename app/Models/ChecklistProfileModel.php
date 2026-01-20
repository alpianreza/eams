<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistProfileModel extends Model
{
  protected $table      = 'compliance_checklist_profiles';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'category_id',
    'name',
    'frequency'
  ];

  protected $useTimestamps = true;

  public function getByCategory($categoryId)
  {
    return $this->where('category_id', $categoryId)
      ->orderBy('name', 'ASC')
      ->findAll();
  }
}
