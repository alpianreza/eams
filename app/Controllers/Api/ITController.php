<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ItDeviceModel;
use App\Models\ItDeviceLogModel;
use App\Models\AssetModel;

class ITController extends BaseController
{
  public function heartbeat()
  {
    $data = $this->request->getJSON(true);
    log_message('error', 'HEARTBEAT HIT: ' . json_encode($data));

    if (!$data) {
      return $this->response->setJSON([
        'status' => 'error',
        'message' => 'no payload'
      ]);
    }

    $deviceModel = new ItDeviceModel();
    $logModel    = new ItDeviceLogModel();
    $assetModel  = new AssetModel();

    // =========================
    // FIND OR CREATE ASSET (AUTO REGISTER)
    // =========================
    // =========================
    // FIND OR CREATE ASSET (AUTO REGISTER)
    // =========================
    $asset = $assetModel
      ->where('serial_number', $data['mac'])
      ->first();

    if (!$asset) {

      // generate inventory number sederhana
      $inventoryNo = 'IT-' . strtoupper(substr(md5($data['mac']), 0, 6));

      $assetId = $assetModel->insert([
        'inventory_no'  => $inventoryNo,
        'category_id'   => 2, // nanti kita bikin config, sementara default
        'asset_name'    => $data['hostname'] ?? 'Unknown Device',
        'brand'         => $data['cpu'] ?? null,
        'serial_number' => $data['mac'],
        'status'        => 'aktif',
        'location'      => 'IT Room'
      ]);
    } else {
      $assetId = $asset['id'];
    }
    // =========================
    // FIND OR CREATE DEVICE
    // =========================
    $device = $deviceModel
      ->where('asset_id', $assetId)
      ->first();

    $deviceData = [
      'asset_id'      => $assetId,
      'hostname'      => $data['hostname'] ?? null,
      'device_user'   => $data['device_user'] ?? null,
      'os'            => $data['os'] ?? null,
      'os_version'    => $data['os_version'] ?? null,
      'cpu'           => $data['cpu'] ?? null,
      'ram_gb'        => $data['ram_gb'] ?? null,
      'storage_gb'    => $data['storage_gb'] ?? null,
      'last_ip'       => $data['ip'] ?? null,
      'mac_address'   => $data['mac'] ?? null,
      'agent_version' => $data['agent_version'] ?? null,
      'last_seen'     => date('Y-m-d H:i:s'),
      'status'        => 'online'
    ];

    if (!$device) {
      $deviceId = $deviceModel->insert($deviceData);
    } else {
      $deviceModel->update($device['id'], $deviceData);
      $deviceId = $device['id'];
    }

    // =========================
    // INSERT LOG
    // =========================
    $logModel->insert([
      'device_id'    => $deviceId,
      'ip_address'   => $data['ip'] ?? null,
      'cpu_usage'    => $data['cpu_usage'] ?? null,
      'ram_usage'    => $data['ram_usage'] ?? null,
      'storage_free' => $data['storage_free'] ?? null,
      'logged_at'    => date('Y-m-d H:i:s')
    ]);

    return $this->response->setJSON([
      'status' => 'ok',
      'asset_id' => $assetId
    ]);
  }
}
