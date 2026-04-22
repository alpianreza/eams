<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$monthInput = date('Y-m', strtotime($ym . '-01'));
?>

<div
  class="hyd-grid-page"
  data-save-url="<?= esc($saveUrl) ?>"
  data-csrf-name="<?= esc($csrfName) ?>"
  data-csrf-hash="<?= esc($csrfHash) ?>">

  <section class="card border-0 shadow-sm checklist-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-kicker mb-1">Checklist Compliance</p>
        <h5 class="mb-1 fw-bold">Pengecekan Hydran Per Minggu</h5>
        <p class="text-muted mb-0">
          Grid mingguan Hydrant dengan struktur sama seperti format print: baris pertanyaan, kolom `Hydrant 1..6` dengan minggu `1-4`.
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
        <div class="text-muted small">Minggu `1-4` mewakili `W1-W4` pada bulan aktif.</div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center small">
        <span class="hyd-legend-pill"><span class="legend-box is-ok"></span>Sesuai</span>
        <span class="hyd-legend-pill"><span class="legend-box is-not-ok"></span>Tidak Sesuai</span>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body p-0">
      <div class="table-responsive hyd-grid-wrap">
        <table class="table table-bordered align-middle mb-0 hyd-grid-table">
          <thead>
            <tr>
              <th rowspan="3" class="sticky-left sticky-no">No</th>
              <th rowspan="3" class="sticky-left sticky-question">Keterangan</th>
              <th colspan="<?= count($hydrants) * 4 ?>" class="month-band">BULAN: <?= esc($monthLabel) ?></th>
              <th rowspan="3" class="col-note">KET</th>
            </tr>
            <tr>
              <?php foreach ($hydrants as $hydrant): ?>
                <th colspan="4" class="question-head">
                  <a href="<?= esc($hydrant['detail_url']) ?>" class="text-decoration-none text-dark">
                    <?= esc($hydrant['label']) ?>
                  </a>
                </th>
              <?php endforeach; ?>
            </tr>
            <tr>
              <?php foreach ($hydrants as $hydrant): ?>
                <?php foreach ([1, 2, 3, 4] as $weekNumber): ?>
                  <th class="week-head col-week"><?= $weekNumber ?></th>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($questions as $index => $question): ?>
              <?php $templateId = (int) ($question['id'] ?? 0); ?>
              <tr>
                <td class="sticky-left sticky-no text-center"><?= $index + 1 ?></td>
                <td class="sticky-left sticky-question"><?= esc(trim((string) ($question['question'] ?? '')) !== '' ? $question['question'] : '-') ?></td>

                <?php foreach ($hydrants as $hydrant): ?>
                  <?php foreach ([1, 2, 3, 4] as $weekNumber): ?>
                    <?php
                    $log = $logMap[$templateId][$hydrant['id']][$weekNumber] ?? null;
                    $state = strtolower((string) ($log['status'] ?? 'empty'));
                    $cellClass = 'is-empty';
                    if ($state === 'ok') {
                      $cellClass = 'is-ok';
                    } elseif ($state === 'not_ok') {
                      $cellClass = 'is-not-ok';
                    }
                    ?>
                    <td
                      class="hyd-check-cell <?= esc($cellClass) ?>"
                      data-inventory-id="<?= (int) $hydrant['id'] ?>"
                      data-template-id="<?= $templateId ?>"
                      data-period-key="<?= esc($ym . '-W' . $weekNumber) ?>"
                      data-state="<?= esc($state) ?>"
                      title="<?= esc(($question['question'] ?? '-') . ' - ' . $hydrant['label'] . ' W' . $weekNumber) ?>">
                      <?php if ($state === 'ok'): ?>
                        <i class="bi bi-check-lg"></i>
                      <?php elseif ($state === 'not_ok'): ?>
                        <i class="bi bi-x-lg"></i>
                      <?php else: ?>
                        <span class="hyd-cell-mark"></span>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                <?php endforeach; ?>

                <td class="col-note"></td>
              </tr>
            <?php endforeach; ?>
            <tr>
              <td colspan="2" class="sticky-left sticky-footer">Temuan</td>
              <td colspan="<?= count($hydrants) * 4 + 1 ?>"></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/checklist.css?v=<?= filemtime(FCPATH . 'assets/css/checklist.css') ?>">
<link rel="stylesheet" href="/assets/css/hydrant-grid.css?v=<?= filemtime(FCPATH . 'assets/css/hydrant-grid.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/hydrant-grid.js?v=<?= filemtime(FCPATH . 'js/hydrant-grid.js') ?>"></script>
<?= $this->endSection() ?>
