<?php if (!function_exists('periodBtnClass')): ?>
  <?php
  function periodBtnClass($status, $active = false)
  {
    if ($active) return 'btn-primary';
    return match ($status) {
      'done'    => 'btn-success',
      'late'    => 'btn-danger',
      'pending' => 'btn-warning',
      default   => 'btn-outline-secondary',
    };
  }
  ?>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header">
    <strong>Checklist Periode (<?= strtoupper($frequency) ?>)</strong>
  </div>

  <!-- NAVIGASI BULAN -->
  <div class="d-flex justify-content-between align-items-center px-3 py-2">

    <!-- PREV -->
    <?php if ($canPrev): ?>
      <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $prevYM ?>"
        class="btn btn-outline-secondary d-flex align-items-center gap-2">
        <i class="bi bi-chevron-left"></i>
        <span class="d-none d-md-inline">
          <?= date('F Y', strtotime($prevYM . '-01')) ?>
        </span>
      </a>
    <?php else: ?>
      <button class="btn btn-outline-secondary" disabled>
        <i class="bi bi-chevron-left"></i>
      </button>
    <?php endif ?>


    <div class="fw-bold fs-5">
      <?= date('F Y', strtotime($navYM . '-01')) ?>
    </div>

    <!-- NEXT -->
    <?php if ($canNext): ?>
      <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $nextYM ?>"
        class="btn btn-outline-secondary d-flex align-items-center gap-2">
        <span class="d-none d-md-inline">
          <?= date('F Y', strtotime($nextYM . '-01')) ?>
        </span>
        <i class="bi bi-chevron-right"></i>
      </a>
    <?php else: ?>
      <button class="btn btn-outline-secondary" disabled>
        <i class="bi bi-chevron-right"></i>
      </button>
    <?php endif ?>


  </div>

  <div class="card-body">

    <!-- ===== WEEKLY ===== -->
    <?php if ($frequency === 'weekly'): ?>
      <div class="row g-2 calendar-grid">
        <?php foreach ($periods as $p): ?>
          <div class="col-6 col-md-3">
            <?php if ($p['allowed']): ?>
              <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?period_key=<?= $p['period_key'] ?>"
                class="btn w-100 d-flex align-items-center justify-content-center gap-2
                     <?= periodBtnClass($p['status'] ?? null, $p['period_key'] === $period_key) ?>">
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

    <!-- ===== MONTHLY ===== -->
    <?php if ($frequency === 'monthly'): ?>
      <div class="row g-2 calendar-grid">
        <?php foreach ($periods as $p): ?>
          <div class="col-6 col-md-4">
            <?php if ($p['allowed']): ?>
              <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?period_key=<?= $p['period_key'] ?>"
                class="btn w-100 d-flex align-items-center justify-content-center gap-2
                     <?= periodBtnClass($p['status'] ?? null, $p['period_key'] === $period_key) ?>">
                <i class="bi bi-calendar-month"></i>
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

    <!-- ===== LEGEND ===== -->
    <div class="badge-legend mt-3">
      <span class="badge bg-success me-2">
        <i class="bi bi-check-circle me-1"></i> DONE
      </span>
      <span class="badge bg-danger me-2">
        <i class="bi bi-exclamation-circle me-1"></i> LATE
      </span>
      <span class="badge bg-warning text-dark me-2">
        <i class="bi bi-clock-history me-1"></i> PENDING
      </span>
      <span class="badge bg-secondary">
        <i class="bi bi-lock me-1"></i> FUTURE
      </span>
    </div>

  </div>