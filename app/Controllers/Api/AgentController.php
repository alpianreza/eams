<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ITDeviceModel;
use App\Models\AssetModel;

class AgentController extends BaseController
{
  protected $deviceModel;
  protected $assetModel;

  public function __construct()
  {
    $this->deviceModel = new ITDeviceModel();
    $this->assetModel = new AssetModel();
  }

  public function heartbeat()
  {
    $data = $this->request->getJSON(true);

    if (!$data) {
      return $this->response->setJSON([
        'status' => 'error',
        'message' => 'invalid payload'
      ]);
    }

    // ===== TOKEN =====
    $deviceToken = $data['device_token'] ?? null;

    if (!$deviceToken) {
      $deviceToken = bin2hex(random_bytes(16));
    }

    // ===== FIND DEVICE =====
    $device = null;

    if ($deviceToken) {
      $device = $this->deviceModel
        ->where('device_token', $deviceToken)
        ->first();
    }

    if (!$device && !empty($data['mac'])) {
      $device = $this->deviceModel
        ->where('mac_address', $data['mac'])
        ->first();
    }

    // ===== PAYLOAD =====
    $payload = [
      'hostname'          => $data['hostname'] ?? null,
      'manufacturer'      => $data['manufacturer'] ?? null,
      'model'             => $data['model'] ?? null,
      'bios'              => $data['bios'] ?? null,
      'device_user'       => $data['device_user'] ?? null,
      'os'                => $data['os'] ?? null,
      'os_version'        => $data['os_version'] ?? null,
      'cpu_name'          => $data['cpu_name'] ?? null,
      'cpu_core'          => $data['cpu_core'] ?? null,
      'cpu_thread'        => $data['cpu_thread'] ?? null,
      'gpu'               => $data['gpu'] ?? null,
      'disk_model'        => $data['disk_model'] ?? null,
      'architecture'      => $data['architecture'] ?? null,
      'ram_gb'            => $data['ram_gb'] ?? null,
      'storage_gb'        => $data['storage_gb'] ?? null,
      'storage_free'      => $data['storage_free'] ?? null,
      'cpu_usage'         => $data['cpu_usage'] ?? null,
      'ram_usage'         => $data['ram_usage'] ?? null,
      'last_ip'           => $this->request->getIPAddress(),
      'mac_address'       => $data['mac'] ?? null,
      'agent_version'     => $data['agent_version'] ?? null,
      'last_update_check' => date('Y-m-d H:i:s'),
      'last_seen'         => date('Y-m-d H:i:s'),
      'status'            => 'online',
      'device_token'      => $deviceToken
    ];

    $old = $device ? json_decode($device['cpu'] ?? '{}', true) : [];

    $extra = [
      'os_edition' => $data['os_edition'] ?? $old['os_edition'] ?? null,
      'os_build'   => $data['os_build'] ?? $old['os_build'] ?? null,
      'os_release' => $data['os_release'] ?? $old['os_release'] ?? null,
      'activation' => $data['activation_status'] ?? $old['activation'] ?? null,
      'pending'    => $data['pending_updates'] ?? $old['pending'] ?? null,
      'hardware'   => $data['hardware'] ?? $old['hardware'] ?? [],
      'force_update' => $old['force_update'] ?? false
    ];

    $payload['cpu'] = json_encode($extra);

    // ===== INSERT / UPDATE =====
    if ($device) {

      $this->deviceModel->update($device['id'], $payload);
      $deviceId = $device['id'];
    } else {

      $this->deviceModel->insert($payload);
      $deviceId = $this->deviceModel->getInsertID();

      // auto asset
      $assetData = [
        'inventory_no' => $this->generateInventoryNo(),
        'category_id'  => 1,
        'asset_name'   => $payload['hostname'],
        'brand'        => $payload['manufacturer'] ?? null,
        'serial_number' => $data['serial_number'] ?? null,
        'status'       => 'aktif'
      ];

      $this->assetModel->insert($assetData);
      $assetId = $this->assetModel->getInsertID();

      $this->deviceModel->update($deviceId, [
        'asset_id' => $assetId
      ]);
    }

    // ===== COMMAND SYSTEM =====
    $device = $this->deviceModel->find($deviceId);

    // ================= COMMAND HANDLER =================
    $command = null;

    if ($device) {

      if (!empty($device['command'])) {
        $command = $device['command'];

        // reset command setelah dikirim
        $this->deviceModel->update($device['id'], [
          'command' => null
        ]);
      }
    }

    return $this->response->setJSON([
      'status' => 'ok',
      'device_token' => $deviceToken,
      'command' => $command
    ]);
  }

  public function agentUpdate()
  {
    $data = $this->request->getJSON(true);

    $device = $this->deviceModel
      ->where('device_token', $data['device_token'])
      ->first();

    if (!$device) {
      return $this->response->setJSON(['update' => false]);
    }

    // ================= FORCE UPDATE =================
    $extra = json_decode($device['cpu'] ?? '{}', true);

    if (($extra['force_update'] ?? false) === true) {

      $extra['force_update'] = false;

      $this->deviceModel->update($device['id'], [
        'cpu' => json_encode($extra)
      ]);

      return $this->response->setJSON([
        'update' => true,
        'url' => base_url('downloads/agent/EAMSAgent-1.1.0.exe'),
        'version' => '1.2.0'
      ]);
    }

    // ================= FALLBACK GLOBAL UPDATE (INI BAGIANNYA) =================
    $latest = '1.2.0';

    if ($device['agent_version'] != $latest) {
      return $this->response->setJSON([
        'update' => true,
        'url' => base_url("downloads/agent/EAMSAgent-$latest.exe"),
        'version' => $latest
      ]);
    }

    return $this->response->setJSON(['update' => false]);
  }

  public function pushUpdate()
  {
    $id = $this->request->getPost('id');

    $device = $this->deviceModel->find($id);
    if (!$device) return $this->response->setJSON(['ok' => false]);

    $extra = json_decode($device['cpu'] ?? '{}', true);
    $extra['force_update'] = true;

    $this->deviceModel->update($id, [
      'cpu' => json_encode($extra)
    ]);

    return $this->response->setJSON(['ok' => true]);
  }

  private function generateInventoryNo()
  {
    $last = $this->assetModel
      ->like('inventory_no', 'IT-PC-', 'after')
      ->orderBy('id', 'DESC')
      ->first();

    if (!$last) return 'IT-PC-001';

    preg_match('/IT-PC-(\d+)/', $last['inventory_no'], $m);
    $num = isset($m[1]) ? (int)$m[1] + 1 : 1;

    return 'IT-PC-' . str_pad($num, 3, '0', STR_PAD_LEFT);
  }
}
