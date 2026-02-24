<?php

namespace App\Models;

use CodeIgniter\Model;

class ItDeviceLogModel extends Model
{
  protected $table = 'it_device_logs';

  protected $allowedFields = [
    'device_id',
    'ip_address',
    'cpu_usage',
    'ram_usage',
    'storage_free',
    'logged_at'
  ];
}
