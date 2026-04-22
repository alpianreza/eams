<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $monthInput = date('Y-m', strtotime($ym . '-01')); ?>

<div
  class="sd-grid-page"
  data-save-url="<?= esc($saveUrl) ?>"
  data-bulk-url="/compliance/checklist/smoke-detector-grid/mark-all"
  data-period-key="<?= esc($ym) ?>"
  data-item-label="Smoke Detector"
  data-csrf-name="<?= esc($csrfName) ?>"
  data-csrf-hash="<?= esc($csrfHash) ?>">

  <section class="card border-0 shadow-sm checklist-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-kicker mb-1">Checklist Compliance</p>
        <h5 class="mb-1 fw-bold">Checklist Smoke Detector</h5>
        <p class="text-muted mb-0">
          Grid bulanan mengikuti format print. Urutan lokasi memakai <strong>specific area</strong>.
        </p>
      </div>

      <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        <input type="month" name="ym" value="<?= esc($monthInput) ?>" class="form-control form-control-sm" onchange="this.form.submit()">
      </form>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <div class="fw-semibold">Periode <?= esc($monthLabel) ?></div>
        <div class="text-muted small">Klik sel untuk putar: <strong>Sesuai</strong>, <strong>Tidak Sesuai</strong>, lalu kosong lagi.</div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center small">
        <button type="button" class="btn btn-success btn-sm sd-mark-all-btn">
          <i class="bi bi-check2-square"></i>
          Centang Semua
        </button>
        <span class="sd-legend-pill"><span class="legend-box is-ok"></span>Sesuai</span>
        <span class="sd-legend-pill"><span class="legend-box is-not-ok"></span>Tidak Sesuai</span>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body p-0">
      <div class="table-responsive sd-grid-wrap">
        <table class="table table-bordered align-middle mb-0 sd-grid-table">
          <thead>
            <tr>
              <th class="sticky-left sticky-no">No.</th>
              <th class="sticky-left sticky-location">Lokasi</th>
              <?php foreach ($questions as $question): ?>
                <th class="col-question vertical-head">
                  <span><?= esc($question['question']) ?></span>
                </th>
              <?php endforeach; ?>
              <th class="col-note">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr<?= $focusId === (int) $row['id'] ? ' class="is-focused"' : '' ?>>
                <td class="sticky-left sticky-no text-center"><?= (int) $row['no'] ?></td>
                <td class="sticky-left sticky-location">
                  <a href="<?= esc($row['detail_url']) ?>" class="text-decoration-none fw-semibold text-dark">
                    <?= esc($row['location']) ?>
                  </a>
                </td>
                <?php foreach ($questions as $question): ?>
                  <?php
                  $templateId = (int) ($question['id'] ?? 0);
                  $log = $row['checks'][$templateId] ?? null;
                  $state = strtolower((string) ($log['status'] ?? 'empty'));
                  $cellClass = 'is-empty';
                  if ($state === 'ok') {
                    $cellClass = 'is-ok';
                  } elseif ($state === 'not_ok') {
                    $cellClass = 'is-not-ok';
                  }
                  ?>
                  <td
                    class="sd-check-cell <?= esc($cellClass) ?>"
                    data-inventory-id="<?= (int) $row['id'] ?>"
                    data-template-id="<?= $templateId ?>"
                    data-period-key="<?= esc($ym) ?>"
                    data-state="<?= esc($state) ?>"
                    title="<?= esc($row['location'] . ' - ' . ($question['question'] ?? '')) ?>">
                    <?php if ($state === 'ok'): ?>
                      <i class="bi bi-check-lg"></i>
                    <?php elseif ($state === 'not_ok'): ?>
                      <i class="bi bi-x-lg"></i>
                    <?php else: ?>
                      <span class="sd-cell-mark"></span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
                <td class="col-note"></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/checklist.css?v=<?= filemtime(FCPATH . 'assets/css/checklist.css') ?>">
<link rel="stylesheet" href="/assets/css/smoke-detector-grid.css?v=<?= filemtime(FCPATH . 'assets/css/smoke-detector-grid.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/smoke-detector-grid.js?v=<?= filemtime(FCPATH . 'js/smoke-detector-grid.js') ?>"></script>
<?= $this->endSection() ?>
