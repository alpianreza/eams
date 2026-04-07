<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceQuestionnaireModel extends Model
{
  protected $table = 'compliance_questionnaires';
  protected $primaryKey = 'id';
  protected $allowedFields = [
    'slug',
    'title',
    'subtitle',
    'description',
    'footer_note',
    'collect_name',
    'collect_phone',
    'collect_email',
    'active',
    'sort_order',
  ];
  protected $useTimestamps = true;
  protected $createdField = 'created_at';
  protected $updatedField = 'updated_at';
}
