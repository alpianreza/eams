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

<a href="<?= base_url('compliance/inventory/detail/' . $inventory['id']) ?>"
  class="btn btn-sm btn-secondary mb-3">
  ← Kembali
</a>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Checklist Periode (<?= strtoupper($frequency) ?>)</strong>

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
    <div class="d-flex justify-content-between align-items-center px-3 py-2">

      <?php if ($canPrev): ?>
        <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $prevYM ?>"
          class="btn btn-outline-secondary btn-sm">
          ‹ <?= date('F Y', strtotime($prevYM . '-01')) ?>
        </a>
      <?php else: ?>
        <button class="btn btn-outline-secondary btn-sm" disabled>‹</button>
      <?php endif ?>

      <div class="fw-bold">
        <?= date('F Y', strtotime($navYM . '-01')) ?>
      </div>

      <?php if ($canNext): ?>
        <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $nextYM ?>"
          class="btn btn-outline-secondary btn-sm">
          <?= date('F Y', strtotime($nextYM . '-01')) ?> ›
        </a>
      <?php else: ?>
        <button class="btn btn-outline-secondary btn-sm" disabled>›</button>
      <?php endif ?>

    </div>

    <div class="card-body">

      <!-- ================= DAILY ================= -->
      <?php if ($frequency === 'daily'): ?>
        <div class="row g-2 calendar-grid">
          <?php foreach ($periods as $p): ?>
            <div class="col-6 col-md-3">
              <?php if ($p['allowed']): ?>
                <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $navYM ?>&period_key=<?= $p['period_key'] ?>"
                  class="btn w-100 <?= periodBtnClass($p['status'], $p['is_active']) ?>">
                  <i class="bi bi-calendar-day me-1"></i>
                  <?= esc($p['label']) ?>
                </a>
              <?php else: ?>
                <div class="btn w-100 btn-outline-secondary disabled">
                  <i class="bi bi-lock me-1"></i>
                  <?= esc($p['label']) ?>
                </div>
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
              <?php if ($p['allowed']): ?>
                <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $navYM ?>&period_key=<?= $p['period_key'] ?>"
                  class="btn w-100 d-flex align-items-center justify-content-center gap-2
                    <?= periodBtnClass($p['status'], $p['is_active']) ?>">
                  <i class="bi bi-calendar-week"></i>
                  <span><?= esc($p['label']) ?></span>
                </a>
              <?php else: ?>
                <div class="btn w-100 btn-outline-secondary disabled d-flex align-items-center justify-content-center gap-2">
                  <i class="bi bi-lock"></i>
                  <span><?= esc($p['label']) ?></span>
                </div>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>

      <!-- ================= MONTHLY ================= -->
      <?php if ($frequency === 'monthly'): ?>
        <div class="row g-2 calendar-grid">
          <?php foreach ($periods as $p): ?>
            <div class="col-6 col-md-4">
              <?php if ($p['allowed']): ?>
                <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $p['period_key'] ?>&period_key=<?= $p['period_key'] ?>"
                  class="btn w-100 d-flex align-items-center justify-content-center gap-2
                    <?= periodBtnClass($p['status'], $p['is_active']) ?>">
                  <i class="fa-regular fa-calendar-check"></i>
                  <span><?= esc($p['label']) ?></span>
                </a>
              <?php else: ?>
                <div class="btn w-100 btn-outline-secondary disabled">
                  <i class="bi bi-lock"></i>
                  <?= esc($p['label']) ?>
                </div>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>

      <!-- ================= LEGEND ================= -->
      <div class="mt-3">
        <span class="badge bg-success me-2">DONE</span>
        <span class="badge bg-danger me-2">LATE</span>
        <span class="badge bg-warning text-dark me-2">PENDING</span>
        <span class="badge bg-secondary">FUTURE</span>
      </div>

    </div>
  </div>
</div>