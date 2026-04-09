<?php helper(['os_lifecycle', 'device']); ?>

<div class="table-responsive">
    <table class="table align-middle mb-0 it-table">
        <thead>
            <tr>
                <th>Hostname</th>
                <th class="d-none d-md-table-cell">User</th>
                <th class="d-none d-lg-table-cell">OS</th>
                <th class="d-none d-xl-table-cell">CPU</th>
                <th>RAM</th>
                <th>Storage</th>
                <th class="d-none d-lg-table-cell">Agent</th>
                <th>Terakhir Aktif</th>
                <th>Risiko</th>
                <th class="d-none d-lg-table-cell">Siklus OS</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($devices)): ?>
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">Data device tidak ditemukan.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($devices as $d): ?>
                    <?php
                    $extra = json_decode($d['cpu'] ?? '{}', true) ?? [];
$heartbeatInterval = max(10, (int)($extra['heartbeat_interval'] ?? 900));
                    $online = device_is_online($d, $heartbeatInterval);
                    $score = device_risk_score($d);
                    [$label, $badge] = device_risk_label($score);
                    $release = $extra['os_release'] ?? null;
                    $osLabel = device_os_label($d);
                    $osMeta = device_os_meta($d);

                    $lifecycle = function_exists('windows_lifecycle')
                        ? windows_lifecycle($release)
                        : ['status' => 'unknown', 'color' => 'secondary'];

                    $recommend = function_exists('windows_upgrade_recommendation')
                        ? windows_upgrade_recommendation($release)
                        : null;
                    ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('it/devices/' . $d['id']) ?>" class="fw-semibold">
                                <?= esc($d['hostname'] ?? '-') ?>
                            </a>
                            <div class="small text-muted d-md-none"><?= esc($d['device_user'] ?? '-') ?></div>
                        </td>
                        <td class="d-none d-md-table-cell"><?= esc($d['device_user'] ?? '-') ?></td>
                        <td class="d-none d-lg-table-cell">
                            <div class="fw-semibold"><?= esc($osLabel) ?></div>
                            <?php if ($osMeta !== ''): ?>
                                <div class="small text-muted"><?= esc($osMeta) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-xl-table-cell text-truncate device-cpu-cell"><?= esc($d['cpu_name'] ?? '-') ?></td>
                        <td><?= esc($d['ram_gb'] ?? 0) ?> GB</td>
                        <td><?= esc($d['storage_gb'] ?? 0) ?> GB</td>
                        <td class="d-none d-lg-table-cell"><?= esc($d['agent_version'] ?? '-') ?></td>
                        <td><?= !empty($d['last_seen']) ? date('d M H:i', strtotime($d['last_seen'])) : '-' ?></td>
                        <td>
                            <span class="badge text-bg-<?= esc($badge) ?>">
                                <?= esc($label) ?> (<?= (int) $score ?>)
                            </span>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <span class="badge text-bg-<?= esc($lifecycle['color']) ?>">
                                <?= esc(strtoupper($lifecycle['status'])) ?>
                            </span>
                            <?php if ($recommend): ?>
                                <div class="small text-muted mt-1"><?= esc($recommend) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($online): ?>
                                <span class="badge text-bg-success">Online</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Offline</span>
                            <?php endif; ?>
                            <div class="small text-muted mt-1">
                                Sync <?= $heartbeatInterval >= 60 ? number_format($heartbeatInterval / 60, 0) . 'm' : (int) $heartbeatInterval . 's' ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-end">
    <ul class="pagination pagination-sm mb-0">
        <?= $pager->links('default', 'eams') ?>
    </ul>
</div>
