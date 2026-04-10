<?= $this->extend('layouts/main') ?>

<?php
$monthLongNames = [
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
?>

<?= $this->section('content') ?>
<div class="ems-page water-report-page">
  <section class="card border-0 shadow-sm ems-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="ems-kicker mb-1">EMS Report</p>
        <h5 class="fw-bold mb-1">Water Consumption</h5>
        <p class="text-muted mb-0">Input konsumsi air bulanan dan output produksi untuk summary tahunan.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= base_url('ems-reports') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Kembali ke EMS
        </a>
      </div>
    </div>
  </section>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <section class="card border-0 shadow-sm no-lift mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($years as $year): ?>
          <a href="<?= base_url('ems-reports/water-consumption?year=' . $year) ?>" class="btn btn-sm <?= $selectedYear === $year ? 'btn-primary' : 'btn-outline-primary' ?> rounded-pill px-3">
            <?= (int) $year ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <div class="row g-3">
    <div class="col-xl-4">
      <section class="card border-0 shadow-sm no-lift h-100">
        <div class="card-header bg-transparent border-0 pb-0">
          <p class="ems-section-kicker mb-1">Input Bulanan</p>
          <h6 class="fw-semibold mb-1">Form Tahun <?= (int) $selectedYear ?></h6>
        </div>
        <div class="card-body pt-2">
          <form action="<?= base_url('ems-reports/water-consumption/save') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="report_year" value="<?= (int) $selectedYear ?>">

            <div class="mb-3">
              <label for="production_output" class="form-label form-label-sm">Production Output</label>
              <input
                type="number"
                step="0.01"
                min="0"
                class="form-control"
                id="production_output"
                name="production_output"
                value="<?= $selectedProductionOutput !== null ? esc(number_format((float) $selectedProductionOutput, 2, '.', '')) : '' ?>"
                placeholder="Contoh: 4350778"
              >
            </div>

            <div class="ems-month-grid">
              <?php foreach ($monthLongNames as $monthNum => $monthName): ?>
                <div class="ems-month-field">
                  <label class="form-label form-label-sm" for="month_<?= (int) $monthNum ?>"><?= esc($monthName) ?></label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control form-control-sm"
                    id="month_<?= (int) $monthNum ?>"
                    name="months[<?= (int) $monthNum ?>]"
                    value="<?= $selectedMonths[$monthNum] !== null ? esc(number_format((float) $selectedMonths[$monthNum], 2, '.', '')) : '' ?>"
                  >
                </div>
              <?php endforeach; ?>
            </div>

            <div class="mt-3 d-grid">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan Data Tahun <?= (int) $selectedYear ?>
              </button>
            </div>
          </form>
        </div>
      </section>
    </div>

    <div class="col-xl-8">
      <section class="card border-0 shadow-sm no-lift mb-3">
        <div class="card-header bg-transparent border-0 pb-0">
          <p class="ems-section-kicker mb-1">Monthly Summary</p>
          <h6 class="fw-semibold mb-1">Water Consumption (<?= (int) min($years) ?>-<?= (int) max($years) ?>)</h6>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table table-bordered align-middle ems-table ems-wide-table">
              <thead>
                <tr>
                  <th rowspan="2" class="sticky-col">Month</th>
                  <?php foreach ($years as $index => $year): ?>
                    <th><?= (int) $year ?></th>
                    <?php if ($index > 0): ?>
                      <th>Change vs <?= (int) $years[$index - 1] ?> (%)</th>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($monthlySummary as $monthNum => $row): ?>
                  <tr>
                    <td class="sticky-col fw-semibold"><?= esc($row['label']) ?></td>
                    <?php foreach ($years as $index => $year): ?>
                      <?php $cell = $row['values'][$year] ?? ['value' => null, 'change' => null]; ?>
                      <td><?= $cell['value'] !== null ? esc(number_format((float) $cell['value'], 2, ',', '.')) : '-' ?></td>
                      <?php if ($index > 0): ?>
                        <td>
                          <?= $cell['change'] !== null ? esc(number_format((float) $cell['change'], 2, ',', '.')) . '%' : '-' ?>
                        </td>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <th class="sticky-col">Total</th>
                  <?php foreach ($years as $index => $year): ?>
                    <th><?= esc(number_format((float) ($yearMeta[$year]['total'] ?? 0), 2, ',', '.')) ?></th>
                    <?php if ($index > 0): ?>
                      <?php
                      $prevYear = $years[$index - 1];
                      $prevTotal = (float) ($yearMeta[$prevYear]['total'] ?? 0);
                      $currentTotal = (float) ($yearMeta[$year]['total'] ?? 0);
                      $totalChange = $prevTotal > 0 ? (($currentTotal - $prevTotal) / $prevTotal) * 100 : null;
                      ?>
                      <th><?= $totalChange !== null ? esc(number_format((float) $totalChange, 2, ',', '.')) . '%' : '-' ?></th>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </section>

      <section class="card border-0 shadow-sm no-lift">
        <div class="card-header bg-transparent border-0 pb-0">
          <p class="ems-section-kicker mb-1">Water Intensity</p>
          <h6 class="fw-semibold mb-1">Intensity Calculation</h6>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table table-bordered align-middle ems-table">
              <thead>
                <tr>
                  <th>Year</th>
                  <th>Water Usage</th>
                  <th>Production Output</th>
                  <th>Intensity (m3 / unit)</th>
                  <th>% Change vs Baseline <?= (int) $baselineYear ?></th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($summaryRows as $row): ?>
                  <?php
                  $statusTone = 'secondary';
                  if ($row['status'] === 'Baseline') {
                      $statusTone = 'primary';
                  } elseif ($row['status'] === 'Decrease') {
                      $statusTone = 'success';
                  } elseif ($row['status'] === 'Increase') {
                      $statusTone = 'danger';
                  } elseif ($row['status'] === 'Stable') {
                      $statusTone = 'info';
                  }
                  ?>
                  <tr>
                    <td class="fw-semibold"><?= (int) $row['year'] ?></td>
                    <td><?= esc(number_format((float) $row['water_usage'], 2, ',', '.')) ?></td>
                    <td><?= $row['production_output'] !== null ? esc(number_format((float) $row['production_output'], 2, ',', '.')) : '-' ?></td>
                    <td><?= $row['intensity'] !== null ? esc(number_format((float) $row['intensity'], 5, ',', '.')) : '-' ?></td>
                    <td>
                      <?= $row['change_vs_baseline'] !== null ? esc(number_format((float) $row['change_vs_baseline'], 2, ',', '.')) . '%' : 'Baseline' ?>
                    </td>
                    <td><span class="badge text-bg-<?= esc($statusTone) ?>"><?= esc($row['status']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/ems-report.css?v=' . filemtime(FCPATH . 'assets/css/ems-report.css')) ?>">
<?= $this->endSection() ?>