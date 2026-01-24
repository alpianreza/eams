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

    <?php if ($canPrev): ?>
      <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $prevYM ?>"
        class="btn btn-outline-secondary">
        ⏪ <?= date('F Y', strtotime($prevYM . '-01')) ?>
      </a>
    <?php else: ?>
      <button class="btn btn-outline-secondary" disabled>⏪</button>
    <?php endif ?>

    <div class="fw-bold fs-5">
      <?= date('F Y', strtotime($navYM . '-01')) ?>
    </div>

    <?php if ($canNext): ?>
      <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $nextYM ?>"
        class="btn btn-outline-secondary">
        <?= date('F Y', strtotime($nextYM . '-01')) ?> ⏩
      </a>
    <?php else: ?>
      <button class="btn btn-outline-secondary" disabled>⏩</button>
    <?php endif ?>

  </div>

  <div class="card-body">

    <!-- ===== WEEKLY ===== -->
    <?php if ($frequency === 'weekly'): ?>
      <div class="row g-2">
        <?php foreach ($periods as $p): ?>
          <div class="col-6">
            <?php if ($p['allowed']): ?>
              <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?period_key=<?= $p['period_key'] ?>"
                class="btn w-100 <?= periodBtnClass($p['status'] ?? null, $p['period_key'] === $period_key) ?>">
                <?= esc($p['label']) ?>
              </a>
            <?php else: ?>
              <div class="btn w-100 btn-outline-secondary disabled">
                <?= esc($p['label']) ?>
              </div>
            <?php endif ?>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>

    <!-- ===== MONTHLY ===== -->
    <?php if ($frequency === 'monthly'): ?>
      <div class="row g-2">
        <?php foreach ($periods as $p): ?>
          <div class="col-4">
            <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?period_key=<?= $p['period_key'] ?>"
              class="btn w-100 <?= periodBtnClass($p['status'] ?? null, $p['period_key'] === $period_key) ?>">
              <?= esc($p['label']) ?>
            </a>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>

    <div class="mt-3">
      <span class="badge bg-success me-2">DONE</span>
      <span class="badge bg-danger me-2">LATE</span>
      <span class="badge bg-warning text-dark me-2">PENDING</span>
      <span class="badge bg-secondary">FUTURE</span>
    </div>

  </div>
</div>