<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceChecklistLogModel extends Model
{
  protected $table      = 'compliance_checklist_logs';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'inventory_id',
    'item_type_id',
    'frequency',
    'inspection_date',
    'inspection_week',
    'inspection_month',
    'inspection_year',
    'checked_by',
    'created_at'
  ];

  protected $useTimestamps = true;

  /**
   * Cek apakah checklist untuk periode ini sudah ada
   */
  public function alreadyChecked($inventoryId, $frequency, $date)
  {
    $builder = $this->where('inventory_id', $inventoryId)
      ->where('frequency', $frequency);

    if ($frequency === 'daily') {
      $builder->where('inspection_date', $date);
    }

    if ($frequency === 'weekly') {
      $builder->where('inspection_week', date('W', strtotime($date)))
        ->where('inspection_year', date('Y', strtotime($date)));
    }

    if ($frequency === 'monthly') {
      $builder->where('inspection_month', date('m', strtotime($date)))
        ->where('inspection_year', date('Y', strtotime($date)));
    }

    return $builder->countAllResults() > 0;
  }
}
