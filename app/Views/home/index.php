<?= $this->extend('layouts/main') ?>

<?php
$title = 'Home';
$monthMap = [
  1 => 'Januari',
  2 => 'Februari',
  3 => 'Maret',
  4 => 'April',
  5 => 'Mei',
  6 => 'Juni',
  7 => 'Juli',
  8 => 'Agustus',
  9 => 'September',
  10 => 'Oktober',
  11 => 'November',
  12 => 'Desember',
];

$selectedMonthNumber = (int) date('n', strtotime($selectedMonth . '-01'));
$selectedMonthYear = date('Y', strtotime($selectedMonth . '-01'));
$selectedMonthLabel = ($monthMap[$selectedMonthNumber] ?? date('F', strtotime($selectedMonth . '-01'))) . ' ' . $selectedMonthYear;

$pendingColor = $summary['pending'] > 0 ? 'text-warning' : 'text-success';
$notOkColor   = $summary['not_ok'] > 0 ? 'text-danger' : 'text-success';

$progressTextClass = 'text-success';
if ($progress < 50) {
  $progressTextClass = 'text-danger';
} elseif ($progress < 80) {
  $progressTextClass = 'text-warning';
}

$progressBarClass = 'bg-success';
if ($progress < 50) {
  $progressBarClass = 'bg-danger';
} elseif ($progress < 80) {
  $progressBarClass = 'bg-warning';
}
?>

<?= $this->section('content') ?>
<div class="home-dashboard-page">
  <section class="card border-0 shadow-sm home-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="home-kicker mb-1">Beranda Compliance</p>
        <h5 class="mb-1 fw-bold">Halo, <?= esc(session('name')) ?></h5>
        <p class="text-muted mb-0">Status checklist periode <strong><?= esc($selectedMonthLabel) ?></strong></p>
      </div>

      <form method="get" class="home-month-form ms-auto">
        <label for="monthFilter" class="form-label form-label-sm mb-1">Periode</label>
        <select id="monthFilter" name="month" class="form-select form-select-sm home-month-select" onchange="this.form.submit()">
          <?php
          $start = new DateTime('2026-01-01');
          $end   = new DateTime(date('Y-m-01'));
          while ($start <= $end):
            $value = $start->format('Y-m');
            $label = ($monthMap[(int) $start->format('n')] ?? $start->format('F')) . ' ' . $start->format('Y');
          ?>
            <option value="<?= esc($value) ?>" <?= $selectedMonth === $value ? 'selected' : '' ?>>
              <?= esc($label) ?>
            </option>
          <?php
            $start->modify('+1 month');
          endwhile;
          ?>
        </select>
      </form>
    </div>
  </section>

  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
      <article class="card h-100 border-0 shadow-sm home-stat-card no-lift">
        <div class="card-body">
          <div class="home-stat-label">Total Inventory</div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="home-stat-value"><?= (int) $summary['total'] ?></div>
            <span class="home-stat-icon text-info"><i class="bi bi-box-seam"></i></span>
          </div>
        </div>
      </article>
    </div>

    <div class="col-6 col-lg-3">
      <article class="card h-100 border-0 shadow-sm home-stat-card no-lift">
        <div class="card-body">
          <div class="home-stat-label">Belum Checklist</div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="home-stat-value <?= esc($pendingColor) ?>"><?= (int) $summary['pending'] ?></div>
            <span class="home-stat-icon text-warning"><i class="bi bi-hourglass-split"></i></span>
          </div>
        </div>
      </article>
    </div>

    <div class="col-6 col-lg-3">
      <article class="card h-100 border-0 shadow-sm home-stat-card no-lift">
        <div class="card-body">
          <div class="home-stat-label">Temuan</div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="home-stat-value <?= esc($notOkColor) ?>"><?= (int) $summary['not_ok'] ?></div>
            <span class="home-stat-icon text-danger"><i class="bi bi-exclamation-triangle"></i></span>
          </div>
        </div>
      </article>
    </div>

    <div class="col-6 col-lg-3">
      <article class="card h-100 border-0 shadow-sm home-stat-card no-lift">
        <div class="card-body">
          <div class="home-stat-label">Progress Bulan Ini</div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="home-stat-value <?= esc($progressTextClass) ?>"><?= (int) $progress ?>%</div>
            <span class="home-stat-icon text-success"><i class="bi bi-graph-up-arrow"></i></span>
          </div>
        </div>
      </article>
    </div>
  </div>

  <section class="card border-0 shadow-sm no-lift mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-semibold">Progress Checklist</h6>
        <strong><?= (int) $progress ?>%</strong>
      </div>
      <div class="progress home-progress-wrap" role="progressbar" aria-label="Progress checklist" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $progress ?>">
        <div class="progress-bar <?= esc($progressBarClass) ?>" style="width: <?= (int) $progress ?>%;"></div>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-header bg-transparent border-0 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h6 class="mb-1 fw-semibold">Inventory Belum Checklist</h6>
        <small class="text-muted">Periode <?= esc($selectedMonthLabel) ?></small>
      </div>
    </div>

    <div class="card-body pt-2">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 home-pending-table">
          <thead>
            <tr>
              <th width="56" class="text-center">No</th>
              <th>Nama Item</th>
              <th>Lokasi</th>
              <th width="120" class="text-center">Frekuensi</th>
              <th width="90" class="text-center">Sisa</th>
              <th width="140" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pendingList)): ?>
              <tr>
                <td colspan="6" class="text-center py-5">
                  <i class="bi bi-check-circle-fill text-success fs-2 d-block mb-2"></i>
                  <div class="fw-semibold">Semua periode sudah selesai</div>
                  <small class="text-muted">Pertahankan konsistensi checklist.</small>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($pendingList as $i => $inv): ?>
                <?php
                $missingPeriods = $inv['missing_periods'] ?? [];
                $frequencyRaw = strtolower((string)($inv['checklist_frequency'] ?? 'monthly'));

                $frequencyLabel = match ($frequencyRaw) {
                  'daily' => 'Harian',
                  'weekly' => 'Mingguan',
                  default => 'Bulanan',
                };

                if ((int)($inv['remaining'] ?? 0) === 0) {
                  $remainingClass = 'btn-outline-success';
                } elseif ((int)($inv['remaining'] ?? 0) <= 3) {
                  $remainingClass = 'btn-outline-warning';
                } else {
                  $remainingClass = 'btn-outline-danger';
                }

                $defaultPeriodKey = $selectedMonth;
                if (!empty($missingPeriods)) {
                  $first = (string) $missingPeriods[0];
                  if ($frequencyRaw === 'daily') {
                    $defaultPeriodKey = $selectedMonth . '-' . $first;
                  } elseif ($frequencyRaw === 'weekly') {
                    $defaultPeriodKey = $selectedMonth . '-W' . (int) $first;
                  }
                }

                $checklistUrl = base_url('compliance/checklist/' . (int) $inv['id']) . '?period_key=' . urlencode($defaultPeriodKey);
                ?>
                <tr>
                  <td class="text-center"><?= $i + 1 ?></td>
                  <td class="fw-semibold"><?= esc($inv['item_name'] ?? '-') ?></td>
                  <td><?= esc($inv['specific_area'] ?? '-') ?></td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border"><?= esc($frequencyLabel) ?></span>
                  </td>
                  <td class="text-center">
                    <button
                      type="button"
                      class="btn btn-sm <?= esc($remainingClass) ?> open-popover"
                      data-id="<?= (int) $inv['id'] ?>"
                      data-frequency="<?= esc($frequencyRaw) ?>"
                      data-missing="<?= esc(json_encode($missingPeriods), 'attr') ?>">
                      <?= (int) ($inv['remaining'] ?? 0) ?>
                    </button>
                  </td>
                  <td class="text-center">
                    <a
                      href="<?= esc($checklistUrl) ?>"
                      class="btn btn-sm btn-primary">
                      Buka Ceklis
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/home-dashboard.css?v=' . filemtime(FCPATH . 'assets/css/home-dashboard.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.HOME_DASHBOARD = {
    selectedMonth: "<?= esc($selectedMonth) ?>",
    checklistBaseUrl: "<?= rtrim(base_url('compliance/checklist'), '/') ?>"
  };
</script>
<script src="<?= base_url('js/home-dashboard.js?v=' . filemtime(FCPATH . 'js/home-dashboard.js')) ?>"></script>
<?= $this->endSection() ?>
