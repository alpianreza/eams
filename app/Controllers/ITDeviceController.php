<?php

namespace App\Controllers;

use App\Models\ITDeviceModel;
use App\Models\AssetModel;
use Config\Database;

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
      $extra = json_decode($d['cpu'] ?? '{}', true) ?: [];
      $heartbeatInterval = max(10, (int)($extra['heartbeat_interval'] ?? (int) env('agent.defaultHeartbeatInterval', 600)));

      if (!device_is_online($d, $heartbeatInterval)) {
        if (!empty($d['last_seen'])) {
          $hours = (time() - strtotime($d['last_seen'])) / 3600;
          if ($hours > 24) $offline++;
        }
      }

      /* ===== NEED UPDATE ===== */
      $pendingUpdates = (int)($extra['pending'] ?? 0);

      if ($pendingUpdates > 5) {
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
    $perPage = in_array((int) $perPage, [20, 50, 100], true) ? (int) $perPage : 20;
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
    page('Detail Device IT');

    $device = $this->deviceModel->find($id);

    if (!$device) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    $asset = null;
    $assignment = null;

    if ($device['asset_id']) {
      $asset = (new AssetModel())->find($device['asset_id']);

      if ($asset) {
        $assignment = Database::connect()
          ->table('asset_assignments aa')
          ->select('e.name, e.employee_id, e.division, e.position, aa.assigned_at')
          ->join('employees e', 'e.id = aa.employee_id', 'left')
          ->where('aa.asset_id', (int) $asset['id'])
          ->where('aa.returned_at', null)
          ->orderBy('aa.assigned_at', 'DESC')
          ->get()
          ->getRowArray();
      }
    }

    $extra = json_decode($device['cpu'] ?? '{}', true) ?: [];
    $hw = $extra['hardware'] ?? [];
    $insights = $this->buildInsights($device, $extra);

    return view('it/devices/detail', [
      'device'     => $device,
      'asset'      => $asset,
      'assignment' => $assignment,
      'hw'         => $hw,
      'extra'      => $extra,
      'insights'   => $insights,
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
    $id = (int)$this->request->getPost('id');
    $action = strtolower(trim((string)$this->request->getPost('action')));
    $now = time();
    $normalInterval = max(60, (int) env('agent.defaultHeartbeatInterval', 600));
    $remoteInterval = max(10, (int) env('agent.remoteHeartbeatInterval', 10));
    $boostSeconds = max(60, (int) env('agent.remoteBoostSeconds', 180));
    $lockSeconds = max(10, (int) env('agent.remoteLockSeconds', 25));

    $allowed = ['restart', 'shutdown', 'update', 'sync', 'restart_agent', 'lock'];
    $actionLabelMap = [
      'restart' => 'Restart OS',
      'shutdown' => 'Shutdown OS',
      'update' => 'Push Update',
      'sync' => 'Sync Sekarang',
      'restart_agent' => 'Restart Agent',
      'lock' => 'Lock Screen',
    ];

    if ($id <= 0 || !in_array($action, $allowed, true)) {
      return $this->response->setJSON([
        'ok' => false,
        'message' => 'Aksi tidak valid'
      ]);
    }

    $device = $this->deviceModel->find($id);
    if (!$device) {
      return $this->response->setJSON([
        'ok' => false,
        'message' => 'Device tidak ditemukan'
      ]);
    }

    $extra = json_decode($device['cpu'] ?? '{}', true) ?: [];
    $lockUntil = (int)($extra['remote_lock_until'] ?? 0);
    $queuedCommand = trim((string)($extra['command'] ?? ''));

    if ($queuedCommand !== '') {
      return $this->response->setJSON([
        'ok' => false,
        'message' => 'Masih ada perintah remote yang belum diproses agent. Coba lagi setelah sinkronisasi berikutnya.',
      ]);
    }

    if ($lockUntil > $now) {
      $retryAfter = $lockUntil - $now;

      return $this->response->setJSON([
        'ok' => false,
        'message' => "Aksi remote dikunci sementara. Coba lagi dalam {$retryAfter} detik.",
        'retry_after' => $retryAfter,
        'lock_until' => $lockUntil,
      ]);
    }

    $extra['command'] = $action;
    $extra['heartbeat_boost_until'] = $now + $boostSeconds;
    $extra['heartbeat_boost_interval'] = $remoteInterval;
    $extra['heartbeat_normal_interval'] = $normalInterval;
    $extra['heartbeat_interval'] = $remoteInterval;
    $extra['remote_lock_until'] = $now + $lockSeconds;
    $extra['remote_lock_action'] = $action;
    $extra['last_remote_request_at'] = $now;

    if ($action === 'update') {
      $extra['force_update'] = true;
    }

    $this->deviceModel->update($id, [
      'cpu' => json_encode($extra)
    ]);

    return $this->response->setJSON([
      'ok' => true,
      'message' => "Perintah " . ($actionLabelMap[$action] ?? strtoupper($action)) . " berhasil diantrikan. Lock aktif {$lockSeconds} detik.",
      'lock_until' => $extra['remote_lock_until'],
      'heartbeat_interval' => $remoteInterval,
    ]);
  }

  private function buildInsights(array $device, array $extra): array
  {
    $insights = [];
    $heartbeatInterval = max(10, (int)($extra['heartbeat_interval'] ?? (int) env('agent.defaultHeartbeatInterval', 600)));

    $pendingUpdates = (int)($extra['pending'] ?? 0);
    if ($pendingUpdates >= 10) {
      $insights[] = [
        'tone' => 'warning',
        'title' => 'Patch keamanan tertunda',
        'body' => "Ada {$pendingUpdates} update yang belum terpasang. Jalankan Push Update agar risiko kerentanan menurun.",
      ];
    } elseif ($pendingUpdates > 0) {
      $insights[] = [
        'tone' => 'info',
        'title' => 'Masih ada update pending',
        'body' => "Terdeteksi {$pendingUpdates} update belum terpasang. Disarankan update di luar jam operasional.",
      ];
    }

    $activation = strtolower((string)($extra['activation'] ?? ''));
    if (in_array($activation, ['not_activated', 'not activated', 'inactive'], true)) {
      $insights[] = [
        'tone' => 'danger',
        'title' => 'Lisensi OS belum aktif',
        'body' => 'Aktivasi Windows belum valid. Pastikan lisensi resmi agar fitur keamanan tetap aktif.',
      ];
    }

    $storageTotal = (float)($device['storage_gb'] ?? 0);
    $storageFree = (float)($extra['storage_free'] ?? 0);
    if ($storageTotal > 0 && $storageFree > 0) {
      $freePercent = ($storageFree / $storageTotal) * 100;
      if ($freePercent < 15) {
        $insights[] = [
          'tone' => 'danger',
          'title' => 'Storage hampir penuh',
          'body' => 'Sisa storage kurang dari 15%. Bersihkan file besar agar performa sistem tetap stabil.',
        ];
      }
    }

    if (!device_is_online($device, $heartbeatInterval)) {
      $insights[] = [
        'tone' => 'secondary',
        'title' => 'Perangkat belum heartbeat',
        'body' => 'Device terdeteksi offline. Cek koneksi jaringan, agent service, atau endpoint heartbeat.',
      ];
    }

    $lockUntil = (int)($extra['remote_lock_until'] ?? 0);
    if ($lockUntil > time()) {
      $remaining = $lockUntil - time();
      $action = strtoupper((string)($extra['remote_lock_action'] ?? 'REMOTE'));
      $insights[] = [
        'tone' => 'info',
        'title' => 'Remote lock masih aktif',
        'body' => "Aksi {$action} sedang diproteksi lock. Sisa waktu sekitar {$remaining} detik.",
      ];
    }

    if (empty($insights)) {
      $insights[] = [
        'tone' => 'success',
        'title' => 'Kondisi perangkat stabil',
        'body' => 'Tidak ada indikator kritis saat ini. Lanjutkan monitoring berkala untuk menjaga performa.',
      ];
    }

    return $insights;
  }
}
