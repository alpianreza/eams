<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use App\Models\ItDeviceModel;

class DeviceStatusCheck extends BaseCommand
{
  protected $group = 'IT';
  protected $name = 'it:status';
  protected $description = 'Update device online/offline status';

  public function run(array $params)
  {
    $model = new ItDeviceModel();

    $devices = $model->findAll();

    $now = time();

    foreach ($devices as $d) {

      if (!$d['last_seen']) continue;

      $diff = $now - strtotime($d['last_seen']);

      $status = $diff > 600 ? 'offline' : 'online';

      if ($d['status'] !== $status) {
        $model->update($d['id'], ['status' => $status]);
      }
    }

    echo "Device status updated\n";
  }
}
