<?= $this->extend('layouts/main') ?>

<?php
$role       = session()->get('role');
$permission = session()->get('permission');
$isWritable = ($permission === 'write' || $role === 'admin');

$statusRaw = strtolower(trim((string) ($asset['status'] ?? '-')));
$statusClass = match ($statusRaw) {
    'baik', 'normal' => 'success',
    'rusak' => 'danger',
    'dipakai' => 'primary',
    default => 'secondary',
};
?>

<?= $this->section('content') ?>
<div class="it-shell" x-data="itAssetDetail(window.IT_ASSET_DETAIL_BOOT || {})" x-init="init()">
    <section class="card border-0 shadow-sm no-lift it-hero-card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="it-kicker mb-1">Detail Asset IT</p>
                <h5 class="fw-bold mb-1"><?= esc($asset['asset_name'] ?? '-') ?></h5>
                <p class="text-muted mb-0">No Inventaris: <strong><?= esc($asset['inventory_no'] ?? '-') ?></strong></p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-dark it-quick-btn" @click="copyInventory()">
                    <i class="bi bi-clipboard me-1"></i> Copy No Inventaris
                </button>
                <?php if ($isWritable): ?>
                    <a href="<?= base_url('it-assets/assign/' . $asset['id']) ?>" class="btn btn-sm btn-primary it-quick-btn">
                        <i class="bi bi-person-check me-1"></i> Assign Asset
                    </a>
                    <a href="<?= base_url('it-assets/edit/' . $asset['id']) ?>" class="btn btn-sm btn-outline-warning it-quick-btn">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('it-assets') ?>" class="btn btn-sm btn-outline-secondary it-quick-btn">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm no-lift mb-3 d-lg-none">
        <div class="card-body py-2">
            <div class="it-mobile-sections">
                <button type="button" class="btn btn-sm" :class="activeSection === 'overview' ? 'btn-primary' : 'btn-outline-primary'" @click="activeSection = 'overview'">Info Asset</button>
                <button type="button" class="btn btn-sm" :class="activeSection === 'current' ? 'btn-primary' : 'btn-outline-primary'" @click="activeSection = 'current'">Pemakai</button>
                <button type="button" class="btn btn-sm" :class="activeSection === 'history' ? 'btn-primary' : 'btn-outline-primary'" @click="activeSection = 'history'">Riwayat</button>
            </div>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-lg-4" x-show="sectionVisible('overview')" x-cloak>
            <section class="card border-0 shadow-sm no-lift h-100">
                <div class="card-body">
                    <?php if (!empty($asset['photo'])): ?>
                        <img src="<?= base_url('uploads/assets/' . $asset['photo']) ?>" class="it-detail-photo mb-3" alt="<?= esc($asset['asset_name'] ?? 'Asset IT') ?>">
                    <?php else: ?>
                        <div class="it-detail-photo-placeholder mb-3">
                            <i class="bi bi-image"></i>
                            <span>Belum ada foto</span>
                        </div>
                    <?php endif; ?>

                    <div class="it-detail-kv">
                        <span>Status</span>
                        <strong><span class="badge text-bg-<?= esc($statusClass) ?>"><?= esc(ucfirst($asset['status'] ?? '-')) ?></span></strong>
                    </div>
                    <div class="it-detail-kv">
                        <span>Brand</span>
                        <strong><?= esc($asset['brand'] ?? '-') ?></strong>
                    </div>
                    <div class="it-detail-kv">
                        <span>Lokasi</span>
                        <strong><?= esc($asset['location'] ?? '-') ?></strong>
                    </div>
                    <div class="it-detail-kv">
                        <span>Serial Number</span>
                        <strong><?= esc($asset['serial_number'] ?? '-') ?></strong>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-8" x-show="sectionVisible('current') || sectionVisible('history')" x-cloak>
            <section class="card border-0 shadow-sm no-lift mb-3" x-show="sectionVisible('current')" x-cloak>
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Pemakai Saat Ini</h6>
                </div>
                <div class="card-body pt-2">
                    <?php if ($currentEmployee): ?>
                        <div class="it-detail-kv">
                            <span>Nama</span>
                            <strong><?= esc($currentEmployee->name) ?></strong>
                        </div>
                        <div class="it-detail-kv">
                            <span>ID Karyawan</span>
                            <strong><?= esc($currentEmployee->employee_id) ?></strong>
                        </div>
                        <div class="it-detail-kv">
                            <span>Divisi / Jabatan</span>
                            <strong><?= esc($currentEmployee->division) ?> - <?= esc($currentEmployee->position) ?></strong>
                        </div>
                        <div class="it-detail-kv">
                            <span>Mulai Pakai</span>
                            <strong><?= esc($currentEmployee->assigned_at) ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border mb-0">Asset ini belum di-assign ke karyawan.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card border-0 shadow-sm no-lift" x-show="sectionVisible('history')" x-cloak>
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-semibold mb-1">Riwayat Pemakaian</h6>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 it-table">
                            <thead>
                                <tr>
                                    <th width="56" class="text-center">No</th>
                                    <th>Nama</th>
                                    <th class="d-none d-md-table-cell">ID Karyawan</th>
                                    <th class="d-none d-lg-table-cell">Divisi / Jabatan</th>
                                    <th>Mulai</th>
                                    <th>Selesai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($history): ?>
                                    <?php $i = 1; ?>
                                    <?php foreach ($history as $h): ?>
                                        <tr>
                                            <td class="text-center"><?= $i++ ?></td>
                                            <td class="fw-semibold"><?= esc($h->name) ?></td>
                                            <td class="d-none d-md-table-cell"><?= esc($h->employee_id) ?></td>
                                            <td class="d-none d-lg-table-cell"><?= esc($h->division) ?> - <?= esc($h->position) ?></td>
                                            <td><?= esc($h->assigned_at) ?></td>
                                            <td>
                                                <?= $h->returned_at ? esc($h->returned_at) : '<span class="badge text-bg-success">Masih dipakai</span>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pemakaian.</td>
                                    </tr>
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

<?= $this->section('scripts') ?>
<script>
    window.IT_ASSET_DETAIL_BOOT = <?= json_encode([
        'inventoryNo' => (string) ($asset['inventory_no'] ?? ''),
    ], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('js/it-suite-alpine.js?v=' . filemtime(FCPATH . 'js/it-suite-alpine.js')) ?>"></script>
<?= $this->endSection() ?>
