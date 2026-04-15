<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="ems-page" x-data="emsReportIndex(window.EMS_REPORT_INDEX_BOOT || {})">
  <section class="card border-0 shadow-sm ems-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="ems-kicker mb-1">EMS Report</p>
        <h5 class="fw-bold mb-1">Environmental Monitoring Report</h5>
        <p class="text-muted mb-0">Kelola report EMS bulanan per jenis konsumsi dan utilitas.</p>
      </div>
      <div class="ems-inline-search">
        <label for="emsReportSearch" class="form-label form-label-sm">Cari report</label>
        <input id="emsReportSearch" type="text" class="form-control" x-model="query" placeholder="Cari berdasarkan nama report...">
      </div>
    </div>
  </section>

  <div class="row g-3">
    <template x-for="report in filteredReports" :key="report.title">
      <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100 no-lift ems-report-card" :class="report.status === 'active' ? 'is-active' : 'is-soon'">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <div>
                <div class="ems-card-title" x-text="report.title"></div>
                <div class="text-muted small" x-text="report.subtitle"></div>
              </div>
              <span class="badge" :class="report.status === 'active' ? 'text-bg-success' : 'text-bg-secondary'" x-text="report.status === 'active' ? 'Ready' : 'Soon'"></span>
            </div>
            <div class="ems-card-icon mb-3">
              <i class="bi" :class="report.icon"></i>
            </div>
            <div class="mt-auto pt-3">
              <template x-if="report.status === 'active'">
                <a :href="report.href" class="btn btn-primary btn-sm">
                  <i class="bi bi-box-arrow-up-right me-1"></i> Buka Report
                </a>
              </template>
              <template x-if="report.status !== 'active'">
                <button type="button" class="btn btn-outline-secondary btn-sm" disabled>Menyusul</button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div class="col-12" x-show="filteredReports.length === 0" x-cloak>
      <div class="card border-0 shadow-sm no-lift">
        <div class="card-body text-muted">Report EMS yang dicari belum ada.</div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/ems-report.css?v=<?= filemtime(FCPATH . 'assets/css/ems-report.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.EMS_REPORT_INDEX_BOOT = <?= json_encode(['reports' => $reports], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/js/ems-report.js?v=<?= filemtime(FCPATH . 'js/ems-report.js') ?>"></script>
<?= $this->endSection() ?>