<?php

namespace App\Models;

use CodeIgniter\Model;

class ItDeviceCommandModel extends \CodeIgniter\Model
{
  protected $table = 'it_device_commands';
  protected $allowedFields = [
    'device_id',
    'command',
    'status',
    'result',
    'requested_by'
  ];
}
