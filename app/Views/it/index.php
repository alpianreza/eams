<?= $this->extend('layouts/main') ?>

<?php
$workspaceCards = [
    [
        'href' => base_url('dashboard-it'),
        'kicker' => 'Ringkasan',
        'title' => 'Dashboard IT',
        'body' => 'Lihat statistik asset, status, dan pemakaian aktif.',
    ],
    [
        'href' => base_url('it-assets'),
        'kicker' => 'Asset',
        'title' => 'Inventaris IT',
        'body' => 'Kelola data device, status, lokasi, dan assignment.',
    ],
    [
        'href' => base_url('it/devices'),
        'kicker' => 'Monitoring',
        'title' => 'Device Control',
        'body' => 'Pantau status online, risiko, dan aksi remote perangkat.',
    ],
    [
        'href' => base_url('employees'),
        'kicker' => 'Karyawan',
        'title' => 'Pemegang IT',
        'body' => 'Lihat data pemegang asset dan alokasi perangkat.',
    ],
];
?>

<?= $this->section('content') ?>
<div class="it-shell" x-data="itWorkspaceHome(window.IT_WORKSPACE_BOOT || [])">
    <section class="card border-0 shadow-sm no-lift it-hero-card mb-3">
        <div class="card-body">
            <p class="it-kicker mb-1">IT Workspace</p>
            <h5 class="fw-bold mb-1">IT Center</h5>
            <p class="text-muted mb-3">Pusat akses cepat untuk dashboard IT, inventaris asset, dan monitoring device.</p>
            <div class="it-workspace-search">
                <label for="itWorkspaceSearch" class="form-label form-label-sm">Cari menu IT</label>
                <input id="itWorkspaceSearch" type="text" class="form-control" x-model="query" placeholder="Contoh: device, asset, dashboard">
            </div>
        </div>
    </section>

    <div class="row g-3">
        <template x-for="card in filteredCards" :key="card.href">
            <div class="col-md-6 col-xl-3">
                <a :href="card.href" class="card border-0 shadow-sm h-100 text-decoration-none no-lift it-workspace-card">
                    <div class="card-body">
                        <span class="it-stat-label" x-text="card.kicker"></span>
                        <h6 class="fw-semibold text-dark mt-1 mb-2" x-text="card.title"></h6>
                        <p class="small text-muted mb-0" x-text="card.body"></p>
                    </div>
                </a>
            </div>
        </template>

        <div class="col-12" x-show="filteredCards.length === 0" x-cloak>
            <div class="card border-0 shadow-sm no-lift">
                <div class="card-body text-muted">Menu IT yang kamu cari belum ada di daftar ini.</div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/it-suite.css?v=' . filemtime(FCPATH . 'assets/css/it-suite.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    window.IT_WORKSPACE_BOOT = <?= json_encode($workspaceCards, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('js/it-suite-alpine.js?v=' . filemtime(FCPATH . 'js/it-suite-alpine.js')) ?>"></script>
<?= $this->endSection() ?>
