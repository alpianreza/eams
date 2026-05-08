<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$monthInput = date('Y-m', strtotime($ym . '-01'));
?>

<div
  class="gate-grid-page"
  data-save-url="<?= esc($saveUrl) ?>"
  data-bulk-url="<?= esc($bulkUrl) ?>"
  data-inventory-id="<?= (int) ($inventory['id'] ?? 0) ?>"
  data-ym="<?= esc($ym) ?>"
  data-csrf-name="<?= esc($csrfName) ?>"
  data-csrf-hash="<?= esc($csrfHash) ?>">

  <section class="card border-0 shadow-sm checklist-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-kicker mb-1">Checklist Compliance</p>
        <h5 class="mb-1 fw-bold">Checklist Gerbang</h5>
        <p class="text-muted mb-0">
          Grid harian untuk 1 inventory gerbang. Baris = item pengecekan, kolom = tanggal dalam bulan.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
          <input type="month" name="ym" value="<?= esc($monthInput) ?>" class="form-control form-control-sm" onchange="this.form.submit()">
        </form>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <div class="fw-semibold"><?= esc($inventory['asset_code'] ?? '-') ?> - <?= esc($inventory['specific_area'] ?? '-') ?></div>
        <div class="fw-semibold">Periode <?= esc($monthLabel) ?></div>
        <div class="text-muted small">Klik sel untuk putar: <strong>Sesuai</strong>, <strong>Tidak Sesuai</strong>, lalu kosong lagi.</div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center small">
        <button type="button" class="btn btn-success btn-sm gate-mark-all-btn">
          <i class="bi bi-check2-square"></i>
          Centang Semua
        </button>
        <span class="gate-legend-pill"><span class="legend-box is-ok"></span>Sesuai</span>
        <span class="gate-legend-pill"><span class="legend-box is-not-ok"></span>Tidak Sesuai</span>
        <span class="gate-legend-pill"><span class="legend-box is-offday"></span>Libur</span>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body p-0">
      <div class="table-responsive gate-grid-wrap">
        <table class="table table-bordered align-middle mb-0 gate-grid-table">
          <thead>
            <tr>
              <th class="sticky-left sticky-no">No</th>
              <th class="sticky-left sticky-question">Item Pengecekan</th>
              <?php foreach ($days as $day): ?>
                <th class="<?= !empty($day['is_offday']) ? 'is-offday' : '' ?>">
                  <?= esc((string) date('j', strtotime($day['period_key']))) ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td class="sticky-left sticky-no text-center"><?= (int) $row['row_no'] ?></td>
                <td class="sticky-left sticky-question">
                  <a href="<?= esc($row['detail_url']) ?>" class="text-decoration-none fw-semibold text-dark">
                    <?= esc($row['question']) ?>
                  </a>
                </td>

                <?php foreach ($days as $day): ?>
                  <?php
                  $periodKey = (string) ($day['period_key'] ?? '');
                  $isOffday = !empty($day['is_offday']);
                  $log = $row['checks'][$periodKey] ?? null;
                  $state = strtolower((string) ($log['status'] ?? 'empty'));
                  $cellClass = 'is-empty';
                  if ($isOffday) {
                    $cellClass = 'is-offday';
                  } elseif ($state === 'ok') {
                    $cellClass = 'is-ok';
                  } elseif ($state === 'not_ok') {
                    $cellClass = 'is-not-ok';
                  }
                  ?>
                  <td
                    class="gate-check-cell <?= esc($cellClass) ?>"
                    data-inventory-id="<?= (int) $row['inventory_id'] ?>"
                    data-template-id="<?= (int) $row['question_id'] ?>"
                    data-period-key="<?= esc($periodKey) ?>"
                    data-state="<?= esc($state) ?>"
                    data-offday="<?= $isOffday ? '1' : '0' ?>"
                    title="<?= esc(($inventory['specific_area'] ?? '-') . ' - ' . $row['question'] . ' - ' . date('d M Y', strtotime($periodKey))) ?>">
                    <?php if ($isOffday): ?>
                      <span class="gate-cell-mark"></span>
                    <?php elseif ($state === 'ok'): ?>
                      <i class="bi bi-check-lg"></i>
                    <?php elseif ($state === 'not_ok'): ?>
                      <i class="bi bi-x-lg"></i>
                    <?php else: ?>
                      <span class="gate-cell-mark"></span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
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
<link rel="stylesheet" href="/assets/css/gate-grid.css?v=<?= filemtime(FCPATH . 'assets/css/gate-grid.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/gate-grid.js?v=<?= filemtime(FCPATH . 'js/gate-grid.js') ?>"></script>
<?= $this->endSection() ?>
