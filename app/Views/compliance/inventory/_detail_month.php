<div id="detailMonthContainer" class="inventory-detail-month-wrap">

  <?php if (!hasRole(['auditor'])): ?>
    <section class="card checklist-card no-lift mb-3">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <h6 class="fw-semibold mb-0">Ringkasan Bulan <?= date('F Y', strtotime($ym . '-01')) ?></h6>
          <span class="text-muted small">Rekap status checklist</span>
        </div>

        <div class="row text-center g-2">
          <div class="col-6 col-md-3">
            <div class="summary-box summary-box-total">
              <div class="summary-value"><?= $rekap['total'] ?></div>
              <div class="summary-label">Total</div>
            </div>
          </div>

          <div class="col-6 col-md-3">
            <div class="summary-box summary-box-ok">
              <div class="summary-value text-success"><?= $rekap['ok'] ?></div>
              <div class="summary-label">Sesuai</div>
            </div>
          </div>

          <div class="col-6 col-md-3">
            <div class="summary-box summary-box-not-ok">
              <div class="summary-value text-danger"><?= $rekap['not_ok'] ?></div>
              <div class="summary-label">Tidak Sesuai</div>
            </div>
          </div>

          <div class="col-6 col-md-3">
            <div class="summary-box summary-box-late">
              <div class="summary-value text-warning"><?= $rekap['late'] ?></div>
              <div class="summary-label">Terlambat</div>
            </div>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <div class="d-flex justify-content-center align-items-center gap-3 mb-3 month-nav-wrap">
    <button
      type="button"
      class="btn btn-outline-secondary btn-sm btn-month-nav"
      data-ym="<?= date('Y-m', strtotime($ym . ' -1 month')) ?>"
      aria-label="Bulan sebelumnya">
      <i class="bi bi-chevron-left"></i>
    </button>

    <span class="fw-semibold month-nav-current"><?= date('F Y', strtotime($ym . '-01')) ?></span>

    <?php
    $nextYM = date('Y-m', strtotime($ym . ' +1 month'));
    $isFuture = $nextYM > $nowYM;
    ?>

    <button
      type="button"
      class="btn btn-outline-secondary btn-sm btn-month-nav"
      data-ym="<?= $nextYM ?>"
      <?= $isFuture ? 'disabled' : '' ?>
      aria-label="Bulan berikutnya">
      <i class="bi bi-chevron-right"></i>
    </button>
  </div>

  <section class="card checklist-card no-lift mb-3">
    <div class="card-body p-0">
      <?php if ($inventory['item_type_id'] == 52): ?>
        <?= $this->include('compliance/inventory/_detail_toilet_grid') ?>
      <?php elseif ($inventory['checklist_frequency'] === 'daily'): ?>
        <?= $this->include('compliance/inventory/_detail_daily_grid') ?>
      <?php elseif ($inventory['checklist_frequency'] === 'weekly'): ?>
        <?= $this->include('compliance/inventory/_detail_weekly_grid') ?>
      <?php else: ?>
        <?= $this->include('compliance/inventory/_detail_monthly_table') ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="card checklist-card no-lift">
    <div class="card-body p-0">
      <div class="px-3 pt-3 text-muted small">Riwayat checklist</div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle table-checklist mb-0">
          <thead class="table-light">
            <tr>
              <?php if (!hasRole(['auditor'])): ?>
                <th width="20%">Tanggal</th>
              <?php endif; ?>
              <th width="24%" class="text-center">Periode</th>
              <th width="16%" class="text-center">Status</th>
              <th>Dicek Oleh</th>
            </tr>
          </thead>

          <tbody>
            <?php if (empty($checklists)): ?>
              <tr>
                <td colspan="<?= hasRole(['auditor']) ? 3 : 4 ?>" class="text-center text-muted py-4">
                  Tidak ada data checklist.
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach ($checklists as $c): ?>
              <tr>
                <?php if (!hasRole(['auditor'])): ?>
                  <td><?= $c['check_date'] ? date('d-m-Y', strtotime($c['check_date'])) : '-' ?></td>
                <?php endif; ?>

                <td class="text-center">
                  <?= period_label($inventory['checklist_frequency'], $c['period_key']) ?>
                </td>

                <?php $state = resolve_period_status($inventory['id'], $inventory['checklist_frequency'], $c['period_key']); ?>

                <td class="text-center">
                  <?php if ($state === 'done'): ?>
                    <span class="badge bg-success d-inline-flex align-items-center gap-1">
                      <i class="bi bi-check-circle-fill"></i>Selesai
                    </span>
                  <?php elseif ($state === 'late'): ?>
                    <span class="badge bg-danger d-inline-flex align-items-center gap-1">
                      <i class="bi bi-exclamation-circle-fill"></i>Terlambat
                    </span>
                  <?php elseif ($state === 'future'): ?>
                    <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
                      <i class="bi bi-lock-fill"></i>Belum Buka
                    </span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark d-inline-flex align-items-center gap-1">
                      <i class="bi bi-hourglass-split"></i>Pending
                    </span>
                  <?php endif; ?>
                </td>

                <td><?= esc($c['checked_by'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
