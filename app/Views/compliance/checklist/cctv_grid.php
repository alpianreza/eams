<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$monthInput = date('Y-m', strtotime($ym . '-01'));
?>

<div
  class="cctv-grid-page"
  data-save-url="<?= esc($saveUrl) ?>"
  data-csrf-name="<?= esc($csrfName) ?>"
  data-csrf-hash="<?= esc($csrfHash) ?>">

  <section class="card border-0 shadow-sm checklist-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-kicker mb-1">Checklist Compliance</p>
        <h5 class="mb-1 fw-bold">Pemeriksaan & Perawatan CCTV</h5>
        <p class="text-muted mb-0">
          Mode grid bulanan untuk CCTV. Klik sel kerja untuk tandai <strong>Sesuai</strong>.
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
        <div class="fw-semibold">Periode <?= esc($monthLabel) ?></div>
        <div class="text-muted small">
          Pertanyaan aktif: <strong><?= esc($question['question'] ?? '-') ?></strong>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center small">
        <span class="cctv-legend-pill"><span class="legend-box is-ok"></span>Sesuai</span>
        <span class="cctv-legend-pill"><span class="legend-box is-offday"></span>Libur</span>
        <span class="cctv-legend-pill"><span class="legend-box is-alert"></span>Non-OK dari detail</span>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body p-0">
      <div class="table-responsive cctv-grid-wrap">
        <table class="table table-bordered align-middle mb-0 cctv-grid-table">
          <thead>
            <tr>
              <th class="sticky-left sticky-no">No</th>
              <th class="sticky-left sticky-name">Jenis Pemeriksa</th>
              <th class="sticky-left sticky-location">Lokasi</th>
              <?php foreach ($days as $day): ?>
                <?php
                $date = $day['period_key'];
                $isOffday = is_date_offday($date, $holidayDates ?? []);
                ?>
                <th class="<?= $isOffday ? 'is-offday' : '' ?>"><?= esc((string) date('j', strtotime($date))) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr<?= $focusId === (int) $row['id'] ? ' class="is-focused"' : '' ?>>
                <td class="sticky-left sticky-no text-center"><?= (int) $row['no'] ?></td>
                <td class="sticky-left sticky-name">
                  <a href="<?= esc($row['detail_url']) ?>" class="text-decoration-none fw-semibold text-dark">
                    <?= esc($row['display_name']) ?>
                  </a>
                </td>
                <td class="sticky-left sticky-location"><?= esc($row['location']) ?></td>

                <?php foreach ($days as $day): ?>
                  <?php
                  $date = $day['period_key'];
                  $isOffday = is_date_offday($date, $holidayDates ?? []);
                  $log = $row['checks'][$date] ?? null;
                  $state = strtolower((string) ($log['status'] ?? 'empty'));
                  $cellClass = 'is-empty';
                  if ($isOffday) {
                    $cellClass = 'is-offday';
                  } elseif ($state === 'ok') {
                    $cellClass = 'is-ok';
                  } elseif ($state === 'not_ok' || $state === 'na') {
                    $cellClass = 'is-alert';
                  }
                  ?>
                  <td
                    class="cctv-check-cell <?= esc($cellClass) ?>"
                    data-inventory-id="<?= (int) $row['id'] ?>"
                    data-period-key="<?= esc($date) ?>"
                    data-state="<?= esc($state) ?>"
                    data-offday="<?= $isOffday ? '1' : '0' ?>"
                    data-detail-url="<?= esc($row['detail_url']) ?>"
                    title="<?= esc($row['display_name'] . ' - ' . date('d M Y', strtotime($date))) ?>">
                    <?php if ($isOffday): ?>
                      <span class="cctv-cell-mark"></span>
                    <?php elseif ($state === 'ok'): ?>
                      <i class="bi bi-check-lg"></i>
                    <?php elseif ($state === 'not_ok'): ?>
                      <i class="bi bi-exclamation-lg"></i>
                    <?php elseif ($state === 'na'): ?>
                      <i class="bi bi-dash-lg"></i>
                    <?php else: ?>
                      <span class="cctv-cell-mark"></span>
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
<link rel="stylesheet" href="/assets/css/cctv-grid.css?v=<?= filemtime(FCPATH . 'assets/css/cctv-grid.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.CCTV_GRID_FLASH = {
    user: <?= json_encode($currentUser) ?>,
  };
</script>
<script src="/js/cctv-grid.js?v=<?= filemtime(FCPATH . 'js/cctv-grid.js') ?>"></script>
<?= $this->endSection() ?>
