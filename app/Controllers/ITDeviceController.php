<?php

namespace App\Controllers;

use App\Models\ITDeviceModel;
use App\Models\AssetModel;
use App\Models\ItDeviceCommandModel;
use Config\Database;

class ITDeviceController extends BaseController
{
  protected $deviceModel;
  protected $commandModel;

  public function __construct()
  {
    $this->deviceModel = new ITDeviceModel();
    $this->commandModel = new ItDeviceCommandModel();
  }

  public function index()
  {
    helper('device');

    $kpi = $this->calculateKpi();

    page('Device Control Center');

    return view('it/devices/index', [
      'kpi' => $kpi
    ]);
  }

  public function stats()
  {
    helper('device');

    return $this->response->setJSON([
      'ok' => true,
      'kpi' => $this->calculateKpi(),
      'generated_at' => time(),
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

    $this->deviceModel->pager->setPath('it/devices');

    return view('it/devices/_table', [
      'devices' => $devices,
      'pager'   => $this->deviceModel->pager
    ]);
  }

  public function detailFragment($id)
  {
    helper(['device', 'os_lifecycle']);

    $payload = $this->buildDetailPayload((int) $id);
    if ($payload === null) {
      return $this->response->setStatusCode(404)->setBody('Device tidak ditemukan');
    }

    return view('it/devices/_detail_content', $payload);
  }

  public function detail($id)
  {
    helper(['device', 'os_lifecycle']); // helper yang dipakai view
    page('Detail Device IT');

    $payload = $this->buildDetailPayload((int) $id);
    if ($payload === null) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    return view('it/devices/detail', [
      ...$payload,
    ]);
  }

  public function sendCommand()
  {
    $id = (int) $this->request->getPost('id');
    $cmd = strtolower(trim((string) $this->request->getPost('cmd')));

    if ($id <= 0 || $cmd === '') {
      return $this->response->setJSON(['ok' => false, 'message' => 'Perintah tidak valid']);
    }

    $device = $this->deviceModel->find($id);
    if (!$device) {
      return $this->response->setJSON(['ok' => false, 'message' => 'Device tidak ditemukan']);
    }

    $queued = $this->queueRemoteCommand($device, $cmd);

    return $this->response->setJSON($queued);
  }

  public function remoteAction()
  {
    $id = (int)$this->request->getPost('id');
    $action = strtolower(trim((string)$this->request->getPost('action')));
    $allowed = [
      'restart' => 'Restart OS',
      'shutdown' => 'Shutdown OS',
      'update' => 'Push Update',
      'sync' => 'Sync Sekarang',
      'restart_agent' => 'Restart Agent',
      'lock' => 'Lock Screen',
      'logoff' => 'Log Off User',
      'popup_message' => 'Kirim Pesan',
    ];

    if ($id <= 0 || !array_key_exists($action, $allowed)) {
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

    $args = $this->buildRemoteCommandArgs($action);
    if (isset($args['__error'])) {
      return $this->response->setJSON([
        'ok' => false,
        'message' => $args['__error'],
      ]);
    }

    $queued = $this->queueRemoteCommand($device, $action, $args, [
      'force_update' => $action === 'update',
      'action_label' => $allowed[$action],
    ]);

    if (!$queued['ok']) {
      return $this->response->setJSON($queued);
    }

    return $this->response->setJSON($queued);
  }

  private function buildInsights(array $device, array $extra): array
  {
    $insights = [];
    $heartbeatInterval = max(86400, (int)($extra['heartbeat_interval'] ?? (int) env('agent.defaultHeartbeatInterval', 86400)));

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

    $lastCommandResult = is_array($extra['last_command_result'] ?? null) ? $extra['last_command_result'] : [];
    if (!empty($lastCommandResult['name'])) {
      $resultStatus = strtolower((string)($lastCommandResult['status'] ?? ''));
      $insights[] = [
        'tone' => $resultStatus === 'success' ? 'success' : ($resultStatus === 'error' ? 'danger' : 'info'),
        'title' => 'Hasil aksi terakhir: ' . strtoupper(str_replace('_', ' ', (string)($lastCommandResult['name'] ?? 'remote'))),
        'body' => trim((string)($lastCommandResult['message'] ?? 'Perintah sudah dieksekusi agent.')),
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

  private function calculateKpi(): array
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
      $score = device_risk_score($d);

      if ($score >= 80) {
        $healthy++;
      } elseif ($score >= 50) {
        $warning++;
      } else {
        $critical++;
      }

      $extra = json_decode($d['cpu'] ?? '{}', true) ?: [];
      $heartbeatInterval = max(86400, (int)($extra['heartbeat_interval'] ?? (int) env('agent.defaultHeartbeatInterval', 86400)));

      if (!device_is_online($d, $heartbeatInterval) && !empty($d['last_seen'])) {
        $hours = (time() - strtotime($d['last_seen'])) / 3600;
        if ($hours > 24) {
          $offline++;
        }
      }

      if ((int)($extra['pending'] ?? 0) > 5) {
        $update++;
      }
    }

    return compact('total', 'healthy', 'warning', 'critical', 'offline', 'update');
  }

  private function buildDetailPayload(int $id): ?array
  {
    $device = $this->deviceModel->find($id);
    if (!$device) {
      return null;
    }

    $asset = null;
    $assignment = null;

    if (!empty($device['asset_id'])) {
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
    $commandHistory = $this->recentCommandHistory((int) $device['id']);

    return [
      'device' => $device,
      'asset' => $asset,
      'assignment' => $assignment,
      'hw' => $hw,
      'extra' => $extra,
      'insights' => $insights,
      'commandHistory' => $commandHistory,
    ];
  }

  private function buildRemoteCommandArgs(string $action): array
  {
    if ($action === 'popup_message') {
      $message = trim((string) $this->request->getPost('message'));
      $title = trim((string) $this->request->getPost('title'));
      $timeout = (int) $this->request->getPost('timeout');

      if ($message === '') {
        return ['__error' => 'Isi pesan tidak boleh kosong'];
      }

      return [
        'title' => $title !== '' ? $title : 'Pesan dari Tim IT',
        'message' => $message,
        'timeout' => max(15, min(300, $timeout > 0 ? $timeout : 90)),
      ];
    }

    return [];
  }

  private function queueRemoteCommand(array $device, string $action, array $args = [], array $options = []): array
  {
    $now = time();
    $extra = json_decode($device['cpu'] ?? '{}', true) ?: [];
    $lockUntil = (int)($extra['remote_lock_until'] ?? 0);
    $queuedCommand = $this->queuedCommandName($extra['command'] ?? null);

    if ($queuedCommand !== '') {
      return [
        'ok' => false,
        'message' => 'Masih ada perintah remote yang belum diproses agent. Coba lagi setelah sinkronisasi berikutnya.',
      ];
    }

    if ($lockUntil > $now) {
      $retryAfter = $lockUntil - $now;

      return [
        'ok' => false,
        'message' => "Aksi remote dikunci sementara. Coba lagi dalam {$retryAfter} detik.",
        'retry_after' => $retryAfter,
        'lock_until' => $lockUntil,
      ];
    }

    $commandId = $this->generateCommandId();
    $actionLabel = trim((string)($options['action_label'] ?? strtoupper($action)));
    $forceUpdate = (bool)($options['force_update'] ?? false);
    $normalInterval = max(86400, (int) env('agent.defaultHeartbeatInterval', 86400));
    $remoteInterval = max($normalInterval, (int) env('agent.remoteHeartbeatInterval', 86400));
    $boostSeconds = max(60, (int) env('agent.remoteBoostSeconds', 180));
    $lockSeconds = max(10, (int) env('agent.remoteLockSeconds', 25));

    $extra['command'] = [
      'id' => $commandId,
      'name' => $action,
      'args' => $args,
      'queued_at' => date(DATE_ATOM, $now),
    ];
    $extra['heartbeat_boost_until'] = 0;
    $extra['heartbeat_boost_interval'] = $normalInterval;
    $extra['heartbeat_normal_interval'] = $normalInterval;
    $extra['heartbeat_interval'] = $normalInterval;
    $extra['command_poll_interval'] = max(5, (int) env('agent.commandPollInterval', 5));
    $extra['remote_lock_until'] = $now + $lockSeconds;
    $extra['remote_lock_action'] = $action;
    $extra['last_remote_request_at'] = $now;

    if ($forceUpdate) {
      $extra['force_update'] = true;
    }

    $this->deviceModel->update((int) $device['id'], [
      'cpu' => json_encode($extra, JSON_UNESCAPED_UNICODE),
    ]);

    $this->recordCommandQueue((int) $device['id'], $commandId, $action, $args);

    $push = $this->pushRemoteCommandToAgent($device, $extra['command']);
    if (!empty($push['ok'])) {
      $this->markCommandDispatchedLocal((int) $device['id'], $commandId);
      $this->clearQueuedRemoteCommand((int) $device['id'], $commandId);

      return [
        'ok' => true,
        'message' => "Perintah {$actionLabel} dikirim langsung ke agent.",
        'delivery' => 'push',
        'push_target' => $push['target'] ?? null,
        'lock_until' => $extra['remote_lock_until'],
        'heartbeat_interval' => $normalInterval,
        'command_id' => $commandId,
      ];
    }

    return [
      'ok' => true,
      'message' => "Perintah {$actionLabel} diantrikan. Push langsung gagal, fallback polling agent aktif.",
      'delivery' => 'queued',
      'push_error' => $push['message'] ?? null,
      'lock_until' => $extra['remote_lock_until'],
      'heartbeat_interval' => $normalInterval,
      'command_id' => $commandId,
    ];
  }

  private function pushRemoteCommandToAgent(array $device, array $command): array
  {
    $token = trim((string)($device['device_token'] ?? ''));
    if ($token === '') {
      return ['ok' => false, 'message' => 'Device token kosong'];
    }

    $port = max(1, min(65535, (int) env('agent.pushPort', 8765)));
    $timeout = max(1, min(10, (int) env('agent.pushTimeout', 2)));
    $payload = [
      'device_token' => $token,
      'command' => $command,
    ];

    foreach ($this->agentPushTargets($device, $port) as $target) {
      $result = $this->postJsonToAgent($target, $payload, $timeout);
      if (!empty($result['ok'])) {
        return ['ok' => true, 'target' => $target, 'response' => $result['body'] ?? null];
      }
    }

    return ['ok' => false, 'message' => 'Agent push endpoint tidak merespons'];
  }

  private function agentPushTargets(array $device, int $port): array
  {
    $extra = json_decode($device['cpu'] ?? '{}', true) ?: [];
    $candidates = [
      $extra['lan_ip'] ?? null,
      $device['last_ip'] ?? null,
      $extra['request_ip'] ?? null,
    ];

    $targets = [];
    foreach ($candidates as $candidate) {
      $ip = trim((string)$candidate);
      if ($ip === '' || $ip === '127.0.0.1' || $ip === '0.0.0.0' || stripos($ip, '::1') !== false) {
        continue;
      }
      if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        continue;
      }
      $targets[] = "http://{$ip}:{$port}/command";
    }

    return array_values(array_unique($targets));
  }

  private function postJsonToAgent(string $url, array $payload, int $timeout): array
  {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if (function_exists('curl_init')) {
      $ch = curl_init($url);
      curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
      ]);
      $responseBody = curl_exec($ch);
      $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
      $error = curl_error($ch);
      curl_close($ch);

      return [
        'ok' => $statusCode >= 200 && $statusCode < 300,
        'status' => $statusCode,
        'body' => $responseBody,
        'message' => $error,
      ];
    }

    $context = stream_context_create([
      'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $body,
        'timeout' => $timeout,
        'ignore_errors' => true,
      ],
    ]);
    $responseBody = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/\s(\d{3})\s/', $statusLine, $matches);
    $statusCode = (int)($matches[1] ?? 0);

    return [
      'ok' => $statusCode >= 200 && $statusCode < 300,
      'status' => $statusCode,
      'body' => $responseBody,
    ];
  }

  private function markCommandDispatchedLocal(int $deviceId, string $commandId): void
  {
    if (!$this->commandLogTableExists()) {
      return;
    }

    $existing = $this->commandModel
      ->where('device_id', $deviceId)
      ->where('command_id', $commandId)
      ->first();

    if (!$existing) {
      return;
    }

    $status = strtolower(trim((string)($existing['status'] ?? 'queued')));
    if (in_array($status, ['success', 'error'], true)) {
      return;
    }

    $this->commandModel->update((int)$existing['id'], ['status' => 'dispatched']);
  }

  private function clearQueuedRemoteCommand(int $deviceId, string $commandId): void
  {
    $device = $this->deviceModel->find($deviceId);
    if (!$device) {
      return;
    }

    $extra = json_decode($device['cpu'] ?? '{}', true) ?: [];
    $command = is_array($extra['command'] ?? null) ? $extra['command'] : [];
    if (trim((string)($command['id'] ?? '')) !== $commandId) {
      return;
    }

    $extra['command'] = null;
    $this->deviceModel->update($deviceId, [
      'cpu' => json_encode($extra, JSON_UNESCAPED_UNICODE),
    ]);
  }

  private function queuedCommandName($commandPayload): string
  {
    if (is_array($commandPayload)) {
      return strtolower(trim((string)($commandPayload['name'] ?? $commandPayload['command'] ?? '')));
    }

    return strtolower(trim((string)$commandPayload));
  }

  private function generateCommandId(): string
  {
    try {
      return bin2hex(random_bytes(12));
    } catch (\Throwable $e) {
      return uniqid('cmd_', true);
    }
  }

  private function recordCommandQueue(int $deviceId, string $commandId, string $command, array $args = []): void
  {
    if (!$this->commandLogTableExists()) {
      return;
    }

    $requestedBy = trim((string)(session('name') ?? session('username') ?? 'System'));

    $this->commandModel->insert([
      'device_id' => $deviceId,
      'command_id' => $commandId,
      'command' => $command,
      'payload_json' => !empty($args) ? json_encode($args, JSON_UNESCAPED_UNICODE) : null,
      'status' => 'queued',
      'requested_by' => $requestedBy !== '' ? $requestedBy : 'System',
      'requested_at' => date('Y-m-d H:i:s'),
    ]);
  }

  private function recentCommandHistory(int $deviceId): array
  {
    if (!$this->commandLogTableExists()) {
      return [];
    }

    return $this->commandModel
      ->where('device_id', $deviceId)
      ->orderBy('id', 'DESC')
      ->findAll(12);
  }

  private function commandLogTableExists(): bool
  {
    static $exists = null;

    if ($exists !== null) {
      return $exists;
    }

    $exists = Database::connect()->tableExists('it_device_commands');
    return $exists;
  }
}
