<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$monthInput = date('Y-m', strtotime($ym . '-01'));
?>

<div
  class="fac-grid-page"
  data-save-url="<?= esc($saveUrl) ?>"
  data-bulk-url="/compliance/checklist/first-aid-content-grid/mark-all"
  data-inventory-id="<?= (int) ($inventory['id'] ?? 0) ?>"
  data-ym="<?= esc($ym) ?>"
  data-csrf-name="<?= esc($csrfName) ?>"
  data-csrf-hash="<?= esc($csrfHash) ?>">

  <section class="card border-0 shadow-sm checklist-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-kicker mb-1">Checklist Compliance</p>
        <h5 class="mb-1 fw-bold">First Aid Kit Content Grid</h5>
        <p class="text-muted mb-0">
          Grid harian untuk 1 inventory. Baris = item content, kolom = tanggal dalam bulan.
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
        <div class="text-muted small">Periode <?= esc($monthLabel) ?></div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center small">
        <button type="button" class="btn btn-success btn-sm fac-mark-all-btn">
          <i class="bi bi-check2-square"></i>
          Centang Semua
        </button>
        <span class="fac-legend-pill"><span class="legend-box is-ok"></span>Sesuai</span>
        <span class="fac-legend-pill"><span class="legend-box is-not-ok"></span>Tidak Sesuai</span>
        <span class="fac-legend-pill"><span class="legend-box is-offday"></span>Libur</span>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body p-0">
      <div class="table-responsive fac-grid-wrap">
        <table class="table table-bordered align-middle mb-0 fac-grid-table">
          <thead>
            <tr>
              <th class="sticky-left sticky-no">No</th>
              <th class="sticky-left sticky-question">Item Content</th>
              <?php foreach ($days as $day): ?>
                <th class="<?= !empty($day['is_offday']) ? 'is-offday' : '' ?>">
                  <?= esc((string) date('j', strtotime($day['period_key']))) ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($questions as $index => $question): ?>
              <?php $templateId = (int) ($question['id'] ?? 0); ?>
              <tr>
                <td class="sticky-left sticky-no text-center"><?= $index + 1 ?></td>
                <td class="sticky-left sticky-question"><?= esc(trim((string) ($question['question'] ?? '')) !== '' ? $question['question'] : '-') ?></td>

                <?php foreach ($days as $day): ?>
                  <?php
                  $periodKey = (string) ($day['period_key'] ?? '');
                  $isOffday = !empty($day['is_offday']);
                  $log = $logMap[$templateId][$periodKey] ?? null;
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
                    class="fac-check-cell <?= esc($cellClass) ?>"
                    data-inventory-id="<?= (int) $inventory['id'] ?>"
                    data-template-id="<?= $templateId ?>"
                    data-period-key="<?= esc($periodKey) ?>"
                    data-state="<?= esc($state) ?>"
                    data-offday="<?= $isOffday ? '1' : '0' ?>"
                    title="<?= esc(trim((string) ($question['question'] ?? '')) . ' - ' . date('d M Y', strtotime($periodKey))) ?>">
                    <?php if ($isOffday): ?>
                      <span class="fac-cell-mark"></span>
                    <?php elseif ($state === 'ok'): ?>
                      <i class="bi bi-check-lg"></i>
                    <?php elseif ($state === 'not_ok'): ?>
                      <i class="bi bi-x-lg"></i>
                    <?php else: ?>
                      <span class="fac-cell-mark"></span>
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
<link rel="stylesheet" href="/assets/css/first-aid-content-grid.css?v=<?= filemtime(FCPATH . 'assets/css/first-aid-content-grid.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/first-aid-content-grid.js?v=<?= filemtime(FCPATH . 'js/first-aid-content-grid.js') ?>"></script>
<?= $this->endSection() ?>
