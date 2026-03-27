<?php if (! function_exists('periodBtnClass')): ?>
  <?php
  function periodBtnClass($status, $active = false)
  {
    if ($active) {
      return 'btn-primary';
    }

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

<?php
$doneCount = 0;
$lateCount = 0;
$pendingCount = 0;
$offdayCount = 0;

foreach ($periods as $period) {
  $status = (string)($period['status'] ?? 'future');
  if (!empty($period['is_offday'])) {
    $offdayCount++;
    continue;
  }

  if ($status === 'done') {
    $doneCount++;
  } elseif ($status === 'late') {
    $lateCount++;
  } elseif ($status === 'pending') {
    $pendingCount++;
  }
}
?>

<div class="card checklist-card checklist-calendar-card mb-3 no-lift">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <strong>Periode Aktif (<?= strtoupper($frequency) ?>)</strong>
      <div class="small text-muted">Pilih tanggal/periode untuk mengisi ceklis.</div>
    </div>

    <button
      class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#calendarCollapse"
      aria-expanded="false">
      <i class="bi bi-calendar-event"></i>
      Pilih Periode
      <i class="bi bi-chevron-down"></i>
    </button>
  </div>

  <div class="collapse show" id="calendarCollapse">
    <div class="card-body">
      <div class="calendar-nav mb-3">
        <?php if ($canPrev): ?>
          <a
            href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $prevYM ?>"
            class="btn btn-outline-secondary btn-sm nav-btn">
            <i class="bi bi-chevron-left"></i>
            <span class="nav-text"><?= date('F Y', strtotime($prevYM . '-01')) ?></span>
          </a>
        <?php else: ?>
          <button class="btn btn-outline-secondary btn-sm" disabled>
            <i class="bi bi-chevron-left"></i>
          </button>
        <?php endif ?>

        <div class="fw-bold nav-current"><?= date('F Y', strtotime($navYM . '-01')) ?></div>

        <?php if ($canNext): ?>
          <a
            href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $nextYM ?>"
            class="btn btn-outline-secondary btn-sm nav-btn">
            <span class="nav-text"><?= date('F Y', strtotime($nextYM . '-01')) ?></span>
            <i class="bi bi-chevron-right"></i>
          </a>
        <?php else: ?>
          <button class="btn btn-outline-secondary btn-sm" disabled>
            <i class="bi bi-chevron-right"></i>
          </button>
        <?php endif ?>
      </div>

      <?php if ($frequency === 'daily'): ?>
        <div class="row g-2 calendar-grid">
          <?php foreach ($periods as $p): ?>
            <div class="col-4 col-md-3 col-lg-2">
              <?php if ($p['is_offday']): ?>
                <div class="btn w-100 btn-offday d-flex align-items-center justify-content-center gap-1">
                  Libur
                </div>
              <?php elseif ($p['status'] === 'future'): ?>
                <div class="btn w-100 btn-outline-secondary disabled d-flex align-items-center justify-content-center gap-1">
                  <i class="bi bi-lock"></i>
                  <?= esc(date('d M', strtotime($p['period_key']))) ?>
                </div>
              <?php else: ?>
                <a
                  href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $navYM ?>&period_key=<?= $p['period_key'] ?>"
                  class="btn w-100 d-flex align-items-center justify-content-center gap-1 checklist-period-btn <?= periodBtnClass($p['status'], $p['is_active']) ?>">
                  <?php if ($p['status'] === 'done'): ?>
                    <i class="bi bi-check-circle-fill"></i>
                  <?php elseif ($p['status'] === 'late'): ?>
                    <i class="bi bi-exclamation-circle-fill"></i>
                  <?php elseif ($p['status'] === 'pending'): ?>
                    <i class="bi bi-hourglass-split"></i>
                  <?php endif; ?>
                  <?= esc(date('d M', strtotime($p['period_key']))) ?>
                </a>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>

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
                <a
                  href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $navYM ?>&period_key=<?= $p['period_key'] ?>"
                  class="btn w-100 d-flex align-items-center justify-content-center gap-2 checklist-period-btn <?= periodBtnClass($p['status'], $p['is_active']) ?>">
                  <?php if ($p['status'] === 'done'): ?>
                    <i class="bi bi-check-circle-fill"></i>
                  <?php elseif ($p['status'] === 'late'): ?>
                    <i class="bi bi-exclamation-circle-fill"></i>
                  <?php elseif ($p['status'] === 'pending'): ?>
                    <i class="bi bi-hourglass-split"></i>
                  <?php endif; ?>
                  <span><?= esc($p['label']) ?></span>
                </a>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>

      <?php if ($frequency === 'monthly'): ?>
        <div class="row g-2 calendar-grid monthly">
          <?php foreach ($periods as $p): ?>
            <div class="col-6 col-md-4 col-lg-3">
              <?php if ($p['allowed']): ?>
                <a
                  href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>?ym=<?= $p['period_key'] ?>&period_key=<?= $p['period_key'] ?>"
                  class="btn w-100 d-flex align-items-center justify-content-center gap-2 checklist-period-btn <?= periodBtnClass($p['status'], $p['is_active']) ?>">
                  <i class="bi bi-calendar-check"></i>
                  <span><?= date('M Y', strtotime($p['period_key'] . '-01')) ?></span>
                </a>
              <?php else: ?>
                <div class="btn w-100 btn-outline-secondary disabled d-flex align-items-center justify-content-center gap-2">
                  <i class="bi bi-lock"></i>
                  <?= date('M Y', strtotime($p['period_key'] . '-01')) ?>
                </div>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>

      <div class="checklist-calendar-summary mt-3">
        <span class="badge text-bg-success">Selesai: <?= $doneCount ?></span>
        <span class="badge text-bg-danger">Terlambat: <?= $lateCount ?></span>
        <span class="badge text-bg-warning text-dark">Pending: <?= $pendingCount ?></span>
        <?php if ($frequency === 'daily'): ?>
          <span class="badge border border-danger text-danger bg-white">Libur: <?= $offdayCount ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
