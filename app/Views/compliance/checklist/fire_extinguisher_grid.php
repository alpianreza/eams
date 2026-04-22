<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$monthInput = date('Y-m', strtotime($ym . '-01'));
$formatDate = static function (?string $date): string {
  if (empty($date)) {
    return '-';
  }

  $timestamp = strtotime($date);
  if ($timestamp === false) {
    return (string) $date;
  }

  return date('d-m-Y', $timestamp);
};
?>

<div
  class="fe-grid-page"
  data-save-url="<?= esc($saveUrl) ?>"
  data-csrf-name="<?= esc($csrfName) ?>"
  data-csrf-hash="<?= esc($csrfHash) ?>">

  <section class="card border-0 shadow-sm checklist-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-kicker mb-1">Checklist Compliance</p>
        <h5 class="mb-1 fw-bold">Checklist Alat Pemadam Api Ringan</h5>
        <p class="text-muted mb-0">
          Grid bulanan mengikuti struktur print PDF. Klik sel status untuk putar:
          <strong>Sesuai</strong>, <strong>Tidak Sesuai</strong>, lalu kosong lagi.
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
          Form kolektif APAR dengan urutan mengikuti nomor inventaris.
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center small">
        <span class="fe-legend-pill"><span class="legend-box is-ok"></span>Sesuai</span>
        <span class="fe-legend-pill"><span class="legend-box is-not-ok"></span>Tidak Sesuai</span>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body p-0">
      <div class="table-responsive fe-grid-wrap">
        <table class="table table-bordered align-middle mb-0 fe-grid-table">
          <thead>
            <tr>
              <th rowspan="2" class="sticky-left sticky-no">No</th>
              <th rowspan="2" class="sticky-left sticky-location">Lokasi</th>
              <?php foreach ($groupedColumns as $group): ?>
                <th colspan="<?= count($group['columns'] ?? []) ?>" class="group-band">
                  <?= esc($group['label'] ?? '') ?>
                </th>
              <?php endforeach; ?>
            </tr>
            <tr>
              <?php foreach ($groupedColumns as $group): ?>
                <?php foreach (($group['columns'] ?? []) as $column): ?>
                  <th class="<?= esc(($column['type'] ?? '') === 'field' ? 'col-static head-sub' : 'col-question head-sub') ?>">
                    <?= esc($column['label'] ?? '') ?>
                  </th>
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
                  <?php foreach (($group['columns'] ?? []) as $column): ?>
                    <?php if (($column['type'] ?? '') === 'field'): ?>
                      <?php
                      $fieldKey = (string) ($column['key'] ?? '');
                      $value = '-';
                      if ($fieldKey === 'type_description') {
                        $value = $row['type_description'];
                      } elseif ($fieldKey === 'expired_date') {
                        $value = $formatDate($row['expired_date']);
                      }
                      ?>
                      <td class="field-cell"><?= esc($value) ?></td>
                    <?php else: ?>
                      <?php
                      $templateId = (int) ($column['id'] ?? 0);
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
                        class="fe-check-cell <?= esc($cellClass) ?>"
                        data-inventory-id="<?= (int) $row['id'] ?>"
                        data-template-id="<?= $templateId ?>"
                        data-period-key="<?= esc($ym) ?>"
                        data-state="<?= esc($state) ?>"
                        title="<?= esc($row['asset_code'] . ' - ' . ($column['label'] ?? '')) ?>">
                        <?php if ($state === 'ok'): ?>
                          <i class="bi bi-check-lg"></i>
                        <?php elseif ($state === 'not_ok'): ?>
                          <i class="bi bi-x-lg"></i>
                        <?php else: ?>
                          <span class="fe-cell-mark"></span>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                  <?php endforeach; ?>
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
<link rel="stylesheet" href="/assets/css/fire-extinguisher-grid.css?v=<?= filemtime(FCPATH . 'assets/css/fire-extinguisher-grid.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/fire-extinguisher-grid.js?v=<?= filemtime(FCPATH . 'js/fire-extinguisher-grid.js') ?>"></script>
<?= $this->endSection() ?>
