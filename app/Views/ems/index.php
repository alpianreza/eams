<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="ems-page">
  <section class="card border-0 shadow-sm ems-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="ems-kicker mb-1">EMS Report</p>
        <h5 class="fw-bold mb-1">Environmental Monitoring Report</h5>
        <p class="text-muted mb-0">Kelola report EMS bulanan per jenis konsumsi dan utilitas.</p>
      </div>
    </div>
  </section>

  <div class="row g-3">
    <?php foreach ($reports as $report): ?>
      <?php $isActive = ($report['status'] ?? 'soon') === 'active'; ?>
      <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100 no-lift ems-report-card <?= $isActive ? 'is-active' : 'is-soon' ?>">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <div>
                <div class="ems-card-title"><?= esc($report['title']) ?></div>
                <div class="text-muted small"><?= esc($report['subtitle']) ?></div>
              </div>
              <span class="badge text-bg-<?= $isActive ? 'success' : 'secondary' ?>">
                <?= $isActive ? 'Ready' : 'Soon' ?>
              </span>
            </div>
            <div class="mt-auto pt-3">
              <?php if ($isActive): ?>
                <a href="<?= esc($report['href']) ?>" class="btn btn-primary btn-sm">
                  <i class="bi bi-droplet me-1"></i> Buka Report
                </a>
              <?php else: ?>
                <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                  Menyusul
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/ems-report.css?v=' . filemtime(FCPATH . 'assets/css/ems-report.css')) ?>">
<?= $this->endSection() ?>