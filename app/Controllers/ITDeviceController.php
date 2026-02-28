<?php

namespace App\Controllers;

use App\Models\ITDeviceModel;
use App\Models\AssetModel;

class ITDeviceController extends BaseController
{
  protected $deviceModel;

  public function __construct()
  {
    $this->deviceModel = new ITDeviceModel();
  }

  public function index()
  {
    helper('device');

    $devices = $this->deviceModel->findAll();

    $total = 0;
    $healthy = 0;
    $warning = 0;
    $critical = 0;
    $offline = 0;
    $update = 0;

    foreach ($devices as $d) {

      $total++;

      /* ===== RISK SCORE ===== */
      $score = device_risk_score($d);

      if ($score >= 80) {
        $healthy++;
      } elseif ($score >= 50) {
        $warning++;
      } else {
        $critical++;
      }

      /* ===== OFFLINE ===== */
      if (!device_is_online($d)) {
        if (!empty($d['last_seen'])) {
          $hours = (time() - strtotime($d['last_seen'])) / 3600;
          if ($hours > 24) $offline++;
        }
      }

      /* ===== NEED UPDATE ===== */
      if (($d['pending_updates'] ?? 0) > 5) {
        $update++;
      }
    }

    $kpi = compact(
      'total',
      'healthy',
      'warning',
      'critical',
      'offline',
      'update'
    );

    page('Device Control Center');

    return view('it/devices/index', [
      'kpi' => $kpi
    ]);
  }
  public function ajax()
  {
    helper(['os_lifecycle', 'device']);

    $perPage = $this->request->getGet('perPage') ?? 20;
    $keyword = $this->request->getGet('q');

    $builder = $this->deviceModel;

    if ($keyword) {
      $builder->groupStart()
        ->like('hostname', $keyword)
        ->orLike('device_user', $keyword)
        ->orLike('os', $keyword)
        ->groupEnd();
    }

    $devices = $builder
      ->orderBy('last_seen', 'DESC')
      ->paginate($perPage);

    return view('it/devices/_table', [
      'devices' => $devices,
      'pager'   => $this->deviceModel->pager
    ]);
  }

  public function detail($id)
  {
    helper(['device', 'os_lifecycle']); // helper yang dipakai view

    $device = $this->deviceModel->find($id);

    if (!$device) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    $asset = null;

    if ($device['asset_id']) {
      $asset = (new AssetModel())->find($device['asset_id']);
    }

    $extra = json_decode($device['cpu'] ?? '{}', true);
    $hw = $extra['hardware'] ?? [];

    return view('it/devices/detail', [
      'device' => $device,
      'asset'  => $asset,
      'hw'     => $hw
    ]);
  }

  public function sendCommand()
  {
    $id = $this->request->getPost('id');
    $cmd = $this->request->getPost('cmd');

    $device = $this->deviceModel->find($id);
    if (!$device) return $this->response->setJSON(['ok' => false]);

    $extra = json_decode($device['cpu'] ?? '{}', true);
    $extra['command'] = $cmd;

    $this->deviceModel->update($id, [
      'cpu' => json_encode($extra)
    ]);

    return $this->response->setJSON(['ok' => true]);
  }

  public function remoteAction()
  {
    $id = $this->request->getPost('id');
    $action = $this->request->getPost('action');

    $allowed = ['restart', 'shutdown', 'update'];

    if (!in_array($action, $allowed)) {
      return $this->response->setJSON(['ok' => false]);
    }

    $this->deviceModel->update($id, [
      'command' => $action
    ]);

    return $this->response->setJSON(['ok' => true]);
  }
}
