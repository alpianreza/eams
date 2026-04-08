<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php helper('device'); ?>
<?php
$extra = $extra ?? device_extra($device);
$hw = $hw ?? device_hardware($device);
$insights = $insights ?? [];

$heartbeatInterval = max(10, (int)($extra['heartbeat_interval'] ?? 600));
$heartbeatLabel = $heartbeatInterval >= 60
    ? (number_format($heartbeatInterval / 60, 0) . ' menit')
    : ($heartbeatInterval . ' detik');
$online = device_is_online($device, $heartbeatInterval);
$score = device_risk_score($device);
[$riskLabel, $riskBadge] = device_risk_label($score);

$remoteLockUntil = (int)($extra['remote_lock_until'] ?? 0);
$remoteLockActive = $remoteLockUntil > time();
$remoteLockRemaining = max(0, $remoteLockUntil - time());
$remoteLockAction = strtolower((string)($extra['remote_lock_action'] ?? ''));
$remoteActionLabel = [
    'shutdown' => 'Shutdown',
    'restart' => 'Restart',
    'update' => 'Push Update',
    'sync' => 'Sync',
    'restart_agent' => 'Restart Agent',
    'lock' => 'Lock',
    'logoff' => 'Log Off',
    'popup_message' => 'Kirim Pesan',
    'collect_diagnostics' => 'Refresh Diagnosa',
];

$cpuUsage = isset($extra['cpu_usage']) ? (float)$extra['cpu_usage'] : null;
$ramUsage = isset($extra['ram_usage']) ? (float)$extra['ram_usage'] : null;
$storageTotal = (float)($device['storage_gb'] ?? 0);
$storageFree = isset($extra['storage_free']) ? (float)$extra['storage_free'] : null;
$storageUsedPercent = null;
if ($storageTotal > 0 && $storageFree !== null) {
    $storageUsedPercent = max(0, min(100, (($storageTotal - $storageFree) / $storageTotal) * 100));
}

$syncAt = !empty($extra['last_sync_at']) ? (int)$extra['last_sync_at'] : null;
$syncStatus = strtolower((string)($extra['last_sync_status'] ?? ''));
$syncLabel = $syncStatus === 'ok' ? 'Berhasil' : ($syncStatus === 'failed' ? 'Gagal' : 'Belum ada data');
$syncBadge = $syncStatus === 'ok' ? 'success' : ($syncStatus === 'failed' ? 'danger' : 'secondary');

$activation = strtolower((string)($extra['activation'] ?? 'unknown'));
$activationLabel = in_array($activation, ['activated', 'active'], true) ? 'Aktif' : (in_array($activation, ['not_activated', 'inactive', 'not activated'], true) ? 'Belum aktif' : 'Tidak diketahui');
$activationBadge = $activationLabel === 'Aktif' ? 'success' : ($activationLabel === 'Belum aktif' ? 'warning' : 'secondary');

$metricTone = static function (?float $value): string {
    if ($value === null) {
        return 'secondary';
    }

    if ($value >= 85) {
        return 'danger';
    }

    if ($value >= 65) {
        return 'warning';
    }

    return 'success';
};

$assignedName = $assignment['name'] ?? '-';
$assignedEmployeeId = $assignment['employee_id'] ?? '-';
$assignedMeta = trim((string)($assignment['division'] ?? ''));
if (!empty($assignment['position'])) {
    $assignedMeta = trim($assignedMeta . ' - ' . $assignment['position']);
}
if ($assignedMeta === '') {
    $assignedMeta = 'Belum ada data jabatan';
}

$peripherals = is_array($hw['peripherals'] ?? null) ? $hw['peripherals'] : [];
$keyboardList = is_array($peripherals['keyboards'] ?? null) ? $peripherals['keyboards'] : [];
$mouseList = is_array($peripherals['mice'] ?? null) ? $peripherals['mice'] : [];
$monitorList = is_array($peripherals['monitors'] ?? null) ? $peripherals['monitors'] : [];
$session = is_array($extra['session'] ?? null) ? $extra['session'] : [];
$diagnostics = is_array($extra['diagnostics'] ?? null) ? $extra['diagnostics'] : [];
$topProcesses = is_array($diagnostics['processes'] ?? null) ? $diagnostics['processes'] : [];
$serviceList = is_array($diagnostics['services'] ?? null) ? $diagnostics['services'] : [];
$softwareList = is_array($diagnostics['software'] ?? null) ? $diagnostics['software'] : [];
$lastCommandResult = is_array($extra['last_command_result'] ?? null) ? $extra['last_command_result'] : [];
$commandHistory = is_array($commandHistory ?? null) ? $commandHistory : [];
$diagnosticsAt = !empty($diagnostics['captured_at']) ? (int)$diagnostics['captured_at'] : null;
?>

<div class="it-shell">
    <section class="card border-0 shadow-sm no-lift it-hero-card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="it-kicker mb-1">Detail Device</p>
                <h5 class="fw-bold mb-1"><?= esc($device['hostname'] ?? '-') ?></h5>
                <p class="text-muted mb-2">
                    <?php $displayIp = $extra['lan_ip'] ?? $device['last_ip'] ?? '-'; ?>
                    User device: <strong><?= esc($device['device_user'] ?? '-') ?></strong> |
                    IP client: <strong><?= esc($displayIp) ?></strong>

                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill text-bg-<?= $online ? 'success' : 'secondary' ?>">
                        <?= $online ? 'Online' : 'Offline' ?>
                    </span>
                    <span class="badge rounded-pill text-bg-<?= esc($riskBadge) ?>">
                        Risiko <?= esc($riskLabel) ?> (<?= (int)$score ?>)
                    </span>
                    <span class="badge rounded-pill text-bg-info">
                        Interval sync <?= esc($heartbeatLabel) ?>
                    </span>
                    <?php if ($remoteLockActive): ?>
                        <span class="badge rounded-pill text-bg-warning">
                            Remote lock <?= (int)$remoteLockRemaining ?> detik (<?= esc($remoteActionLabel[$remoteLockAction] ?? strtoupper($remoteLockAction ?: 'AKSI')) ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('it/devices') ?>" class="btn btn-sm btn-outline-secondary it-quick-btn">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <a href="<?= base_url('it-assets') ?>" class="btn btn-sm btn-outline-primary it-quick-btn">
                    <i class="bi bi-pc-display me-1"></i> Inventaris IT
                </a>
            </div>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-lg-4">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Status Realtime</h6>
                </div>
                <div class="card-body pt-2">
                    <div class="it-detail-kv"><span>Nama pengguna</span><strong><?= esc($device['device_user'] ?? '-') ?></strong></div>
                    <div class="it-detail-kv"><span>Assign pengguna</span><strong><?= esc($assignedName) ?></strong></div>
                    <div class="it-detail-kv"><span>ID Karyawan</span><strong><?= esc($assignedEmployeeId) ?></strong></div>
                    <div class="it-detail-kv"><span>Divisi/Jabatan</span><strong><?= esc($assignedMeta) ?></strong></div>
                    <div class="it-detail-kv"><span>Sync terakhir</span><strong><?= $syncAt ? date('d M Y H:i:s', $syncAt) : '-' ?></strong></div>
                    <div class="it-detail-kv"><span>Status sync</span><strong><span class="badge text-bg-<?= esc($syncBadge) ?>"><?= esc($syncLabel) ?></span></strong></div>
                    <div class="it-detail-kv"><span>Status lisensi</span><strong><span class="badge text-bg-<?= esc($activationBadge) ?>"><?= esc($activationLabel) ?></span></strong></div>

                    <div class="it-metric">
                        <div class="d-flex justify-content-between mb-1">
                            <span>CPU Usage</span>
                            <strong><?= $cpuUsage !== null ? number_format($cpuUsage, 1) . '%' : '-' ?></strong>
                        </div>
                        <div class="progress it-progress">
                            <div class="progress-bar bg-<?= esc($metricTone($cpuUsage)) ?>" role="progressbar" style="width: <?= $cpuUsage !== null ? max(0, min(100, $cpuUsage)) : 0 ?>%"></div>
                        </div>
                    </div>

                    <div class="it-metric">
                        <div class="d-flex justify-content-between mb-1">
                            <span>RAM Usage</span>
                            <strong><?= $ramUsage !== null ? number_format($ramUsage, 1) . '%' : '-' ?></strong>
                        </div>
                        <div class="progress it-progress">
                            <div class="progress-bar bg-<?= esc($metricTone($ramUsage)) ?>" role="progressbar" style="width: <?= $ramUsage !== null ? max(0, min(100, $ramUsage)) : 0 ?>%"></div>
                        </div>
                    </div>

                    <div class="it-metric mb-0">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Storage terpakai</span>
                            <strong><?= $storageUsedPercent !== null ? number_format($storageUsedPercent, 1) . '%' : '-' ?></strong>
                        </div>
                        <div class="progress it-progress">
                            <div class="progress-bar bg-<?= esc($metricTone($storageUsedPercent)) ?>" role="progressbar" style="width: <?= $storageUsedPercent !== null ? max(0, min(100, $storageUsedPercent)) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-8">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Command Center</h6>
                </div>
                <div class="card-body pt-2">
                    <p class="text-muted small mb-3">
                        Command tetap dipoll cepat, sementara data berat seperti proses, service, dan software hanya diambil saat diminta supaya client tetap ringan.
                    </p>
                    <?php if ($remoteLockActive): ?>
                        <div class="alert alert-warning py-2 px-3 mb-3">
                            Remote lock aktif <?= (int)$remoteLockRemaining ?> detik untuk aksi
                            <strong><?= esc($remoteActionLabel[$remoteLockAction] ?? strtoupper($remoteLockAction ?: 'AKSI')) ?></strong>.
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($lastCommandResult['name'])): ?>
                        <?php
                        $lastCommandStatus = strtolower((string)($lastCommandResult['status'] ?? 'info'));
                        $lastCommandTone = $lastCommandStatus === 'success' ? 'success' : ($lastCommandStatus === 'error' ? 'danger' : 'info');
                        $lastCommandTime = !empty($lastCommandResult['executed_at']) ? (int)$lastCommandResult['executed_at'] : null;
                        ?>
                        <div class="alert alert-<?= esc($lastCommandTone) ?> py-2 px-3 mb-3">
                            <div class="fw-semibold">Aksi terakhir: <?= esc($remoteActionLabel[$lastCommandResult['name']] ?? strtoupper(str_replace('_', ' ', (string)$lastCommandResult['name']))) ?></div>
                            <div class="small">
                                <?= esc($lastCommandResult['message'] ?? 'Perintah terakhir sudah dieksekusi agent.') ?>
                                <?php if ($lastCommandTime): ?>
                                    <span class="text-muted"> / <?= date('d M Y H:i:s', $lastCommandTime) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="it-command-grid">
                        <button class="btn btn-danger remote-btn" data-action="shutdown" data-id="<?= (int)$device['id'] ?>" data-lock-until="<?= (int)$remoteLockUntil ?>" <?= $remoteLockActive ? 'disabled' : '' ?>>
                            <i class="bi bi-power me-1"></i> Shutdown OS
                        </button>
                        <button class="btn btn-warning remote-btn" data-action="restart" data-id="<?= (int)$device['id'] ?>" data-lock-until="<?= (int)$remoteLockUntil ?>" <?= $remoteLockActive ? 'disabled' : '' ?>>
                            <i class="bi bi-arrow-repeat me-1"></i> Restart OS
                        </button>
                        <button class="btn btn-primary remote-btn" data-action="update" data-id="<?= (int)$device['id'] ?>" data-lock-until="<?= (int)$remoteLockUntil ?>" <?= $remoteLockActive ? 'disabled' : '' ?>>
                            <i class="bi bi-cloud-arrow-up me-1"></i> Push Update
                        </button>
                        <button class="btn btn-outline-info remote-btn" data-action="sync" data-id="<?= (int)$device['id'] ?>" data-lock-until="<?= (int)$remoteLockUntil ?>" <?= $remoteLockActive ? 'disabled' : '' ?>>
                            <i class="bi bi-arrow-clockwise me-1"></i> Sync Sekarang
                        </button>
                        <button class="btn btn-outline-dark remote-btn" data-action="restart_agent" data-id="<?= (int)$device['id'] ?>" data-lock-until="<?= (int)$remoteLockUntil ?>" <?= $remoteLockActive ? 'disabled' : '' ?>>
                            <i class="bi bi-cpu me-1"></i> Restart Agent
                        </button>
                        <button class="btn btn-outline-warning remote-btn" data-action="lock" data-id="<?= (int)$device['id'] ?>" data-lock-until="<?= (int)$remoteLockUntil ?>" <?= $remoteLockActive ? 'disabled' : '' ?>>
                            <i class="bi bi-lock me-1"></i> Lock Screen
                        </button>
                        <button class="btn btn-outline-danger remote-btn" data-action="logoff" data-id="<?= (int)$device['id'] ?>" data-lock-until="<?= (int)$remoteLockUntil ?>" <?= $remoteLockActive ? 'disabled' : '' ?>>
                            <i class="bi bi-box-arrow-right me-1"></i> Log Off User
                        </button>
                        <button class="btn btn-outline-primary remote-message-btn" data-id="<?= (int)$device['id'] ?>" data-lock-until="<?= (int)$remoteLockUntil ?>" <?= $remoteLockActive ? 'disabled' : '' ?>>
                            <i class="bi bi-chat-left-text me-1"></i> Kirim Pesan
                        </button>
                        <button class="btn btn-outline-success diagnostic-refresh-btn" data-id="<?= (int)$device['id'] ?>" data-lock-until="<?= (int)$remoteLockUntil ?>" <?= $remoteLockActive ? 'disabled' : '' ?>>
                            <i class="bi bi-activity me-1"></i> Refresh Diagnosa
                        </button>
                        <button class="btn btn-outline-secondary copy-token-btn" data-token="<?= esc($device['device_token'] ?? '') ?>">
                            <i class="bi bi-clipboard-check me-1"></i> Copy Token
                        </button>
                        <button class="btn btn-outline-secondary" onclick="window.open('<?= base_url('downloads/logs/' . ($device['device_token'] ?? '') . '.log') ?>', '_blank')">
                            <i class="bi bi-journal-text me-1"></i> View Log
                        </button>
                    </div>

                    <div class="it-token-box mt-3">
                        <label class="form-label form-label-sm text-muted mb-1">Device Token</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" readonly value="<?= esc($device['device_token'] ?? '-') ?>">
                            <button class="btn btn-outline-secondary copy-token-btn" data-token="<?= esc($device['device_token'] ?? '') ?>" type="button">Copy</button>
                        </div>
                    </div>
                    <div class="small text-muted mt-3">
                        Aksi premium yang aktif di batch ini: popup message, refresh diagnosa proses/service/software, dan log off user.
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-lg-5">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Insight Otomatis</h6>
                </div>
                <div class="card-body pt-2">
                    <ul class="it-insight-list mb-0">
                        <?php foreach ($insights as $insight): ?>
                            <li class="tone-<?= esc($insight['tone'] ?? 'secondary') ?>">
                                <strong><?= esc($insight['title'] ?? '-') ?></strong>
                                <span><?= esc($insight['body'] ?? '-') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        </div>

        <div class="col-lg-7">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Informasi Sistem</h6>
                </div>
                <div class="card-body pt-2">
                    <div class="it-detail-kv"><span>Sistem Operasi</span><strong><?= esc($device['os'] ?? '-') ?> <?= esc($device['os_version'] ?? '') ?></strong></div>
                    <div class="it-detail-kv"><span>Edisi / Build</span><strong><?= esc($extra['os_edition'] ?? '-') ?> / <?= esc($extra['os_build'] ?? '-') ?></strong></div>
                    <div class="it-detail-kv"><span>CPU</span><strong><?= esc($device['cpu_name'] ?? '-') ?></strong></div>
                    <div class="it-detail-kv"><span>Core / Thread</span><strong><?= (int)($device['cpu_core'] ?? 0) ?> / <?= (int)($device['cpu_thread'] ?? 0) ?></strong></div>
                    <div class="it-detail-kv"><span>GPU</span><strong><?= esc($device['gpu'] ?? '-') ?></strong></div>
                    <div class="it-detail-kv"><span>Arsitektur</span><strong><?= esc($device['architecture'] ?? '-') ?></strong></div>
                    <div class="it-detail-kv"><span>RAM Total</span><strong><?= esc($device['ram_gb'] ?? 0) ?> GB</strong></div>
                    <div class="it-detail-kv"><span>Storage Total</span><strong><?= esc($device['storage_gb'] ?? 0) ?> GB</strong></div>
                    <div class="it-detail-kv"><span>Sisa Storage</span><strong><?= $storageFree !== null ? number_format($storageFree, 2) . ' GB' : '-' ?></strong></div>
                    <div class="it-detail-kv"><span>Agent Version</span><strong><?= esc($device['agent_version'] ?? '-') ?></strong></div>
                    <div class="it-detail-kv"><span>Last Seen</span><strong><?= !empty($device['last_seen']) ? date('d M Y H:i:s', strtotime($device['last_seen'])) : '-' ?></strong></div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-lg-4">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Session Aktif</h6>
                </div>
                <div class="card-body pt-2">
                    <div class="it-detail-kv"><span>User aktif</span><strong><?= esc($session['user'] ?? ($device['device_user'] ?? '-')) ?></strong></div>
                    <div class="it-detail-kv"><span>Idle</span><strong><?= isset($session['idle_sec']) ? gmdate('H:i:s', (int)$session['idle_sec']) : '-' ?></strong></div>
                    <div class="it-detail-kv"><span>Uptime agent</span><strong><?= isset($session['uptime_sec']) ? gmdate('H:i:s', (int)$session['uptime_sec']) : '-' ?></strong></div>
                    <div class="it-detail-kv"><span>Jumlah monitor</span><strong><?= esc($session['monitor_count'] ?? '-') ?></strong></div>
                    <div class="it-detail-kv"><span>Resolusi utama</span><strong><?= esc($session['primary_resolution'] ?? '-') ?></strong></div>
                    <div class="it-detail-kv"><span>Polling command</span><strong><?= isset($session['command_poll_interval']) ? (int)$session['command_poll_interval'] . ' detik' : '-' ?></strong></div>
                    <div class="it-detail-kv"><span>Startup agent</span><strong><?= !empty($session['startup_enabled']) ? 'Aktif' : 'Nonaktif' ?></strong></div>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-semibold mb-1">Top Process</h6>
                        <?php if ($diagnosticsAt): ?>
                            <div class="small text-muted">Snapshot <?= date('d M Y H:i:s', $diagnosticsAt) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <?php if (!empty($topProcesses)): ?>
                        <ul class="list-group list-group-flush it-list">
                            <?php foreach (array_slice($topProcesses, 0, 8) as $process): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <div class="fw-semibold"><?= esc($process['name'] ?? 'Process') ?></div>
                                            <div class="small text-muted">PID <?= esc($process['pid'] ?? '-') ?><?= !empty($process['status']) ? ' / ' . esc($process['status']) : '' ?></div>
                                        </div>
                                        <div class="text-end small">
                                            <div>RAM <?= number_format((float)($process['memory_percent'] ?? 0), 1) ?>%</div>
                                            <div class="text-muted">CPU <?= number_format((float)($process['cpu_percent'] ?? 0), 1) ?>%</div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Belum ada snapshot process. Klik <strong>Refresh Diagnosa</strong> untuk mengambil data terbaru.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Service Penting</h6>
                </div>
                <div class="card-body pt-2">
                    <?php if (!empty($serviceList)): ?>
                        <ul class="list-group list-group-flush it-list">
                            <?php foreach (array_slice($serviceList, 0, 8) as $service): ?>
                                <?php $isRunning = strtolower((string)($service['status'] ?? '')) === 'running'; ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold"><?= esc($service['display_name'] ?? $service['name'] ?? 'Service') ?></div>
                                        <div class="small text-muted"><?= esc($service['name'] ?? '-') ?><?= !empty($service['start_type']) ? ' / ' . esc($service['start_type']) : '' ?></div>
                                    </div>
                                    <span class="badge text-bg-<?= $isRunning ? 'success' : 'secondary' ?>"><?= esc($service['status'] ?? '-') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Belum ada snapshot service. Klik <strong>Refresh Diagnosa</strong> untuk mengambil data terbaru.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-lg-6">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Detail RAM</h6>
                </div>
                <div class="card-body pt-2">
                    <?php if (!empty($hw['ram_slots'])): ?>
                        <ul class="list-group list-group-flush it-list">
                            <?php foreach ($hw['ram_slots'] as $slotIndex => $ram): ?>
                                <?php
                                $manufacturer = trim((string)($ram['manufacturer'] ?? ''));
                                $isUnknownManufacturer = $manufacturer === '' || in_array(strtolower($manufacturer), ['unknown', '-', 'n/a', 'na'], true);
                                $ramLabel = $isUnknownManufacturer ? 'Slot RAM ' . ((int)$slotIndex + 1) : $manufacturer;
                                $ramSpeed = (int)($ram['speed'] ?? 0);
                                if ($ramSpeed > 0) {
                                    $ramLabel .= ' - ' . number_format($ramSpeed, 0, ',', '.') . ' MHz';
                                }
                                $ramSize = (float)($ram['size_gb'] ?? 0);
                                $ramSizeLabel = $ramSize > 0
                                    ? rtrim(rtrim(number_format($ramSize, 2, '.', ''), '0'), '.')
                                    : '0';
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= esc($ramLabel) ?></span>
                                    <strong><?= esc($ramSizeLabel) ?> GB</strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Data slot RAM belum tersedia.</div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <span class="badge text-bg-info">Total <?= esc(device_ram_total($device)) ?> GB</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Detail Storage</h6>
                </div>
                <div class="card-body pt-2">
                    <?php
                    $diskList = $hw['disks'] ?? [];

                    if (empty($diskList)) {
                        $diskModelRaw = trim((string)($device['disk_model'] ?? ''));
                        if ($diskModelRaw !== '') {
                            $diskModels = preg_split('/[;|\r\n]+/', $diskModelRaw) ?: [];
                            foreach ($diskModels as $diskModel) {
                                $diskModel = trim((string)$diskModel);
                                if ($diskModel === '') {
                                    continue;
                                }
                                $diskList[] = [
                                    'model' => $diskModel,
                                    'size_gb' => null,
                                ];
                            }
                        }
                    }
                    ?>
                    <?php if (!empty($diskList)): ?>
                        <ul class="list-group list-group-flush it-list">
                            <?php foreach ($diskList as $disk): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= esc($disk['model'] ?? 'Disk') ?></span>
                                    <strong>
                                        <?php if (isset($disk['size_gb']) && $disk['size_gb'] !== null && (float)$disk['size_gb'] > 0): ?>
                                            <?= esc(rtrim(rtrim(number_format((float)$disk['size_gb'], 2, '.', ''), '0'), '.')) ?> GB
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Data disk belum tersedia.</div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <span class="badge text-bg-primary">Total <?= esc(device_disk_total($device)) ?> GB</span>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-lg-4">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Keyboard</h6>
                </div>
                <div class="card-body pt-2">
                    <?php if (!empty($keyboardList)): ?>
                        <ul class="list-group list-group-flush it-list">
                            <?php foreach ($keyboardList as $keyboard): ?>
                                <?php
                                $keyboardName = trim((string)($keyboard['display_name'] ?? $keyboard['name'] ?? 'Keyboard'));
                                $keyboardMeta = array_filter([
                                    trim((string)($keyboard['interface'] ?? '')),
                                    trim((string)($keyboard['status'] ?? '')),
                                ]);
                                ?>
                                <li class="list-group-item">
                                    <div class="fw-semibold"><?= esc($keyboardName !== '' ? $keyboardName : 'Keyboard') ?></div>
                                    <div class="small text-muted"><?= !empty($keyboardMeta) ? esc(implode(' / ', $keyboardMeta)) : '-' ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Data keyboard belum tersedia.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Mouse / Pointer</h6>
                </div>
                <div class="card-body pt-2">
                    <?php if (!empty($mouseList)): ?>
                        <ul class="list-group list-group-flush it-list">
                            <?php foreach ($mouseList as $mouse): ?>
                                <?php
                                $mouseName = trim((string)($mouse['display_name'] ?? $mouse['name'] ?? 'Pointer'));
                                $mouseMeta = array_filter([
                                    trim((string)($mouse['interface'] ?? '')),
                                    trim((string)($mouse['status'] ?? '')),
                                ]);
                                ?>
                                <li class="list-group-item">
                                    <div class="fw-semibold"><?= esc($mouseName !== '' ? $mouseName : 'Pointer') ?></div>
                                    <div class="small text-muted"><?= !empty($mouseMeta) ? esc(implode(' / ', $mouseMeta)) : '-' ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Data mouse atau pointer belum tersedia.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Monitor</h6>
                </div>
                <div class="card-body pt-2">
                    <?php if (!empty($monitorList)): ?>
                        <ul class="list-group list-group-flush it-list">
                            <?php foreach ($monitorList as $monitor): ?>
                                <?php
                                $monitorName = trim((string)($monitor['display_name'] ?? $monitor['name'] ?? 'Monitor'));
                                $monitorMeta = array_filter([
                                    trim((string)($monitor['resolution'] ?? '')),
                                    trim((string)($monitor['status'] ?? '')),
                                ]);
                                ?>
                                <li class="list-group-item">
                                    <div class="fw-semibold"><?= esc($monitorName !== '' ? $monitorName : 'Monitor') ?></div>
                                    <div class="small text-muted"><?= !empty($monitorMeta) ? esc(implode(' / ', $monitorMeta)) : '-' ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Data monitor belum tersedia.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-lg-6">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Software Terpasang</h6>
                </div>
                <div class="card-body pt-2">
                    <?php if (!empty($softwareList)): ?>
                        <ul class="list-group list-group-flush it-list">
                            <?php foreach (array_slice($softwareList, 0, 10) as $software): ?>
                                <li class="list-group-item">
                                    <div class="fw-semibold"><?= esc($software['name'] ?? 'Software') ?></div>
                                    <div class="small text-muted">
                                        <?= esc($software['version'] ?? '-') ?>
                                        <?php if (!empty($software['publisher'])): ?>
                                            / <?= esc($software['publisher']) ?>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Belum ada snapshot software. Klik <strong>Refresh Diagnosa</strong> untuk mengambil data terbaru.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-6">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Riwayat Command</h6>
                </div>
                <div class="card-body pt-2">
                    <?php if (!empty($commandHistory)): ?>
                        <ul class="list-group list-group-flush it-list">
                            <?php foreach ($commandHistory as $commandRow): ?>
                                <?php
                                $commandStatus = strtolower((string)($commandRow['status'] ?? 'queued'));
                                $statusTone = $commandStatus === 'success' ? 'success' : ($commandStatus === 'error' ? 'danger' : ($commandStatus === 'queued' ? 'warning' : 'secondary'));
                                $requestedAt = !empty($commandRow['requested_at']) ? date('d M Y H:i:s', strtotime($commandRow['requested_at'])) : '-';
                                ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold"><?= esc($remoteActionLabel[$commandRow['command']] ?? strtoupper(str_replace('_', ' ', (string)$commandRow['command']))) ?></div>
                                            <div class="small text-muted"><?= esc($commandRow['requested_by'] ?? 'System') ?> / <?= esc($requestedAt) ?></div>
                                            <?php if (!empty($commandRow['result'])): ?>
                                                <div class="small mt-1"><?= esc($commandRow['result']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge text-bg-<?= esc($statusTone) ?>"><?= esc($commandStatus) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Riwayat command akan muncul di sini setelah tabel audit aktif dan perintah pertama dikirim.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <section class="card border-0 shadow-sm no-lift mt-3">
        <div class="card-header bg-transparent border-0 pb-0">
            <h6 class="fw-semibold mb-1">Keterkaitan Asset & Assignment</h6>
        </div>
        <div class="card-body pt-2">
            <?php if ($asset): ?>
                <div class="it-detail-kv">
                    <span>Asset Terhubung</span>
                    <strong><?= esc($asset['inventory_no'] ?? '-') ?> - <?= esc($asset['asset_name'] ?? '-') ?></strong>
                </div>
                <div class="it-detail-kv">
                    <span>Lokasi</span>
                    <strong><?= esc($asset['location'] ?? '-') ?></strong>
                </div>
                <div class="it-detail-kv">
                    <span>Assign Pengguna</span>
                    <strong><?= esc($assignedName) ?></strong>
                </div>
                <div class="it-detail-kv">
                    <span>Divisi / Jabatan</span>
                    <strong><?= esc($assignedMeta) ?></strong>
                </div>
                <div class="it-detail-kv">
                    <span>Tanggal Assign</span>
                    <strong><?= !empty($assignment['assigned_at']) ? date('d M Y H:i', strtotime($assignment['assigned_at'])) : '-' ?></strong>
                </div>
                <a href="<?= base_url('it-assets/detail/' . $asset['id']) ?>" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Detail Asset
                </a>
            <?php else: ?>
                <div class="alert alert-warning mb-0">Device ini belum ditautkan ke asset inventaris.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/it-suite.css?v=' . filemtime(FCPATH . 'assets/css/it-suite.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/device-remote.js?v=' . filemtime(FCPATH . 'js/device-remote.js')) ?>"></script>
<?= $this->endSection() ?>

