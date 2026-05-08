<?php

namespace App\Models;

use CodeIgniter\Model;

class PdamWaterLogModel extends Model
{
  protected $table = 'pdam_water_logs';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'log_date',
    'log_time',
    'meter_reading',
    'note',
    'created_by',
    'created_at',
    'updated_at',
  ];

  protected $useTimestamps = true;
}
