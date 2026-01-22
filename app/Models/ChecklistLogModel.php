<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistLogModel extends Model
{
  protected $table = 'checklist_logs';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'inventory_id',
    'item_type_id',
    'checklist_template_id',
    'check_date',
    'period_key',
    'status',
    'remark',
    'photo',
    'checked_by',
    'created_at'
  ];

  protected $useTimestamps = false;
}
