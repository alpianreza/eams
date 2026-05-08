<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $monthInput = date('Y-m', strtotime($ym . '-01')); ?>

<div
  class="gg-grid-page"
  data-save-url="<?= esc($saveUrl) ?>"
  data-bulk-url="/compliance/checklist/generic-grid/mark-all"
  data-inventory-id="<?= (int) ($inventory['id'] ?? 0) ?>"
  data-frequency="<?= esc($frequency) ?>"
  data-ym="<?= esc($ym) ?>"
  data-csrf-name="<?= esc($csrfName) ?>"
  data-csrf-hash="<?= esc($csrfHash) ?>">

  <section class="card border-0 shadow-sm checklist-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-kicker mb-1">Checklist Compliance</p>
        <h5 class="mb-1 fw-bold">Grid <?= esc($inventory['item_display_name'] ?? 'Checklist') ?></h5>
        <p class="text-muted mb-0">
          Mode grid untuk inventory yang belum punya template grid khusus.
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
        <div class="fw-semibold"><?= esc($inventory['asset_code'] ?? '-') ?> - <?= esc($inventory['specific_area'] ?? '-') ?></div>
        <div class="text-muted small">
          Frekuensi <?= esc(ucfirst($frequency)) ?><?php if ($frequency !== 'monthly'): ?>, periode <?= esc($monthLabel) ?><?php else: ?>, tahun <?= esc((string) $year) ?><?php endif; ?>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center small">
        <?php if (hasRole(['admin', 'compliance'])): ?>
          <button type="button" class="btn btn-success btn-sm gg-mark-all-btn">
            <i class="bi bi-check2-square"></i>
            Centang Semua
          </button>
        <?php endif; ?>
        <span class="gg-legend-pill"><span class="legend-box is-ok"></span>Sesuai</span>
        <span class="gg-legend-pill"><span class="legend-box is-not-ok"></span>Tidak Sesuai</span>
        <?php if ($frequency === 'daily'): ?>
          <span class="gg-legend-pill"><span class="legend-box is-offday"></span>Libur</span>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body p-0">
      <div class="table-responsive gg-grid-wrap">
        <table class="table table-bordered align-middle mb-0 gg-grid-table">
          <thead>
            <tr>
              <th class="sticky-left sticky-no">No</th>
              <?php if ($isSlotChecklist): ?>
                <th class="sticky-left sticky-slot">Slot</th>
                <th class="sticky-left sticky-question with-slot">Pertanyaan</th>
              <?php else: ?>
                <th class="sticky-left sticky-question">Pertanyaan</th>
              <?php endif; ?>
              <?php foreach ($columns as $column): ?>
                <th class="<?= !empty($column['is_offday']) ? 'is-offday' : '' ?>">
                  <?= esc($column['label'] ?? $column['period_key']) ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td class="sticky-left sticky-no text-center"><?= (int) $row['row_no'] ?></td>
                <?php if ($isSlotChecklist): ?>
                  <td class="sticky-left sticky-slot"><?= esc($row['slot_label']) ?></td>
                  <td class="sticky-left sticky-question with-slot">
                    <a href="/compliance/checklist/<?= (int) $inventory['id'] ?>?ym=<?= esc($ym) ?>&slot=<?= esc($row['slot_code']) ?>" class="text-decoration-none fw-semibold text-dark">
                      <?= esc($row['question']) ?>
                    </a>
                  </td>
                <?php else: ?>
                  <td class="sticky-left sticky-question">
                    <a href="/compliance/checklist/<?= (int) $inventory['id'] ?>?ym=<?= esc($ym) ?>" class="text-decoration-none fw-semibold text-dark">
                      <?= esc($row['question']) ?>
                    </a>
                  </td>
                <?php endif; ?>

                <?php foreach ($columns as $column): ?>
                  <?php
                  $periodKey = (string) ($column['period_key'] ?? '');
                  $isOffday = !empty($column['is_offday']);
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
                    class="gg-check-cell <?= esc($cellClass) ?>"
                    data-template-id="<?= (int) $row['template_id'] ?>"
                    data-period-key="<?= esc($periodKey) ?>"
                    data-state="<?= esc($state) ?>"
                    data-time-slot="<?= esc($row['slot_code']) ?>"
                    data-offday="<?= $isOffday ? '1' : '0' ?>">
                    <?php if ($isOffday): ?>
                      <span class="gg-cell-mark"></span>
                    <?php elseif ($state === 'ok'): ?>
                      <i class="bi bi-check-lg"></i>
                    <?php elseif ($state === 'not_ok'): ?>
                      <i class="bi bi-x-lg"></i>
                    <?php else: ?>
                      <span class="gg-cell-mark"></span>
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
<link rel="stylesheet" href="/assets/css/generic-grid.css?v=<?= filemtime(FCPATH . 'assets/css/generic-grid.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/generic-grid.js?v=<?= filemtime(FCPATH . 'js/generic-grid.js') ?>"></script>
<?= $this->endSection() ?>
