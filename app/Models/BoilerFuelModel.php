<?php

namespace App\Models;

use CodeIgniter\Model;

class BoilerFuelModel extends Model
{
  protected $table = 'boiler_fuel_logs';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'log_date',
    'log_time',
    'polybag',
    'kg',
    'note',
    'created_by',
    'created_at',
    'updated_at'
  ];

  protected $useTimestamps = true;
}
