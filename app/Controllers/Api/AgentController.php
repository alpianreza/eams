<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ITDeviceModel;
use App\Models\AssetModel;
use Config\Database;

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
        $data = $this->resolvePayload();
        $method = strtoupper($this->request->getMethod());

        if (empty($data)) {
            if ($method === 'GET') {
                return $this->response->setJSON([
                    'status' => 'ok',
                    'message' => 'Agent API aktif',
                    'server_time' => date(DATE_ATOM),
                ]);
            }

            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'invalid payload',
            ]);
        }

        [$device, $deviceToken] = $this->resolveDeviceAndToken($data);

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
            'last_ip'           => $this->request->getIPAddress(),
            'mac_address'       => $data['mac'] ?? null,
            'agent_version'     => $data['agent_version'] ?? null,
            'last_update_check' => date('Y-m-d H:i:s'),
            'last_seen'         => date('Y-m-d H:i:s'),
            'status'            => 'online',
            'device_token'      => $deviceToken,
        ];

        $oldExtra = $device ? $this->decodeExtra($device['cpu'] ?? null) : [];

        $extra = [
            'os_edition'   => $data['os_edition'] ?? $oldExtra['os_edition'] ?? null,
            'os_build'     => $data['os_build'] ?? $oldExtra['os_build'] ?? null,
            'os_release'   => $data['os_release'] ?? $oldExtra['os_release'] ?? null,
            'activation'   => $data['activation_status'] ?? $oldExtra['activation'] ?? null,
            'pending'      => $data['pending_updates'] ?? $oldExtra['pending'] ?? null,
            'storage_free' => $data['storage_free'] ?? $oldExtra['storage_free'] ?? null,
            'cpu_usage'    => $data['cpu_usage'] ?? $oldExtra['cpu_usage'] ?? null,
            'ram_usage'    => $data['ram_usage'] ?? $oldExtra['ram_usage'] ?? null,
            'health'       => $data['health'] ?? $oldExtra['health'] ?? [],
            'last_sync_status' => $data['last_sync_status'] ?? $oldExtra['last_sync_status'] ?? null,
            'last_sync_at' => $data['last_sync_at'] ?? $oldExtra['last_sync_at'] ?? null,
            'heartbeat_interval' => $data['heartbeat_interval'] ?? $data['interval'] ?? $oldExtra['heartbeat_interval'] ?? $this->defaultHeartbeatInterval(),
            'heartbeat_boost_until' => $oldExtra['heartbeat_boost_until'] ?? 0,
            'heartbeat_boost_interval' => $oldExtra['heartbeat_boost_interval'] ?? $this->remoteHeartbeatInterval(),
            'heartbeat_normal_interval' => $oldExtra['heartbeat_normal_interval'] ?? $this->defaultHeartbeatInterval(),
            'remote_lock_until' => $oldExtra['remote_lock_until'] ?? 0,
            'remote_lock_action' => $oldExtra['remote_lock_action'] ?? null,
            'last_remote_request_at' => $oldExtra['last_remote_request_at'] ?? null,
            'lan_ip'       => $data['lan_ip'] ?? $oldExtra['lan_ip'] ?? null,
            'hardware'     => $data['hardware'] ?? $oldExtra['hardware'] ?? [],
            'force_update' => $oldExtra['force_update'] ?? false,
            'command'      => $oldExtra['command'] ?? null,
        ];

        $resolvedHeartbeatInterval = $this->resolveHeartbeatInterval($extra);
        $extra['heartbeat_interval'] = $resolvedHeartbeatInterval;

        $payload['cpu'] = json_encode($extra, JSON_UNESCAPED_UNICODE);

        if ($device) {
            $this->deviceModel->update($device['id'], $payload);
            $deviceId = (int) $device['id'];
        } else {
            $this->deviceModel->insert($payload);
            $deviceId = (int) $this->deviceModel->getInsertID();

            $assetData = [
                'inventory_no'  => $this->generateInventoryNo(),
                'category_id'   => 1,
                'asset_name'    => $payload['hostname'],
                'brand'         => $payload['manufacturer'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'status'        => 'aktif',
            ];

            $this->assetModel->insert($assetData);
            $assetId = (int) $this->assetModel->getInsertID();

            $this->deviceModel->update($deviceId, ['asset_id' => $assetId]);
        }

        $command = $this->popQueuedCommand($deviceId);
        $latestDevice = $this->deviceModel->find($deviceId);
        $latestExtra = $this->decodeExtra($latestDevice['cpu'] ?? null);
        $latestHeartbeatInterval = $this->resolveHeartbeatInterval($latestExtra);
        $clientProfile = $this->buildClientProfile($deviceId, $latestExtra);

        return $this->response->setJSON([
            'status'      => 'ok',
            'device_token' => $deviceToken,
            'heartbeat_interval' => $latestHeartbeatInterval,
            'command'     => $command,
            'client_profile' => $clientProfile,
            'remote_lock_until' => (int) ($latestExtra['remote_lock_until'] ?? 0),
            'server_time' => date(DATE_ATOM),
        ]);
    }

    public function agentUpdate()
    {
        $data = $this->resolvePayload();

        $deviceToken = trim((string) ($data['device_token'] ?? ''));
        $macAddress = trim((string) ($data['mac'] ?? ''));

        if ($deviceToken === '' && $macAddress === '') {
            return $this->response->setJSON(['update' => false]);
        }

        $device = null;

        if ($deviceToken !== '') {
            $device = $this->deviceModel
                ->where('device_token', $deviceToken)
                ->first();
        }

        if (!$device && $macAddress !== '') {
            $device = $this->deviceModel
                ->where('mac_address', $macAddress)
                ->first();
        }

        if (!$device) {
            return $this->response->setJSON(['update' => false]);
        }

        $extra = $this->decodeExtra($device['cpu'] ?? null);
        $latest = $this->resolveLatestAgentVersion();

        if (($extra['force_update'] ?? false) === true) {
            $extra['force_update'] = false;

            $this->deviceModel->update((int) $device['id'], [
                'cpu' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            ]);

            return $this->response->setJSON([
                'update' => true,
                'url' => $this->buildAgentDownloadUrl($latest),
                'version' => $latest,
            ]);
        }

        $currentVersion = trim((string) ($device['agent_version'] ?? '0.0.0'));

        if ($currentVersion === '' || version_compare($currentVersion, $latest, '<')) {
            return $this->response->setJSON([
                'update' => true,
                'url' => $this->buildAgentDownloadUrl($latest),
                'version' => $latest,
            ]);
        }

        return $this->response->setJSON(['update' => false]);
    }

    public function pushUpdate()
    {
        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['ok' => false, 'message' => 'ID device tidak valid']);
        }

        $queued = $this->queueCommand($id, 'update', true);

        return $this->response->setJSON([
            'ok' => $queued,
            'message' => $queued ? 'Perintah update diantrikan' : 'Device tidak ditemukan',
        ]);
    }

    private function resolvePayload(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json) && !empty($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
            // Abaikan parse error JSON, lanjut fallback ke form-data/query string.
        }

        $post = $this->request->getPost();
        if (is_array($post) && !empty($post)) {
            return $post;
        }

        $rawBody = trim((string) $this->request->getBody());
        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }

        $get = $this->request->getGet();
        if (is_array($get) && !empty($get)) {
            if (!empty($get['device_token']) || !empty($get['hostname']) || !empty($get['mac'])) {
                return $get;
            }
        }

        return [];
    }

    private function resolveDeviceAndToken(array $data): array
    {
        $providedToken = trim((string) ($data['device_token'] ?? ''));
        $macAddress = trim((string) ($data['mac'] ?? ''));

        $device = null;

        if ($providedToken !== '') {
            $device = $this->deviceModel
                ->where('device_token', $providedToken)
                ->first();
        }

        if (!$device && $macAddress !== '') {
            $device = $this->deviceModel
                ->where('mac_address', $macAddress)
                ->first();
        }

        $deviceToken = $providedToken !== ''
            ? $providedToken
            : (!empty($device['device_token']) ? (string) $device['device_token'] : bin2hex(random_bytes(16)));

        return [$device, $deviceToken];
    }

    private function decodeExtra(?string $raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function queueCommand(int $deviceId, string $command, bool $forceUpdate = false): bool
    {
        $device = $this->deviceModel->find($deviceId);
        if (!$device) {
            return false;
        }

        $extra = $this->decodeExtra($device['cpu'] ?? null);
        $extra['command'] = $command;

        if ($forceUpdate) {
            $extra['force_update'] = true;
        }

        $now = time();
        $extra['heartbeat_boost_until'] = $now + $this->remoteBoostSeconds();
        $extra['heartbeat_boost_interval'] = $this->remoteHeartbeatInterval();
        $extra['heartbeat_normal_interval'] = $this->defaultHeartbeatInterval();
        $extra['heartbeat_interval'] = $this->remoteHeartbeatInterval();
        $extra['remote_lock_until'] = $now + $this->remoteLockSeconds();
        $extra['remote_lock_action'] = $command;
        $extra['last_remote_request_at'] = $now;

        $this->deviceModel->update($deviceId, [
            'cpu' => json_encode($extra, JSON_UNESCAPED_UNICODE),
        ]);

        return true;
    }

    private function popQueuedCommand(int $deviceId): ?string
    {
        $device = $this->deviceModel->find($deviceId);
        if (!$device) {
            return null;
        }

        $extra = $this->decodeExtra($device['cpu'] ?? null);
        $command = trim((string) ($extra['command'] ?? ''));

        if ($command === '') {
            return null;
        }

        $extra['command'] = null;
        $extra['remote_lock_until'] = 0;
        $extra['remote_lock_action'] = null;
        $extra['heartbeat_interval'] = $this->resolveHeartbeatInterval($extra);
        $this->deviceModel->update($deviceId, [
            'cpu' => json_encode($extra, JSON_UNESCAPED_UNICODE),
        ]);

        return $command;
    }

    private function defaultHeartbeatInterval(): int
    {
        return max(60, (int) env('agent.defaultHeartbeatInterval', 600));
    }

    private function remoteHeartbeatInterval(): int
    {
        return max(10, (int) env('agent.remoteHeartbeatInterval', 10));
    }

    private function remoteBoostSeconds(): int
    {
        return max(60, (int) env('agent.remoteBoostSeconds', 180));
    }

    private function remoteLockSeconds(): int
    {
        return max(10, (int) env('agent.remoteLockSeconds', 25));
    }

    private function resolveHeartbeatInterval(array $extra): int
    {
        $normalInterval = max(60, (int) ($extra['heartbeat_normal_interval'] ?? $this->defaultHeartbeatInterval()));
        $remoteInterval = max(10, (int) ($extra['heartbeat_boost_interval'] ?? $this->remoteHeartbeatInterval()));
        $boostUntil = (int) ($extra['heartbeat_boost_until'] ?? 0);
        $hasQueuedCommand = trim((string) ($extra['command'] ?? '')) !== '';

        if ($hasQueuedCommand || $boostUntil > time()) {
            return $remoteInterval;
        }

        return $normalInterval;
    }

    private function buildClientProfile(int $deviceId, array $extra): array
    {
        $device = $this->deviceModel->find($deviceId);
        if (!$device) {
            return [];
        }

        $assetSummary = [];
        $assignmentSummary = [];

        if (!empty($device['asset_id'])) {
            $asset = $this->assetModel->find((int) $device['asset_id']);
            if ($asset) {
                $assetSummary = [
                    'inventory_no' => $asset['inventory_no'] ?? null,
                    'asset_name' => $asset['asset_name'] ?? null,
                    'location' => $asset['location'] ?? null,
                    'status' => $asset['status'] ?? null,
                ];
            }

            $assignment = Database::connect()
                ->table('asset_assignments aa')
                ->select('e.name, e.employee_id, e.division, e.position, aa.assigned_at')
                ->join('employees e', 'e.id = aa.employee_id', 'left')
                ->where('aa.asset_id', (int) $device['asset_id'])
                ->where('aa.returned_at', null)
                ->orderBy('aa.assigned_at', 'DESC')
                ->get()
                ->getRowArray();

            if ($assignment) {
                $assignmentSummary = [
                    'name' => $assignment['name'] ?? null,
                    'employee_id' => $assignment['employee_id'] ?? null,
                    'division' => $assignment['division'] ?? null,
                    'position' => $assignment['position'] ?? null,
                    'assigned_at' => $assignment['assigned_at'] ?? null,
                ];
            }
        }

        return [
            'hostname' => $device['hostname'] ?? null,
            'device_user' => $device['device_user'] ?? null,
            'server_ip' => $device['last_ip'] ?? null,
            'client_ip' => $extra['lan_ip'] ?? null,
            'agent_version' => $device['agent_version'] ?? null,
            'heartbeat_interval' => (int) ($extra['heartbeat_interval'] ?? $this->defaultHeartbeatInterval()),
            'pending_updates' => $extra['pending'] ?? null,
            'activation_status' => $extra['activation'] ?? null,
            'last_seen' => $device['last_seen'] ?? null,
            'asset' => $assetSummary,
            'assignment' => $assignmentSummary,
        ];
    }

    private function resolveLatestAgentVersion(): string
    {
        $configured = trim((string) env('agent.latestVersion', ''));
        if ($configured !== '') {
            return $configured;
        }

        $agentDir = rtrim((string) FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR . 'agent' . DIRECTORY_SEPARATOR;
        if (is_dir($agentDir)) {
            $latest = '0.0.0';
            $files = glob($agentDir . 'EAMSAgent-*.exe') ?: [];

            foreach ($files as $file) {
                $name = basename($file);
                if (preg_match('/EAMSAgent-([0-9]+(?:\.[0-9]+){1,3})\.exe/i', $name, $m)) {
                    $ver = $m[1];
                    if (version_compare($ver, $latest, '>')) {
                        $latest = $ver;
                    }
                }
            }

            if ($latest !== '0.0.0') {
                return $latest;
            }
        }

        return '1.2.0';
    }

    private function buildAgentDownloadUrl(string $version): string
    {
        return base_url('downloads/agent/EAMSAgent-' . $version . '.exe');
    }

    private function generateInventoryNo()
    {
        $last = $this->assetModel
            ->like('inventory_no', 'IT-PC-', 'after')
            ->orderBy('id', 'DESC')
            ->first();

        if (!$last) {
            return 'IT-PC-001';
        }

        preg_match('/IT-PC-(\d+)/', $last['inventory_no'], $matches);
        $num = isset($matches[1]) ? (int) $matches[1] + 1 : 1;

        return 'IT-PC-' . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }
}
