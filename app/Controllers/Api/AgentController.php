<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ITDeviceModel;
use App\Models\AssetModel;
use App\Models\ItDeviceCommandModel;
use Config\Database;

class AgentController extends BaseController
{
    protected $deviceModel;
    protected $assetModel;
    protected $commandModel;

    public function __construct()
    {
        $this->deviceModel = new ITDeviceModel();
        $this->assetModel = new AssetModel();
        $this->commandModel = new ItDeviceCommandModel();
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

        $requestIp = $this->request->getIPAddress();
        $clientIp = trim((string) ($data['lan_ip'] ?? ''));

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
            'last_ip'           => $clientIp !== '' ? $clientIp : $requestIp,
            'mac_address'       => $data['mac'] ?? null,
            'agent_version'     => $data['agent_version'] ?? null,
            'last_update_check' => date('Y-m-d H:i:s'),
            'last_seen'         => date('Y-m-d H:i:s'),
            'status'            => 'online',
            'device_token'      => $deviceToken,
        ];

        $oldExtra = $device ? $this->decodeExtra($device['cpu'] ?? null) : [];

        $incomingDiagnostics = $data['diagnostics'] ?? null;
        if (!is_array($incomingDiagnostics) || empty($incomingDiagnostics)) {
            $incomingDiagnostics = $oldExtra['diagnostics'] ?? [];
        }

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
            'update_channel' => $data['update_channel'] ?? $oldExtra['update_channel'] ?? 'auto',
            'lan_ip' => $clientIp !== '' ? $clientIp : ($oldExtra['lan_ip'] ?? null),
            'request_ip' => $requestIp,
            'hardware'     => $data['hardware'] ?? $oldExtra['hardware'] ?? [],
            'session'      => $data['session'] ?? $oldExtra['session'] ?? [],
            'diagnostics'  => $incomingDiagnostics,
            'last_command_result' => $data['last_command_result'] ?? $oldExtra['last_command_result'] ?? null,
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

        $this->syncCommandResult($deviceId, $extra['last_command_result'] ?? null);

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

    public function command()
    {
        $data = $this->resolvePayload();
        $method = strtoupper($this->request->getMethod());

        if (empty($data)) {
            if ($method === 'GET') {
                return $this->response->setJSON([
                    'status' => 'ok',
                    'message' => 'Agent command API aktif',
                    'server_time' => date(DATE_ATOM),
                ]);
            }

            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'invalid payload',
            ]);
        }

        $device = $this->findDeviceByIdentity($data);
        if (!$device) {
            return $this->response->setJSON([
                'status' => 'missing',
                'command' => null,
                'server_time' => date(DATE_ATOM),
            ]);
        }

        $deviceId = (int) $device['id'];
        $command = $this->popQueuedCommand($deviceId);
        $latestDevice = $this->deviceModel->find($deviceId);
        $latestExtra = $this->decodeExtra($latestDevice['cpu'] ?? null);

        return $this->response->setJSON([
            'status' => 'ok',
            'device_token' => $latestDevice['device_token'] ?? null,
            'heartbeat_interval' => $this->resolveHeartbeatInterval($latestExtra),
            'command' => $command,
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
        $track = $this->resolveAgentTrack($data, $device, $extra);
        $latest = $this->resolveLatestAgentPackage($track);

        if (($extra['force_update'] ?? false) === true) {
            $extra['force_update'] = false;

            $this->deviceModel->update((int) $device['id'], [
                'cpu' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            ]);

            return $this->response->setJSON([
                'update' => !empty($latest['filename']),
                'url' => !empty($latest['filename']) ? $this->buildAgentDownloadUrl($latest['filename']) : null,
                'version' => $latest['version'] ?? null,
                'channel' => $track,
            ]);
        }

        $currentVersion = trim((string) ($device['agent_version'] ?? $data['agent_version'] ?? '0.0.0'));

        if (!empty($latest['filename']) && ($currentVersion === '' || version_compare($currentVersion, (string) $latest['version'], '<'))) {
            return $this->response->setJSON([
                'update' => true,
                'url' => $this->buildAgentDownloadUrl($latest['filename']),
                'version' => $latest['version'],
                'channel' => $track,
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

        $queued = $this->queueCommand($id, 'update', [], true);

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

    private function findDeviceByIdentity(array $data): ?array
    {
        $providedToken = trim((string) ($data['device_token'] ?? ''));
        $macAddress = trim((string) ($data['mac'] ?? ''));
        $hostname = trim((string) ($data['hostname'] ?? ''));

        if ($providedToken !== '') {
            $device = $this->deviceModel
                ->where('device_token', $providedToken)
                ->first();

            if ($device) {
                return $device;
            }
        }

        if ($macAddress !== '') {
            $device = $this->deviceModel
                ->where('mac_address', $macAddress)
                ->first();

            if ($device) {
                return $device;
            }
        }

        if ($hostname !== '') {
            $device = $this->deviceModel
                ->where('hostname', $hostname)
                ->orderBy('last_seen', 'DESC')
                ->first();

            if ($device) {
                return $device;
            }
        }

        return null;
    }

    private function resolveDeviceAndToken(array $data): array
    {
        $providedToken = trim((string) ($data['device_token'] ?? ''));
        $device = $this->findDeviceByIdentity($data);

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

    private function queueCommand(int $deviceId, string $command, array $args = [], bool $forceUpdate = false): bool
    {
        $device = $this->deviceModel->find($deviceId);
        if (!$device) {
            return false;
        }

        $extra = $this->decodeExtra($device['cpu'] ?? null);
        $commandId = $this->generateCommandId();
        $extra['command'] = [
            'id' => $commandId,
            'name' => $command,
            'args' => $args,
            'queued_at' => date(DATE_ATOM),
        ];

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

        $this->recordCommandQueue($deviceId, $commandId, $command, $args);

        return true;
    }

    private function popQueuedCommand(int $deviceId)
    {
        $device = $this->deviceModel->find($deviceId);
        if (!$device) {
            return null;
        }

        $extra = $this->decodeExtra($device['cpu'] ?? null);
        $command = $extra['command'] ?? null;
        $commandName = $this->extractQueuedCommandName($command);

        if ($commandName === '') {
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
        $hasQueuedCommand = $this->extractQueuedCommandName($extra['command'] ?? null) !== '';

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
            'server_ip' => $extra['request_ip'] ?? null,
            'client_ip' => $extra['lan_ip'] ?? $device['last_ip'] ?? null,
            'agent_version' => $device['agent_version'] ?? null,
            'heartbeat_interval' => (int) ($extra['heartbeat_interval'] ?? $this->defaultHeartbeatInterval()),
            'pending_updates' => $extra['pending'] ?? null,
            'activation_status' => $extra['activation'] ?? null,
            'last_seen' => $device['last_seen'] ?? null,
            'update_channel' => $extra['update_channel'] ?? 'auto',
            'os' => $device['os'] ?? null,
            'os_version' => $device['os_version'] ?? null,
            'os_edition' => $extra['os_edition'] ?? null,
            'os_release' => $extra['os_release'] ?? null,
            'os_build' => $extra['os_build'] ?? null,
            'cpu_name' => $device['cpu_name'] ?? null,
            'cpu_core' => $device['cpu_core'] ?? null,
            'cpu_thread' => $device['cpu_thread'] ?? null,
            'gpu' => $device['gpu'] ?? null,
            'ram_gb' => $device['ram_gb'] ?? null,
            'storage_gb' => $device['storage_gb'] ?? null,
            'storage_total_gb' => $device['storage_gb'] ?? null,
            'storage_free' => $extra['storage_free'] ?? null,
            'storage_free_gb' => $extra['storage_free'] ?? null,
            'cpu_usage' => $extra['cpu_usage'] ?? null,
            'ram_usage' => $extra['ram_usage'] ?? null,
            'hardware' => is_array($extra['hardware'] ?? null) ? $extra['hardware'] : [],
            'session' => is_array($extra['session'] ?? null) ? $extra['session'] : [],
            'diagnostics' => is_array($extra['diagnostics'] ?? null) ? $extra['diagnostics'] : [],
            'last_command_result' => is_array($extra['last_command_result'] ?? null) ? $extra['last_command_result'] : null,
            'asset' => $assetSummary,
            'assignment' => $assignmentSummary,
        ];
    }

    private function extractQueuedCommandName($commandPayload): string
    {
        if (is_array($commandPayload)) {
            return strtolower(trim((string) ($commandPayload['name'] ?? $commandPayload['command'] ?? '')));
        }

        return strtolower(trim((string) $commandPayload));
    }

    private function generateCommandId(): string
    {
        try {
            return bin2hex(random_bytes(12));
        } catch (\Throwable $e) {
            return uniqid('cmd_', true);
        }
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

    private function recordCommandQueue(int $deviceId, string $commandId, string $command, array $args = []): void
    {
        if (!$this->commandLogTableExists()) {
            return;
        }

        $this->commandModel->insert([
            'device_id' => $deviceId,
            'command_id' => $commandId,
            'command' => $command,
            'payload_json' => !empty($args) ? json_encode($args, JSON_UNESCAPED_UNICODE) : null,
            'status' => 'queued',
            'requested_by' => 'System/API',
            'requested_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function syncCommandResult(int $deviceId, $commandResult): void
    {
        if (!$this->commandLogTableExists() || !is_array($commandResult)) {
            return;
        }

        $commandId = trim((string) ($commandResult['id'] ?? ''));
        if ($commandId === '') {
            return;
        }

        $status = strtolower(trim((string) ($commandResult['status'] ?? 'done')));
        $message = trim((string) ($commandResult['message'] ?? ''));
        $executedAtRaw = $commandResult['executed_at'] ?? null;
        $executedAt = null;

        if (is_numeric($executedAtRaw)) {
            $executedAt = date('Y-m-d H:i:s', (int) $executedAtRaw);
        } elseif (is_string($executedAtRaw) && trim($executedAtRaw) !== '') {
            $executedAt = date('Y-m-d H:i:s', strtotime($executedAtRaw));
        }

        $builder = $this->commandModel
            ->where('device_id', $deviceId)
            ->where('command_id', $commandId);

        $existing = $builder->first();
        if (!$existing) {
            return;
        }

        $updatePayload = [
            'status' => $status !== '' ? $status : 'done',
            'result' => $message !== '' ? $message : null,
            'executed_at' => $executedAt,
        ];

        $this->commandModel->update((int) $existing['id'], $updatePayload);
    }

    private function normalizeAgentTrack(?string $value): string
    {
        $track = strtolower(trim((string) $value));
        $aliases = [
            '' => 'stable',
            'auto' => 'stable',
            'stable' => 'stable',
            'default' => 'stable',
            'main' => 'stable',
            'modern' => 'stable',
            'win7' => 'win7',
            'windows7' => 'win7',
            'windows_7' => 'win7',
            'legacy' => 'win7',
            'legacy-win7' => 'win7',
            'xp' => 'xp',
            'windowsxp' => 'xp',
            'windows_xp' => 'xp',
            'legacy-xp' => 'xp',
        ];

        return $aliases[$track] ?? 'stable';
    }

    private function resolveAgentTrack(array $data = [], ?array $device = null, array $extra = []): string
    {
        $rawTrack = strtolower(trim((string) ($data['update_channel'] ?? $extra['update_channel'] ?? '')));
        if ($rawTrack !== '' && $rawTrack !== 'auto') {
            return $this->normalizeAgentTrack($rawTrack);
        }

        $osText = strtolower(trim(implode(' ', array_filter([
            (string) ($data['os'] ?? ''),
            (string) ($data['os_version'] ?? ''),
            (string) ($data['os_edition'] ?? $extra['os_edition'] ?? ''),
            (string) ($device['os'] ?? ''),
            (string) ($device['os_version'] ?? ''),
        ]))));

        if ($osText !== '') {
            if (str_contains($osText, 'windows xp') || str_contains($osText, 'server 2003')) {
                return 'xp';
            }

            foreach (['windows 7', 'windows 8', 'windows 8.1', 'windows vista', 'server 2008', 'server 2012'] as $keyword) {
                if (str_contains($osText, $keyword)) {
                    return 'win7';
                }
            }
        }

        $buildText = trim((string) ($data['os_build'] ?? $extra['os_build'] ?? ''));
        $buildParts = $buildText !== '' ? explode('.', $buildText) : [];
        $buildNumber = (int) end($buildParts);

        if ($buildNumber > 0 && $buildNumber < 6000) {
            return 'xp';
        }

        if ($buildNumber > 0 && $buildNumber < 10240) {
            return 'win7';
        }

        return 'stable';
    }

    private function resolveAgentDownloadDirectory(): ?string
    {
        $candidates = [
            rtrim((string) FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR . 'agent' . DIRECTORY_SEPARATOR,
            rtrim((string) FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'download' . DIRECTORY_SEPARATOR . 'agent' . DIRECTORY_SEPARATOR,
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    private function resolveAgentDownloadBasePath(): string
    {
        $downloadsDir = rtrim((string) FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR . 'agent';
        if (is_dir($downloadsDir)) {
            return 'downloads/agent';
        }

        return 'download/agent';
    }

    private function resolveAgentArtifactPatterns(string $track): array
    {
        $normalized = $this->normalizeAgentTrack($track);

        if ($normalized === 'win7') {
            return [
                '/^(?:EAMSAgent|YHSClient)-win7-(?<version>[0-9]+(?:\.[0-9]+){1,3})\.exe$/i',
            ];
        }

        if ($normalized === 'xp') {
            return [
                '/^(?:EAMSAgent|YHSClient)-xp-(?<version>[0-9]+(?:\.[0-9]+){1,3})\.exe$/i',
            ];
        }

        return [
            '/^(?:EAMSAgent|YHSClient)-(?<version>[0-9]+(?:\.[0-9]+){1,3})\.exe$/i',
        ];
    }

    private function defaultAgentArtifactFilename(string $track, string $version): string
    {
        $normalized = $this->normalizeAgentTrack($track);

        if ($normalized === 'win7') {
            return 'EAMSAgent-win7-' . $version . '.exe';
        }

        if ($normalized === 'xp') {
            return 'EAMSAgent-xp-' . $version . '.exe';
        }

        return 'EAMSAgent-' . $version . '.exe';
    }

    private function resolveLatestAgentPackage(string $track): array
    {
        $directory = $this->resolveAgentDownloadDirectory();
        $patterns = $this->resolveAgentArtifactPatterns($track);
        $latest = [
            'track' => $this->normalizeAgentTrack($track),
            'version' => trim((string) env('agent.latestVersion', '')),
            'filename' => null,
        ];

        foreach (glob(rtrim((string) $directory, '\\/') . DIRECTORY_SEPARATOR . '*.exe') ?: [] as $file) {
            $name = basename($file);
            foreach ($patterns as $pattern) {
                if (!preg_match($pattern, $name, $match)) {
                    continue;
                }

                $version = $match['version'] ?? null;
                if ($version === null) {
                    continue;
                }

                if ($latest['filename'] === null || version_compare((string) $version, (string) $latest['version'], '>')) {
                    $latest['version'] = (string) $version;
                    $latest['filename'] = $name;
                }
            }
        }

        if ($latest['filename'] === null && $latest['version'] !== '') {
            $latest['filename'] = $this->defaultAgentArtifactFilename($latest['track'], $latest['version']);
        }

        return $latest;
    }

    private function buildAgentDownloadUrl(string $filename): string
    {
        return base_url($this->resolveAgentDownloadBasePath() . '/' . rawurlencode($filename));
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
