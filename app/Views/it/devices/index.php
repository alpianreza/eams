<?= $this->extend('layouts/main') ?>

<?php
$cards = [
    ['key' => 'total', 'label' => 'Total Device', 'value' => $kpi['total'] ?? 0, 'icon' => 'bi-hdd-network', 'tone' => 'info'],
    ['key' => 'healthy', 'label' => 'Sehat', 'value' => $kpi['healthy'] ?? 0, 'icon' => 'bi-check-circle', 'tone' => 'success'],
    ['key' => 'warning', 'label' => 'Perlu Perhatian', 'value' => $kpi['warning'] ?? 0, 'icon' => 'bi-exclamation-circle', 'tone' => 'warning'],
    ['key' => 'critical', 'label' => 'Kritis', 'value' => $kpi['critical'] ?? 0, 'icon' => 'bi-x-circle', 'tone' => 'danger'],
    ['key' => 'offline', 'label' => 'Offline > 24 Jam', 'value' => $kpi['offline'] ?? 0, 'icon' => 'bi-wifi-off', 'tone' => 'secondary'],
    ['key' => 'update', 'label' => 'Perlu Update', 'value' => $kpi['update'] ?? 0, 'icon' => 'bi-arrow-repeat', 'tone' => 'primary'],
];
?>

<?= $this->section('content') ?>
<div
    class="it-shell"
    x-data="itDeviceIndex({
        tableUrl: '/it/devices/ajax',
        statsUrl: '/it/devices/stats',
        initialPerPage: 20
    })"
    x-init="init()"
>
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
        <template x-for="card in cards" :key="card.key">
            <div class="col-6 col-md-4 col-xl-2">
                <article class="card border-0 shadow-sm h-100 no-lift it-stat-card">
                    <div class="card-body py-3">
                        <span class="it-stat-label" x-text="card.label"></span>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <strong class="it-stat-value" x-text="card.value"></strong>
                            <span class="it-stat-icon" :class="'tone-' + card.tone">
                                <i class="bi" :class="card.icon"></i>
                            </span>
                        </div>
                    </div>
                </article>
            </div>
        </template>
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
                        x-model="q"
                        @input="handleSearchInput()"
                        placeholder="Cari hostname, user, atau sistem operasi">
                </div>
                <div class="col-lg-4">
                    <label for="devicePerPage" class="form-label form-label-sm">Baris per Halaman</label>
                    <select id="devicePerPage" class="form-select" x-model.number="perPage" @change="changePerPage()">
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="it-loading-state mb-3" id="deviceLoadingState" hidden>
                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            </div>

            <div
                id="deviceAjax"
                class="position-relative"
                @click="handleContainerClick($event)"
                x-html="tableHtml"
            ></div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/it-suite.css?v=' . filemtime(FCPATH . 'assets/css/it-suite.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    window.IT_DEVICE_INDEX_BOOT = <?= json_encode([
        'cards' => array_map(static fn(array $card) => [
            'key' => $card['key'],
            'label' => $card['label'],
            'value' => (int) $card['value'],
            'icon' => $card['icon'],
            'tone' => $card['tone'],
        ], $cards),
    ], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('js/it-device-live.js?v=' . filemtime(FCPATH . 'js/it-device-live.js')) ?>"></script>
<script src="<?= base_url('js/device-remote.js?v=' . filemtime(FCPATH . 'js/device-remote.js')) ?>"></script>
<?= $this->endSection() ?>
