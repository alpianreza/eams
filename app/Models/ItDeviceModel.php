<?php

namespace App\Models;

use CodeIgniter\Model;

class ITDeviceModel extends Model
{
  protected $table = 'it_devices';
  protected $primaryKey = 'id';

  protected $allowedFields = [
    'asset_id',
    'hostname',
    'manufacturer',
    'model',
    'bios',
    'device_user',
    'os',
    'os_version',
    'cpu',
    'cpu_name',
    'cpu_core',
    'cpu_thread',
    'gpu',
    'disk_model',
    'architecture',
    'ram',
    'ram_gb',
    'storage',
    'storage_gb',
    'last_ip',
    'mac_address',
    'agent_version',
    'last_update_check',
    'last_seen',
    'status',
    'device_token'
  ];
}
