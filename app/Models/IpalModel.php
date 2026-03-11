<?php

namespace App\Models;

use CodeIgniter\Model;

class IpalModel extends Model
{
  protected $table = 'ipal_logs';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'log_date',
    'start_meter',
    'stop_meter',
    'volume',
    'pemakaian',
    'ket',
    'created_by'
  ];

  protected $useTimestamps = true;
}
