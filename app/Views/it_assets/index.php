<?= $this->extend('layouts/main') ?>

<?php
$role       = session()->get('role');
$permission = session()->get('permission');
$isWritable = ($permission === 'write' || $role === 'admin');

$filterItems = [
    ['value' => '', 'label' => 'Semua IT'],
    ['value' => 'Komputer', 'label' => 'Komputer'],
    ['value' => 'Laptop', 'label' => 'Laptop'],
    ['value' => 'Peripheral', 'label' => 'Peripheral'],
    ['value' => 'Network', 'label' => 'Network Device'],
];
?>

<?= $this->section('content') ?>
<div
    class="it-shell"
    x-data="itAssetIndex(window.IT_ASSET_INDEX_BOOT || {})"
    x-init="init()"
>
    <section class="card border-0 shadow-sm no-lift it-hero-card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="it-kicker mb-1">IT Asset</p>
                <h5 class="fw-bold mb-1">Inventaris IT</h5>
                <p class="text-muted mb-0">Kelola data asset IT, status, lokasi, dan pemakaian karyawan.</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <?php if ($isWritable): ?>
                    <a href="<?= base_url('it-assets/create') ?>" class="btn btn-sm btn-primary it-quick-btn">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Asset
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('dashboard-it') ?>" class="btn btn-sm btn-outline-primary it-quick-btn">
                    <i class="bi bi-pie-chart me-1"></i> Dashboard IT
                </a>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm no-lift mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <template x-for="item in filterItems" :key="item.value || 'all'">
                    <button
                        type="button"
                        class="btn btn-sm rounded-pill px-3"
                        :class="activeType === item.value ? 'btn-primary' : 'btn-outline-primary'"
                        @click="setType(item.value)"
                        x-text="item.label"
                    ></button>
                </template>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm no-lift">
        <div class="card-body">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-lg-8">
                    <label for="assetSearch" class="form-label form-label-sm">Cari Asset</label>
                    <input
                        id="assetSearch"
                        type="text"
                        class="form-control"
                        placeholder="Cari no inventaris, nama asset, brand, atau lokasi"
                        x-model="q"
                        @input="handleSearchInput()"
                    >
                </div>

                <div class="col-lg-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-7 col-md-8">
                            <label for="perPageSelect" class="form-label form-label-sm">Baris per Halaman</label>
                            <select id="perPageSelect" class="form-select" x-model.number="perPage" @change="changePerPage()">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col-5 col-md-4 d-grid" x-show="q || activeType" x-cloak>
                            <button type="button" class="btn btn-outline-secondary" @click="resetFilters()">Reset</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="it-loading-state mb-3" x-show="loading" x-cloak>
                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            </div>

            <div id="itAssetAjax" @click="handleContainerClick($event)" x-html="tableHtml"></div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/it-suite.css?v=' . filemtime(FCPATH . 'assets/css/it-suite.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    window.IT_ASSET_INDEX_BOOT = <?= json_encode([
        'tableUrl' => '/it-assets/ajax',
        'initialType' => (string) $type,
        'initialQuery' => (string) $keyword,
        'initialPerPage' => (int) $perPage,
        'initialTableHtml' => view('it_assets/_table', ['assets' => $assets, 'pager' => $pager]),
        'filterItems' => $filterItems,
    ], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('js/it-suite-alpine.js?v=' . filemtime(FCPATH . 'js/it-suite-alpine.js')) ?>"></script>
<?= $this->endSection() ?>
