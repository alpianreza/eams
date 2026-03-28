<?= $this->extend('layouts/main') ?>

<?php
$statusLabels = [
    'baik' => 'Baik',
    'normal' => 'Normal',
    'aktif' => 'Aktif',
    'dipakai' => 'Dipakai',
    'tersedia' => 'Tersedia',
    'available' => 'Tersedia',
    'rusak' => 'Rusak',
    'repair' => 'Perbaikan',
    'unknown' => 'Lainnya',
];

$statusTones = [
    'baik' => 'success',
    'normal' => 'success',
    'aktif' => 'primary',
    'dipakai' => 'info',
    'tersedia' => 'secondary',
    'available' => 'secondary',
    'rusak' => 'danger',
    'repair' => 'warning',
    'unknown' => 'dark',
];
?>

<?= $this->section('content') ?>
<div class="it-shell">
    <section class="card border-0 shadow-sm no-lift it-hero-card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="it-kicker mb-1">IT Operations</p>
                <h5 class="fw-bold mb-1">Dashboard IT</h5>
                <p class="text-muted mb-0">Ringkasan kondisi aset IT dan perangkat aktif karyawan.</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('it-assets') ?>" class="btn btn-sm btn-primary it-quick-btn">
                    <i class="bi bi-pc-display me-1"></i> Inventaris IT
                </a>
                <a href="<?= base_url('it/devices') ?>" class="btn btn-sm btn-outline-primary it-quick-btn">
                    <i class="bi bi-cpu me-1"></i> Device Center
                </a>
                <a href="<?= base_url('employees') ?>" class="btn btn-sm btn-outline-secondary it-quick-btn">
                    <i class="bi bi-people me-1"></i> Pemegang IT
                </a>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <article class="card border-0 shadow-sm h-100 no-lift it-stat-card">
                <div class="card-body">
                    <span class="it-stat-label">Total Asset IT</span>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <strong class="it-stat-value"><?= (int) $totalIT ?></strong>
                        <span class="it-stat-icon tone-info"><i class="bi bi-box-seam"></i></span>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-6 col-xl-3">
            <article class="card border-0 shadow-sm h-100 no-lift it-stat-card">
                <div class="card-body">
                    <span class="it-stat-label">Asset Dipakai</span>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <strong class="it-stat-value"><?= (int) $usedAsset ?></strong>
                        <span class="it-stat-icon tone-success"><i class="bi bi-check2-circle"></i></span>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-6 col-xl-3">
            <article class="card border-0 shadow-sm h-100 no-lift it-stat-card">
                <div class="card-body">
                    <span class="it-stat-label">Asset Rusak</span>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <strong class="it-stat-value text-danger"><?= (int) $brokenAsset ?></strong>
                        <span class="it-stat-icon tone-danger"><i class="bi bi-exclamation-octagon"></i></span>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-6 col-xl-3">
            <article class="card border-0 shadow-sm h-100 no-lift it-stat-card">
                <div class="card-body">
                    <span class="it-stat-label">Asset Compliance</span>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <strong class="it-stat-value"><?= (int) $complianceAsset ?></strong>
                        <span class="it-stat-icon tone-warning"><i class="bi bi-shield-check"></i></span>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Distribusi Status Asset IT</h6>
                    <small class="text-muted">Ringkasan status berdasarkan data inventaris</small>
                </div>
                <div class="card-body pt-3">
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!empty($statusSummary)): ?>
                            <?php foreach ($statusSummary as $row): ?>
                                <?php
                                $key = strtolower(trim((string) ($row->status ?? 'unknown')));
                                if ($key === '') {
                                    $key = 'unknown';
                                }
                                $label = $statusLabels[$key] ?? ucfirst($key);
                                $tone = $statusTones[$key] ?? 'dark';
                                ?>
                                <span class="badge text-bg-<?= esc($tone) ?> it-status-pill">
                                    <?= esc($label) ?>: <?= (int) ($row->total ?? 0) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-light border mb-0">Belum ada data status asset.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-8">
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-semibold mb-1">Pengguna Komputer Aktif</h6>
                        <small class="text-muted">Karyawan yang saat ini memegang asset komputer</small>
                    </div>
                    <a href="<?= base_url('it-assets') ?>" class="btn btn-sm btn-outline-primary">Kelola Aset</a>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 it-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th class="d-none d-md-table-cell">Divisi</th>
                                    <th>Asset</th>
                                    <th class="d-none d-lg-table-cell">No Inventaris</th>
                                    <th>Sejak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($computerUsers)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Belum ada data pemakaian komputer aktif.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($computerUsers as $row): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= esc($row->name ?? '-') ?></td>
                                            <td class="d-none d-md-table-cell"><?= esc($row->division ?? '-') ?></td>
                                            <td><?= esc($row->asset_name ?? '-') ?></td>
                                            <td class="d-none d-lg-table-cell"><?= esc($row->inventory_no ?? '-') ?></td>
                                            <td><?= !empty($row->assigned_at) ? date('d M Y', strtotime($row->assigned_at)) : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/it-suite.css?v=' . filemtime(FCPATH . 'assets/css/it-suite.css')) ?>">
<?= $this->endSection() ?>
