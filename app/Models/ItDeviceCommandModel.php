<?php

namespace App\Models;

use CodeIgniter\Model;

class ItDeviceCommandModel extends \CodeIgniter\Model
{
  protected $table = 'it_device_commands';
  protected $primaryKey = 'id';
  protected $returnType = 'array';
  protected $useTimestamps = true;
  protected $createdField = 'created_at';
  protected $updatedField = 'updated_at';
  protected $allowedFields = [
    'device_id',
    'command_id',
    'command',
    'payload_json',
    'status',
    'result',
    'requested_by',
    'requested_at',
    'executed_at',
    'created_at',
    'updated_at',
  ];
}
