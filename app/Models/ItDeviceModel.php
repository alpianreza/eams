<?php

namespace App\Models;

use CodeIgniter\Model;

class ItDeviceModel extends \CodeIgniter\Model
{
  protected $table = 'it_devices';
  protected $allowedFields = [
    'asset_id',
    'hostname',
    'device_user',
    'os',
    'os_version',
    'cpu',
    'ram_gb',
    'storage_gb',
    'last_ip',
    'mac_address',
    'agent_version',
    'last_seen',
    'status'
  ];
}
