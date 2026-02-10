<?php if (! function_exists('periodBtnClass')): ?>
  <?php
  function periodBtnClass($status, $active = false)
  {
    if ($active) return 'btn-primary';

    return match ($status) {
      'done'    => 'btn-success',
      'late'    => 'btn-danger',
      'pending' => 'btn-warning',
      'future'  => 'btn-outline-secondary',
      default   => 'btn-outline-secondary',
    };
  }
  ?>
<?php endif; ?>


<div class="checklist-header mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Periode: (<?= strtoupper($frequency) ?>)</strong>
    <button class="btn btn-sm btn-outline-primary"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#calendarCollapse"
      aria-expanded="false">
      <i class="bi bi-calendar-event me-1"></i>
      Pilih Periode
      <i class="bi bi-chevron-down ms-1"></i>
    </button>
  </div>

  <div class="collapse mt-2" id="calendarCollapse">

    <!-- NAV BULAN -->
    <div class="calendar-nav">

      <?php if ($canPrev): ?>
        <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $prevYM ?>"
          class="btn btn-outline-secondary btn-sm nav-btn">
          <i class="bi bi-chevron-left"></i>
          <span class="nav-text">
            <?= date('F Y', strtotime($prevYM . '-01')) ?>
          </span>
        </a>
      <?php else: ?>
        <button class="btn btn-outline-secondary btn-sm" disabled>
          <i class="bi bi-chevron-left"></i>
        </button>
      <?php endif ?>

      <div class="fw-bold nav-current">
        <?= date('F Y', strtotime($navYM . '-01')) ?>
      </div>

      <?php if ($canNext): ?>
        <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $nextYM ?>"
          class="btn btn-outline-secondary btn-sm nav-btn">
          <span class="nav-text">
            <?= date('F Y', strtotime($nextYM . '-01')) ?>
          </span>
          <i class="bi bi-chevron-right"></i>
        </a>
      <?php else: ?>
        <button class="btn btn-outline-secondary btn-sm" disabled>
          <i class="bi bi-chevron-right"></i>
        </button>
      <?php endif ?>

    </div>

    <div class="card-body">

      <!-- ================= DAILY ================= -->
      <?php if ($frequency === 'daily'): ?>
        <div class="row g-2 calendar-grid">
          <?php foreach ($periods as $p): ?>
            <div class="col-3 col-md-2 col-lg-1">

              <?php if ($p['is_offday']): ?>

                <div class="btn w-100 btn-offday d-flex align-items-center justify-content-center gap-1">
                  <i class="bi bi-calendar-x"></i>
                  Libur
                </div>

              <?php elseif ($p['status'] === 'future'): ?>

                <div class="btn w-100 btn-outline-secondary disabled d-flex align-items-center justify-content-center gap-1">
                  <i class="bi bi-lock"></i>
                  <?= esc(date('d M', strtotime($p['period_key']))) ?>
                </div>

              <?php else: ?>

                <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $navYM ?>&period_key=<?= $p['period_key'] ?>"
                  class="btn w-100 d-flex align-items-center justify-content-center gap-1
             <?= periodBtnClass($p['status'], $p['is_active']) ?>">

                  <?php if ($p['status'] === 'done'): ?>
                    <i class="fa-regular fa-calendar-check"></i>
                  <?php elseif ($p['status'] === 'late'): ?>
                    <i class="bi bi-exclamation-circle"></i>
                  <?php elseif ($p['status'] === 'pending'): ?>
                    <i class="fa-regular fa-calendar"></i>
                  <?php endif; ?>

                  <?= esc(date('d M', strtotime($p['period_key']))) ?>
                </a>

              <?php endif ?>

            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>


      <!-- ================= WEEKLY ================= -->
      <?php if ($frequency === 'weekly'): ?>
        <div class="row g-2 calendar-grid">
          <?php foreach ($periods as $p): ?>
            <div class="col-6 col-md-3">

              <?php if ($p['status'] === 'future'): ?>

                <div class="btn w-100 btn-outline-secondary disabled d-flex align-items-center justify-content-center gap-2">
                  <i class="bi bi-lock"></i>
                  <span><?= esc($p['label']) ?></span>
                </div>

              <?php else: ?>

                <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $navYM ?>&period_key=<?= $p['period_key'] ?>"
                  class="btn w-100 d-flex align-items-center justify-content-center gap-2
             <?= periodBtnClass($p['status'], $p['is_active']) ?>">

                  <?php if ($p['status'] === 'done'): ?>
                    <i class="fa-regular fa-calendar-check"></i>
                  <?php elseif ($p['status'] === 'late'): ?>
                    <i class="bi bi-exclamation-circle"></i>
                  <?php elseif ($p['status'] === 'pending'): ?>
                    <i class="fa-regular fa-calendar"></i>
                  <?php endif; ?>

                  <span><?= esc($p['label']) ?></span>
                </a>

              <?php endif ?>

            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>

      <!-- ================= MONTHLY ================= -->
      <?php if ($frequency === 'monthly'): ?>
        <div class="row g-2 calendar-grid monthly">
          <?php foreach ($periods as $p): ?>
            <div class="col-4 col-md-3 col-lg-2">
              <?php if ($p['allowed']): ?>
                <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $p['period_key'] ?>&period_key=<?= $p['period_key'] ?>"
                  class="btn w-100 d-flex align-items-center justify-content-center gap-2
                    <?= periodBtnClass($p['status'], $p['is_active']) ?>">
                  <i class="fa-regular fa-calendar-check"></i>
                  <span><?= date('M Y', strtotime($p['period_key'] . '-01')) ?>
                  </span>
                </a>
              <?php else: ?>
                <div class="btn w-100 btn-outline-secondary disabled">
                  <i class="bi bi-lock"></i>
                  <?= date('M Y', strtotime($p['period_key'] . '-01')) ?>
                </div>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>

      <!-- ================= LEGEND ================= -->
      <div class="mt-3 d-flex flex-wrap gap-2">

        <span class="badge bg-success">SELESAI</span>

        <span class="badge bg-danger">TERLAMBAT</span>

        <span class="badge bg-warning text-dark">PENDING</span>

        <!-- LIBUR -->
        <span class="badge border border-danger text-danger bg-white d-flex align-items-center gap-1">
          <i class="bi bi-calendar-x"></i>
          LIBUR
        </span>

      </div>

    </div>
  </div>
</div>