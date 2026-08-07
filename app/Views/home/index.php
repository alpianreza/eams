<?= $this->extend('layouts/main') ?>

<?php
$title = 'Dashboard';
$monthMap = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$selectedDate = DateTime::createFromFormat('!Y-m', $selectedMonth) ?: new DateTime('first day of this month');
$selectedMonthLabel = ($monthMap[(int) $selectedDate->format('n')] ?? $selectedDate->format('F')) . ' ' . $selectedDate->format('Y');
$progressValue = max(0, min(100, (int) $progress));
$pendingItems = array_values(array_filter($pendingList, static fn(array $item): bool => (int) ($item['remaining'] ?? 0) > 0));
$visiblePendingItems = array_slice($pendingItems, 0, 8);
$pendingInventoryCount = count($pendingItems);
$completedInventoryCount = max(0, (int) $summary['total'] - $pendingInventoryCount);
$progressTone = $progressValue >= 80 ? 'is-success' : ($progressValue >= 50 ? 'is-warning' : 'is-danger');
$statusTitle = $progressValue >= 100 ? 'Periode selesai' : ($progressValue >= 80 ? 'Hampir selesai' : ($progressValue >= 50 ? 'Perlu dipercepat' : 'Butuh perhatian'));
$statusDescription = $progressValue >= 100
  ? 'Seluruh kewajiban checklist pada periode ini sudah terpenuhi.'
  : $pendingInventoryCount . ' inventaris masih memiliki checklist yang belum diselesaikan.';
?>

<?= $this->section('content') ?>
<div class="home-dashboard-page">
  <section class="home-command-card mb-3">
    <div class="home-command-copy">
      <span class="home-eyebrow"><i class="bi bi-grid-1x2"></i> Pusat operasi compliance</span>
      <h1>Selamat datang, <?= esc(session('name')) ?></h1>
      <p>Pantau prioritas, selesaikan checklist, dan tindak lanjuti temuan dalam satu tampilan.</p>
    </div>

    <div class="home-command-tools">
      <form method="get" class="home-period-form">
        <label for="monthFilter">Periode laporan</label>
        <div class="home-period-control">
          <i class="bi bi-calendar3" aria-hidden="true"></i>
          <select id="monthFilter" name="month" class="form-select" onchange="this.form.submit()">
            <?php
            $start = new DateTime('2026-01-01');
            $end = new DateTime(date('Y-m-01'));
            while ($start <= $end):
              $value = $start->format('Y-m');
              $label = ($monthMap[(int) $start->format('n')] ?? $start->format('F')) . ' ' . $start->format('Y');
            ?>
              <option value="<?= esc($value) ?>" <?= $selectedMonth === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php $start->modify('+1 month'); endwhile; ?>
          </select>
        </div>
      </form>

      <div class="home-command-actions">
        <a href="<?= base_url('compliance/inventory') ?>" class="btn btn-primary"><i class="bi bi-check2-square"></i> Mulai checklist</a>
        <?php if (hasRole(['admin', 'compliance', 'auditor'])): ?>
          <a href="<?= base_url('compliance/dashboard') ?>" class="btn btn-outline-primary"><i class="bi bi-bar-chart-line"></i> Analitik</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="home-kpi-grid mb-3" aria-label="Ringkasan <?= esc($selectedMonthLabel) ?>">
    <article class="home-kpi-card is-primary">
      <span class="home-kpi-icon"><i class="bi bi-activity"></i></span>
      <div><span>Progress periode</span><strong><?= $progressValue ?>%</strong><small><?= esc($selectedMonthLabel) ?></small></div>
    </article>
    <article class="home-kpi-card">
      <span class="home-kpi-icon"><i class="bi bi-box-seam"></i></span>
      <div><span>Inventaris saya</span><strong><?= (int) $summary['total'] ?></strong><small><?= $completedInventoryCount ?> tanpa tunggakan</small></div>
    </article>
    <article class="home-kpi-card <?= (int) $summary['pending'] > 0 ? 'is-warning' : 'is-success' ?>">
      <span class="home-kpi-icon"><i class="bi bi-clock-history"></i></span>
      <div><span>Belum checklist</span><strong><?= (int) $summary['pending'] ?></strong><small><?= $pendingInventoryCount ?> inventaris terdampak</small></div>
    </article>
    <article class="home-kpi-card <?= (int) $summary['not_ok'] > 0 ? 'is-danger' : 'is-success' ?>">
      <span class="home-kpi-icon"><i class="bi bi-exclamation-diamond"></i></span>
      <div><span>Temuan</span><strong><?= (int) $summary['not_ok'] ?></strong><small><?= (int) $summary['not_ok'] > 0 ? 'Perlu tindak lanjut' : 'Tidak ada temuan' ?></small></div>
    </article>
  </section>

  <div class="home-workspace-grid">
    <section class="home-panel">
      <header class="home-panel-header">
        <div><span class="home-panel-kicker">Antrian kerja</span><h2>Prioritas checklist</h2><p>Inventaris dengan kewajiban yang belum selesai pada <?= esc($selectedMonthLabel) ?>.</p></div>
        <?php if ($pendingInventoryCount > 0): ?><span class="home-count-badge"><?= $pendingInventoryCount ?> inventaris</span><?php endif; ?>
      </header>

      <div class="home-task-list">
        <?php if (empty($visiblePendingItems)): ?>
          <div class="home-empty-state"><span><i class="bi bi-check2-circle"></i></span><h3>Semua checklist selesai</h3><p>Tidak ada antrian pekerjaan untuk periode ini.</p></div>
        <?php else: ?>
          <?php foreach ($visiblePendingItems as $inv): ?>
            <?php
            $missingPeriods = $inv['missing_periods'] ?? [];
            $frequencyRaw = strtolower((string) ($inv['checklist_frequency'] ?? 'monthly'));
            $frequencyMeta = match ($frequencyRaw) {
              'daily' => ['label' => 'Harian', 'icon' => 'bi-calendar-day'],
              'weekly' => ['label' => 'Mingguan', 'icon' => 'bi-calendar-week'],
              default => ['label' => 'Bulanan', 'icon' => 'bi-calendar-month'],
            };
            $defaultPeriodKey = $selectedMonth;
            if (!empty($missingPeriods)) {
              $first = (string) $missingPeriods[0];
              if ($frequencyRaw === 'daily') $defaultPeriodKey = $selectedMonth . '-' . $first;
              elseif ($frequencyRaw === 'weekly') $defaultPeriodKey = $selectedMonth . '-W' . (int) $first;
            }
            $remaining = (int) ($inv['remaining'] ?? 0);
            $checklistUrl = base_url('compliance/checklist/' . (int) $inv['id']) . '?period_key=' . urlencode($defaultPeriodKey);
            ?>
            <article class="home-task-row">
              <span class="home-task-icon"><i class="bi <?= esc($frequencyMeta['icon']) ?>"></i></span>
              <div class="home-task-main"><h3><?= esc($inv['item_name'] ?? '-') ?></h3><p><i class="bi bi-geo-alt"></i> <?= esc($inv['specific_area'] ?? 'Lokasi belum diatur') ?></p></div>
              <div class="home-task-frequency"><span><?= esc($frequencyMeta['label']) ?></span><small>Frekuensi</small></div>
              <button type="button" class="home-remaining-button open-popover" data-id="<?= (int) $inv['id'] ?>" data-frequency="<?= esc($frequencyRaw) ?>" data-missing="<?= esc(json_encode($missingPeriods), 'attr') ?>" aria-label="Lihat <?= $remaining ?> periode yang belum selesai"><strong><?= $remaining ?></strong><span>tersisa</span></button>
              <a href="<?= esc($checklistUrl) ?>" class="btn btn-primary home-task-action">Kerjakan <i class="bi bi-arrow-right"></i></a>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if ($pendingInventoryCount > count($visiblePendingItems)): ?>
        <footer class="home-panel-footer"><a href="<?= base_url('compliance/inventory') ?>">Lihat semua <?= $pendingInventoryCount ?> inventaris <i class="bi bi-arrow-right"></i></a></footer>
      <?php endif; ?>
    </section>

    <aside class="home-side-column">
      <section class="home-panel home-progress-panel">
        <header class="home-panel-header is-compact"><div><span class="home-panel-kicker">Kesehatan periode</span><h2>Progress keseluruhan</h2></div></header>
        <div class="home-progress-visual"><div class="home-progress-ring <?= esc($progressTone) ?>" style="--home-progress: <?= $progressValue ?>" role="img" aria-label="Progress <?= $progressValue ?> persen"><div><strong><?= $progressValue ?>%</strong><span>selesai</span></div></div></div>
        <div class="home-status-callout <?= esc($progressTone) ?>"><i class="bi <?= $progressValue >= 100 ? 'bi-check-circle' : 'bi-info-circle' ?>"></i><div><strong><?= esc($statusTitle) ?></strong><p><?= esc($statusDescription) ?></p></div></div>
      </section>

      <section class="home-panel">
        <header class="home-panel-header is-compact"><div><span class="home-panel-kicker">Navigasi cepat</span><h2>Buka modul</h2></div></header>
        <nav class="home-shortcut-list" aria-label="Navigasi cepat">
          <a href="<?= base_url('compliance/inventory') ?>"><span><i class="bi bi-boxes"></i></span><div><strong>Inventory compliance</strong><small>Kelola aset dan checklist</small></div><i class="bi bi-chevron-right"></i></a>
          <?php if (hasRole(['admin', 'compliance', 'auditor'])): ?><a href="<?= base_url('compliance/dashboard') ?>"><span><i class="bi bi-clipboard-data"></i></span><div><strong>Dashboard compliance</strong><small>Analisis performa menyeluruh</small></div><i class="bi bi-chevron-right"></i></a><?php endif; ?>
          <?php if (hasRole(['admin', 'compliance'])): ?><a href="<?= base_url('holidays') ?>"><span><i class="bi bi-calendar-event"></i></span><div><strong>Hari libur</strong><small>Atur kalender operasional</small></div><i class="bi bi-chevron-right"></i></a><?php endif; ?>
        </nav>
      </section>
    </aside>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/home-dashboard.css?v=' . filemtime(FCPATH . 'assets/css/home-dashboard.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>window.HOME_DASHBOARD = {selectedMonth: "<?= esc($selectedMonth) ?>", checklistBaseUrl: "<?= rtrim(base_url('compliance/checklist'), '/') ?>"};</script>
<script src="<?= base_url('js/home-dashboard.js?v=' . filemtime(FCPATH . 'js/home-dashboard.js')) ?>"></script>
<?= $this->endSection() ?>
