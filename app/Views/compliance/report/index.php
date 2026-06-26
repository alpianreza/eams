<?= $this->extend('layouts/main') ?>

<?php
$title = 'Laporan Compliance';
$monthNames = [
  1 => 'Januari',
  2 => 'Februari',
  3 => 'Maret',
  4 => 'April',
  5 => 'Mei',
  6 => 'Juni',
  7 => 'Juli',
  8 => 'Agustus',
  9 => 'September',
  10 => 'Oktober',
  11 => 'November',
  12 => 'Desember',
];
?>

<?= $this->section('content') ?>
<div class="compliance-report-page">
  <section class="card border-0 shadow-sm report-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="report-kicker mb-1">Laporan Compliance</p>
        <h5 class="mb-1 fw-bold">Laporan Rekap Checklist</h5>
        <p class="text-muted mb-0">Pilih item dan periode untuk melihat rekap checklist serta temuan.</p>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm report-filter-card no-lift mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label for="categorySelect" class="form-label form-label-sm mb-1">Kategori</label>
          <select id="categorySelect" class="form-select form-select-sm">
            <option value="">Pilih Kategori</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>"><?= esc($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label for="itemTypeSelect" class="form-label form-label-sm mb-1">Nama Item</label>
          <select id="itemTypeSelect" class="form-select form-select-sm" disabled>
            <option value="">Pilih Item</option>
          </select>
        </div>

        <div class="col-md-2">
          <label for="inventorySelect" class="form-label form-label-sm mb-1">No Inventaris</label>
          <select id="inventorySelect" class="form-select form-select-sm" disabled>
            <option value="">Pilih No Inventaris</option>
          </select>
        </div>

        <div class="col-md-1">
          <label for="yearSelect" class="form-label form-label-sm mb-1">Tahun</label>
          <select id="yearSelect" class="form-select form-select-sm">
            <?php for ($y = 2026; $y <= (int) date('Y'); $y++): ?>
              <option value="<?= (int) $y ?>" <?= (int) date('Y') === (int) $y ? 'selected' : '' ?>><?= (int) $y ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label for="monthSelect" class="form-label form-label-sm mb-1">Bulan</label>
          <select id="monthSelect" class="form-select form-select-sm">
            <?php foreach ($monthNames as $num => $name): ?>
              <option value="<?= (int) $num ?>" <?= (int) date('n') === (int) $num ? 'selected' : '' ?>>
                <?= esc($name) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-1 d-grid">
          <button id="loadReport" class="btn btn-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1">
            <i class="bi bi-search"></i>
            <span class="d-none d-md-inline">Muat</span>
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm report-result-card no-lift">
    <div class="card-body position-relative">
      <div id="reportLoading" class="report-loading d-none">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></div>
        Memuat laporan...
      </div>

      <div id="reportContainer" class="report-container">
        <div class="text-center text-muted py-5">
          Pilih kategori, item, dan periode lalu klik tombol <strong>Muat</strong>.
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content report-image-modal-content">
      <div class="modal-header border-0 pb-0">
        <h6 class="modal-title">Preview Foto Temuan</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body text-center pt-2">
        <img id="previewImage" src="" class="img-fluid rounded" alt="Preview temuan">
      </div>
    </div>
  </div>
</div>

<a id="exportFloating" class="btn btn-danger eams-export-float compliance-report-export-float d-none" target="_blank">
  <i class="bi bi-file-earmark-pdf me-1"></i>
  Export PDF
</a>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/compliance-report.css?v=' . filemtime(FCPATH . 'assets/css/compliance-report.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.REPORT_CONFIG = {
    baseUrl: "<?= rtrim(base_url(), '/') ?>",
    itemByCategoryUrl: "<?= base_url('compliance/report/item-by-category') ?>",
    inventoryByTypeUrl: "<?= base_url('compliance/report/inventory-by-type') ?>",
    loadUrl: "<?= base_url('compliance/report/load') ?>",
    exportBaseUrl: "<?= rtrim(base_url('export/pdf/recap'), '/') ?>"
  };
</script>
<script src="<?= base_url('js/compliance-report.js?v=' . filemtime(FCPATH . 'js/compliance-report.js')) ?>"></script>
<?= $this->endSection() ?>
