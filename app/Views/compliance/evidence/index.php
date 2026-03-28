<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$currentYear = (int) date('Y');
$startYear = max(2024, $currentYear - 5);
?>

<div id="evidencePage"
  class="evidence-page"
  data-url-ajax="<?= site_url('compliance/evidence/ajax') ?>"
  data-url-detail-base="<?= site_url('compliance/evidence/detail') ?>"
  data-url-update="<?= site_url('compliance/evidence/update-followup') ?>">

  <section class="card border-0 shadow-sm evidence-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="evidence-kicker mb-1">Compliance Evidence</p>
        <h5 class="mb-1 fw-bold">Evidence Center</h5>
        <p class="text-muted mb-0">Pantau temuan <strong>Tidak sesuai</strong> beserta progres tindak lanjutnya.</p>
      </div>

      <div class="evidence-summary text-muted small">
        Fokus pada item dengan foto temuan untuk tindak lanjut lebih cepat.
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm evidence-filter-card no-lift mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label for="filterYear" class="form-label form-label-sm mb-1">Tahun</label>
          <select id="filterYear" class="form-select form-select-sm">
            <option value="">Semua Tahun</option>
            <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
              <option value="<?= $year ?>"><?= $year ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="col-12 col-md-4">
          <label for="filterItem" class="form-label form-label-sm mb-1">Item Checklist</label>
          <select id="filterItem" class="form-select form-select-sm">
            <option value="">Semua Item</option>
            <?php foreach ($itemTypes as $item): ?>
              <option value="<?= (int) $item['id'] ?>"><?= esc($item['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label for="filterFollowUp" class="form-label form-label-sm mb-1">Status Tindak Lanjut</label>
          <select id="filterFollowUp" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <option value="open">Open</option>
            <option value="monitoring">Monitoring</option>
            <option value="closed">Closed</option>
          </select>
        </div>

        <div class="col-12 col-md-2 d-grid">
          <button type="button" class="btn btn-outline-danger btn-sm" id="btnEvidenceReset">
            Reset Filter
          </button>
        </div>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body">
      <div id="evidenceAjax" class="evidence-grid-wrap">
        <div class="evidence-grid-state text-center p-4">
          Menyiapkan data evidence...
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade evidence-modal" id="evidenceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Evidence</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body" id="evidenceDetailBody">
        <div class="text-center p-4">Memuat detail evidence...</div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/evidence.css?v=' . filemtime(FCPATH . 'assets/css/evidence.css')) ?>">
<?= $this->endSection() ?>
