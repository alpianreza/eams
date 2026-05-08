<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$monthInput = date('Y-m', strtotime($ym . '-01'));
?>

<div
  class="ia-grid-page"
  data-save-url="<?= esc($saveUrl) ?>"
  data-bulk-url="/compliance/checklist/intrusion-alarm-grid/mark-all"
  data-ym="<?= esc($ym) ?>"
  data-csrf-name="<?= esc($csrfName) ?>"
  data-csrf-hash="<?= esc($csrfHash) ?>">

  <section class="card border-0 shadow-sm checklist-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-kicker mb-1">Checklist Compliance</p>
        <h5 class="mb-1 fw-bold">Pemeriksaan & Perawatan Alarm Keamanan</h5>
        <p class="text-muted mb-0">
          Grid mingguan untuk Intrusion Alarm. Kolom minggu lengkap <strong>1, 2, 3, 4</strong> untuk setiap pertanyaan.
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
          Minggu `1-4` mewakili `W1-W4` dalam satu bulan aktif.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center small">
        <button type="button" class="btn btn-success btn-sm ia-mark-all-btn">
          <i class="bi bi-check2-square"></i>
          Centang Semua
        </button>
        <span class="ia-legend-pill"><span class="legend-box is-ok"></span>Sesuai</span>
        <span class="ia-legend-pill"><span class="legend-box is-not-ok"></span>Tidak Sesuai</span>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body p-0">
      <div class="table-responsive ia-grid-wrap">
        <table class="table table-bordered align-middle mb-0 ia-grid-table">
          <thead>
            <tr>
              <th rowspan="3" class="sticky-left sticky-no">No</th>
              <th rowspan="3" class="sticky-left sticky-location">Keterangan</th>
              <th colspan="<?= array_sum(array_map(static fn(array $group): int => count($group['columns'] ?? []), $groupedColumns)) ?>" class="month-band">
                Bulan : <?= esc($monthLabel) ?>
              </th>
              <th rowspan="3" class="col-note">Keterangan</th>
            </tr>
            <tr>
              <?php foreach ($groupedColumns as $group): ?>
                <th colspan="<?= count($group['columns'] ?? []) ?>" class="question-head">
                  <?= esc($group['label'] ?? '') ?>
                </th>
              <?php endforeach; ?>
            </tr>
            <tr>
              <?php foreach ($groupedColumns as $group): ?>
                <?php foreach (($group['columns'] ?? []) as $column): ?>
                  <th class="week-head col-week"><?= esc($column['label'] ?? '') ?></th>
                <?php endforeach; ?>
              <?php endforeach; ?>
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

                <?php foreach ($groupedColumns as $group): ?>
                  <?php $templateId = (int) ($group['template_id'] ?? 0); ?>
                  <?php foreach (($group['columns'] ?? []) as $column): ?>
                    <?php
                    $weekNumber = (int) ($column['week'] ?? 0);
                    $log = $row['checks'][$templateId][$weekNumber] ?? null;
                    $state = strtolower((string) ($log['status'] ?? 'empty'));
                    $cellClass = 'is-empty';
                    if ($state === 'ok') {
                      $cellClass = 'is-ok';
                    } elseif ($state === 'not_ok') {
                      $cellClass = 'is-not-ok';
                    }
                    ?>
                    <td
                      class="ia-check-cell <?= esc($cellClass) ?>"
                      data-inventory-id="<?= (int) $row['id'] ?>"
                      data-template-id="<?= $templateId ?>"
                      data-period-key="<?= esc($ym . '-W' . $weekNumber) ?>"
                      data-state="<?= esc($state) ?>"
                      title="<?= esc($row['location'] . ' - ' . ($group['label'] ?? '') . ' W' . $weekNumber) ?>">
                      <?php if ($state === 'ok'): ?>
                        <i class="bi bi-check-lg"></i>
                      <?php elseif ($state === 'not_ok'): ?>
                        <i class="bi bi-x-lg"></i>
                      <?php else: ?>
                        <span class="ia-cell-mark"></span>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
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
<link rel="stylesheet" href="/assets/css/intrusion-alarm-grid.css?v=<?= filemtime(FCPATH . 'assets/css/intrusion-alarm-grid.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/intrusion-alarm-grid.js?v=<?= filemtime(FCPATH . 'js/intrusion-alarm-grid.js') ?>"></script>
<?= $this->endSection() ?>
