<?= $this->extend('layouts/main') ?>

<?php
$years = $boot['years'] ?? [];
$rows = $boot['rows'] ?? [];
?>

<?= $this->section('content') ?>
<div class="ems-page ghg-summary-page" x-data="{ ready: true }">
  <section class="card border-0 shadow-sm ems-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="ems-kicker mb-1">EMS Report</p>
        <h5 class="fw-bold mb-1">GHG Summary</h5>
        <p class="text-muted mb-0">Rekap tahunan emisi dihitung dari report energi yang sudah diisi.</p>
      </div>
      <a href="/ems-reports" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke EMS
      </a>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-header bg-transparent border-0 pb-0 ems-report-sheet-head">
      <div class="ems-sheet-title"><?= esc($boot['title'] ?? 'GHG Summary') ?></div>
      <div class="ems-sheet-subtitle"><?= esc($boot['companyName'] ?? '') ?></div>
      <div class="ems-sheet-address"><?= esc($boot['address'] ?? '') ?></div>
      <div class="ems-sheet-meta">Baseline Year: <?= esc((string) ($boot['baselineYear'] ?? '')) ?></div>
    </div>
    <div class="card-body pt-2">
      <div class="table-responsive">
        <table class="table table-bordered align-middle ems-table ems-wide-table ems-ghg-table">
          <thead>
            <tr>
              <th class="sticky-col">Scope</th>
              <th>Activity Type</th>
              <?php foreach ($years as $year): ?>
                <th><?= (int) $year ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr class="<?= !empty($row['is_total']) ? 'ems-ghg-total-row' : '' ?><?= !empty($row['is_grand_total']) ? ' ems-ghg-grand-row' : '' ?>">
                <td class="sticky-col fw-semibold"><?= esc($row['scope']) ?></td>
                <td><?= esc($row['activity']) ?></td>
                <?php foreach ($years as $year): ?>
                  <?php $value = $row['values'][$year] ?? null; ?>
                  <td><?= $value !== null ? esc(number_format((float) $value, 5, ',', '.')) : '-' ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/ems-report.css?v=<?= filemtime(FCPATH . 'assets/css/ems-report.css') ?>">
<?= $this->endSection() ?>
