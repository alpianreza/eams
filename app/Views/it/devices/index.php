<?= $this->extend('layouts/main') ?>

<?php
$cards = [
    ['Total Device', $kpi['total'] ?? 0, 'bi-hdd-network', 'info'],
    ['Sehat', $kpi['healthy'] ?? 0, 'bi-check-circle', 'success'],
    ['Perlu Perhatian', $kpi['warning'] ?? 0, 'bi-exclamation-circle', 'warning'],
    ['Kritis', $kpi['critical'] ?? 0, 'bi-x-circle', 'danger'],
    ['Offline > 24 Jam', $kpi['offline'] ?? 0, 'bi-wifi-off', 'secondary'],
    ['Perlu Update', $kpi['update'] ?? 0, 'bi-arrow-repeat', 'primary'],
];
?>

<?= $this->section('content') ?>
<div class="it-shell">
    <section class="card border-0 shadow-sm no-lift it-hero-card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="it-kicker mb-1">IT Monitoring</p>
                <h5 class="fw-bold mb-1">Pusat Kendali Device</h5>
                <p class="text-muted mb-0">Pantau kesehatan perangkat, status online, dan tindakan remote.</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('dashboard-it') ?>" class="btn btn-sm btn-outline-primary it-quick-btn">
                    <i class="bi bi-pie-chart me-1"></i> Dashboard IT
                </a>
                <a href="<?= base_url('it-assets') ?>" class="btn btn-sm btn-primary it-quick-btn">
                    <i class="bi bi-pc-display me-1"></i> Inventaris IT
                </a>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-3">
        <?php foreach ($cards as $card): ?>
            <div class="col-6 col-md-4 col-xl-2">
                <article class="card border-0 shadow-sm h-100 no-lift it-stat-card">
                    <div class="card-body py-3">
                        <span class="it-stat-label"><?= esc($card[0]) ?></span>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <strong class="it-stat-value"><?= (int) $card[1] ?></strong>
                            <span class="it-stat-icon tone-<?= esc($card[3]) ?>">
                                <i class="bi <?= esc($card[2]) ?>"></i>
                            </span>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="card border-0 shadow-sm no-lift">
        <div class="card-body">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-lg-8">
                    <label for="searchDevice" class="form-label form-label-sm">Cari Device</label>
                    <input
                        type="text"
                        id="searchDevice"
                        class="form-control"
                        placeholder="Cari hostname, user, atau sistem operasi">
                </div>
                <div class="col-lg-4">
                    <label for="devicePerPage" class="form-label form-label-sm">Baris per Halaman</label>
                    <select id="devicePerPage" class="form-select">
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="it-loading-state mb-3" id="deviceLoadingState" hidden>
                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                <span>Memuat data device...</span>
            </div>

            <div id="deviceAjax" class="position-relative"></div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/it-suite.css?v=' . filemtime(FCPATH . 'assets/css/it-suite.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/it-devices.js?v=' . filemtime(FCPATH . 'js/it-devices.js')) ?>"></script>
<script src="<?= base_url('js/device-remote.js?v=' . filemtime(FCPATH . 'js/device-remote.js')) ?>"></script>
<?= $this->endSection() ?>
