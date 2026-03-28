<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$monthMap = [
  '01' => 'Januari',
  '02' => 'Februari',
  '03' => 'Maret',
  '04' => 'April',
  '05' => 'Mei',
  '06' => 'Juni',
  '07' => 'Juli',
  '08' => 'Agustus',
  '09' => 'September',
  '10' => 'Oktober',
  '11' => 'November',
  '12' => 'Desember',
];

$dayMap = [
  'Sunday' => 'Minggu',
  'Monday' => 'Senin',
  'Tuesday' => 'Selasa',
  'Wednesday' => 'Rabu',
  'Thursday' => 'Kamis',
  'Friday' => 'Jumat',
  'Saturday' => 'Sabtu'
];

$daysInMonth = (int) date('t', strtotime("$year-$month-01"));
$monthLabel = ($monthMap[$month] ?? date('F', strtotime("$year-$month-01"))) . ' ' . $year;

$totalPoly = 0;
$totalKg = 0.0;
$filledDays = 0;

for ($d = 1; $d <= $daysInMonth; $d++) {
  $date = "$year-$month-" . sprintf('%02d', $d);
  $row = $logs[$date] ?? null;
  if ($row) {
    $filledDays++;
    $totalPoly += (float)($row['total_polybag'] ?? 0);
    $totalKg += (float)($row['total_kg'] ?? 0);
  }
}
?>

<div class="utility-shell utility-boiler-shell">
  <section class="card utility-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="utility-kicker mb-1">Boiler Monitoring</p>
        <h5 class="mb-1 fw-bold">Laporan Pemakaian Bahan Bakar Boiler</h5>
        <p class="text-muted mb-0">Pantau pemakaian harian polybag dan kilogram dalam satu periode.</p>
      </div>

      <div class="utility-actions d-flex flex-wrap align-items-center gap-2">
        <form method="get" class="utility-month-form">
          <input type="month"
            name="monthpicker"
            value="<?= $year . '-' . $month ?>"
            class="form-control form-control-sm"
            onchange="this.form.submit()">
        </form>

        <a href="<?= base_url('boiler/export?year=' . $year . '&month=' . $month) ?>"
          class="btn btn-success btn-sm d-inline-flex align-items-center gap-1">
          <i class="bi bi-file-earmark-spreadsheet"></i>
          Export Excel
        </a>
      </div>
    </div>
  </section>

  <section class="row g-2 mb-3 utility-stat-grid">
    <div class="col-12 col-md-4">
      <div class="card utility-stat-card no-lift">
        <div class="card-body">
          <div class="utility-stat-label">Periode</div>
          <div class="utility-stat-value"><?= esc($monthLabel) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card utility-stat-card no-lift">
        <div class="card-body">
          <div class="utility-stat-label">Total Polybag</div>
          <div class="utility-stat-value"><?= number_format($totalPoly, 0, ',', '.') ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="card utility-stat-card no-lift">
        <div class="card-body">
          <div class="utility-stat-label">Total KG</div>
          <div class="utility-stat-value"><?= number_format($totalKg, 2) ?></div>
        </div>
      </div>
    </div>
  </section>

  <section class="card utility-table-card no-lift">
    <div class="card-body p-0">
      <div class="table-responsive d-none d-md-block utility-table-wrap">
        <table class="table table-bordered align-middle mb-0 utility-table">
          <thead>
            <tr>
              <th width="56" class="text-center">No</th>
              <th width="130">Hari</th>
              <th width="150">Tanggal</th>
              <th width="120" class="text-end">Polybag</th>
              <th width="120" class="text-end">KG</th>
              <th width="110" class="text-center">Status</th>
              <th width="110" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
              <?php
              $date = "$year-$month-" . sprintf('%02d', $d);
              $dayName = $dayMap[date('l', strtotime($date))] ?? date('l', strtotime($date));
              $isOff = is_date_offday($date, $holidayDates ?? []);
              $row = $logs[$date] ?? null;
              $poly = (float)($row['total_polybag'] ?? 0);
              $kg = (float)($row['total_kg'] ?? 0);
              ?>
              <tr class="<?= $isOff ? 'utility-offday-row' : '' ?>">
                <td class="text-center fw-semibold"><?= $d ?></td>
                <td><?= esc($dayName) ?></td>
                <td><?= esc(date('d M Y', strtotime($date))) ?></td>
                <td class="text-end"><?= $row ? number_format($poly, 0, ',', '.') : '-' ?></td>
                <td class="text-end"><?= $row ? number_format($kg, 2) : '-' ?></td>
                <td class="text-center">
                  <?php if ($isOff): ?>
                    <span class="badge text-bg-danger">Libur</span>
                  <?php elseif ($row): ?>
                    <span class="badge text-bg-success">Terisi</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary">Belum</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <a href="<?= base_url('boiler/detail/' . $date) ?>" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-eye"></i>
                    Detail
                  </a>
                </td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>

      <div class="d-block d-md-none p-2">
        <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
          <?php
          $date = "$year-$month-" . sprintf('%02d', $d);
          $dayName = $dayMap[date('l', strtotime($date))] ?? date('l', strtotime($date));
          $isOff = is_date_offday($date, $holidayDates ?? []);
          $row = $logs[$date] ?? null;
          $poly = (float)($row['total_polybag'] ?? 0);
          $kg = (float)($row['total_kg'] ?? 0);
          ?>
          <article class="card utility-mobile-card mb-2 <?= $isOff ? 'utility-mobile-offday' : '' ?>">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                  <div class="fw-semibold"><?= $d ?> - <?= esc($dayName) ?></div>
                  <div class="text-muted small"><?= esc(date('d M Y', strtotime($date))) ?></div>
                </div>
                <?php if ($isOff): ?>
                  <span class="badge text-bg-danger">Libur</span>
                <?php elseif ($row): ?>
                  <span class="badge text-bg-success">Terisi</span>
                <?php else: ?>
                  <span class="badge text-bg-secondary">Belum</span>
                <?php endif; ?>
              </div>

              <div class="utility-mobile-metric">
                <span>Polybag</span>
                <strong><?= $row ? number_format($poly, 0, ',', '.') : '-' ?></strong>
              </div>
              <div class="utility-mobile-metric">
                <span>KG</span>
                <strong><?= $row ? number_format($kg, 2) : '-' ?></strong>
              </div>

              <a href="<?= base_url('boiler/detail/' . $date) ?>" class="btn btn-outline-primary btn-sm mt-2 w-100">
                Lihat Detail
              </a>
            </div>
          </article>
        <?php endfor; ?>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/utility-ops.css?v=' . filemtime(FCPATH . 'assets/css/utility-ops.css')) ?>">
<?= $this->endSection() ?>
