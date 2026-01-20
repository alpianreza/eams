<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceChecklistLogModel extends Model
{
  protected $table      = 'compliance_checklist_logs';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'inventory_id',
    'checklist_profile_id',
    'frequency',
    'inspection_date',
    'inspection_week',
    'inspection_month',
    'inspection_year',
    'is_holiday',
    'checked_by',
    'created_at'
  ];

  protected $useTimestamps = true;
}
