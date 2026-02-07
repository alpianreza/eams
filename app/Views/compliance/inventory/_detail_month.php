<div id="detailMonthContainer">

  <!--Skelton-->

  <!-- ================= REKAP ================= -->
  <h5 class="mt-5 mb-3 fw-semibold">Rekap Checklist Bulan <?= date('F Y', strtotime($ym . '-01')) ?></h5>

  <div class="card checklist-card mb-4">
    <div class="card-body">
      <div class="row text-center g-3">

        <div class="col-6 col-md">
          <div class="fw-bold fs-4"><?= $rekap['total'] ?></div>
          <div class="text-muted">Total</div>
        </div>

        <div class="col-6 col-md">
          <div class="fw-bold fs-4 text-success"><?= $rekap['ok'] ?></div>
          <div class="text-muted">Sesuai</div>
        </div>

        <div class="col-6 col-md">
          <div class="fw-bold fs-4 text-danger"><?= $rekap['ng'] ?></div>
          <div class="text-muted">Tidak</div>
        </div>

        <div class="col-6 col-md">
          <div class="fw-bold fs-4 text-warning"><?= $rekap['late'] ?></div>
          <div class="text-muted">Terlambat</div>
        </div>

      </div>
    </div>
  </div>

  <!-- ================= NAV BULAN ================= -->
  <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
    <button
      type="button"
      class="btn btn-outline-secondary btn-sm btn-month-nav"
      data-ym="<?= date('Y-m', strtotime($ym . ' -1 month')) ?>">
      <i class="bi bi-chevron-left"></i>
    </button>

    <span class="fw-semibold">
      <?= date('F Y', strtotime($ym . '-01')) ?>
    </span>

    <?php
    $nextYM = date('Y-m', strtotime($ym . ' +1 month'));
    $isFuture = $nextYM > $nowYM;
    ?>

    <button
      type="button"
      class="btn btn-outline-secondary btn-sm btn-month-nav"
      data-ym="<?= $nextYM ?>"
      <?= $isFuture ? 'disabled' : '' ?>>
      <i class="bi bi-chevron-right"></i>
    </button>

  </div>

  <?php if ($inventory['checklist_frequency'] === 'daily'): ?>
    <?= $this->include('compliance/inventory/_detail_daily_grid') ?>
  <?php elseif ($inventory['checklist_frequency'] === 'weekly'): ?>
    <?= $this->include('compliance/inventory/_detail_weekly_grid') ?>
  <?php else: ?>
    <?= $this->include('compliance/inventory/_detail_monthly_table') ?>
  <?php endif; ?>



  <!-- ================= TABEL CHECKLIST ================= -->
  <div class="card checklist-card">
    <div class="card-body p-0">

      <div class="px-3 pt-3 text-muted small">
        Riwayat checklist
      </div>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle table-checklist mb-0">
          <thead class="table-light">
            <tr>
              <th width="20%">Tanggal</th>
              <th width="20%" class="text-center">Periode</th>
              <th width="15%" class="text-center">Status</th>
              <th>Dicek Oleh</th>
            </tr>
          </thead>
          <tbody>

            <?php if (empty($checklists)): ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-4">
                  Tidak ada data checklist
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach ($checklists as $c): ?>
              <tr>
                <td>
                  <?= $c['check_date']
                    ? date('d-m-Y', strtotime($c['check_date']))
                    : '-' ?>
                </td>
                <td class="text-center">
                  <?= period_label(
                    $inventory['checklist_frequency'],
                    $c['period_key']
                  ) ?>
                </td>
                <?php
                $state = resolve_period_status(
                  $inventory['id'],
                  $inventory['checklist_frequency'],
                  $c['period_key']
                );
                ?>

                <td class="text-center fw-bold">
                  <?php if ($state === 'done'): ?>
                    <span class="text-success">✓</span>
                  <?php elseif ($state === 'late'): ?>
                    <span class="text-danger">✗</span>
                  <?php elseif ($state === 'future'): ?>
                    <span class="text-muted">–</span>
                  <?php else: ?>
                    <span class="text-secondary">⏳</span>
                  <?php endif; ?>
                </td>

                <td><?= esc($c['checked_by'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>

          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>