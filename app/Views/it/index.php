<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="it-shell">
    <section class="card border-0 shadow-sm no-lift it-hero-card mb-3">
        <div class="card-body">
            <p class="it-kicker mb-1">IT Workspace</p>
            <h5 class="fw-bold mb-1">IT Center</h5>
            <p class="text-muted mb-0">Pusat akses cepat untuk dashboard IT, inventaris asset, dan monitoring device.</p>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <a href="<?= base_url('dashboard-it') ?>" class="card border-0 shadow-sm h-100 text-decoration-none no-lift">
                <div class="card-body">
                    <span class="it-stat-label">Ringkasan</span>
                    <h6 class="fw-semibold text-dark mt-1 mb-2">Dashboard IT</h6>
                    <p class="small text-muted mb-0">Lihat statistik asset, status, dan pemakaian aktif.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="<?= base_url('it-assets') ?>" class="card border-0 shadow-sm h-100 text-decoration-none no-lift">
                <div class="card-body">
                    <span class="it-stat-label">Asset</span>
                    <h6 class="fw-semibold text-dark mt-1 mb-2">Inventaris IT</h6>
                    <p class="small text-muted mb-0">Kelola data device, status, lokasi, dan assignment.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="<?= base_url('it/devices') ?>" class="card border-0 shadow-sm h-100 text-decoration-none no-lift">
                <div class="card-body">
                    <span class="it-stat-label">Monitoring</span>
                    <h6 class="fw-semibold text-dark mt-1 mb-2">Device Control</h6>
                    <p class="small text-muted mb-0">Pantau status online, risiko, dan aksi remote perangkat.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="<?= base_url('employees') ?>" class="card border-0 shadow-sm h-100 text-decoration-none no-lift">
                <div class="card-body">
                    <span class="it-stat-label">Karyawan</span>
                    <h6 class="fw-semibold text-dark mt-1 mb-2">Pemegang IT</h6>
                    <p class="small text-muted mb-0">Lihat data pemegang asset dan alokasi perangkat.</p>
                </div>
            </a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/it-suite.css?v=' . filemtime(FCPATH . 'assets/css/it-suite.css')) ?>">
<?= $this->endSection() ?>
