<?php
$frequencyLabel = match ($frequency) {
  'daily' => 'Harian',
  'weekly' => 'Mingguan',
  default => 'Bulanan',
};
?>

<div class="report-result-head d-flex justify-content-between align-items-center gap-2 mb-3">
  <button
    type="button"
    class="btn btn-outline-secondary btn-sm navInventory report-nav-btn-circle"
    data-id="<?= esc((string) $prev) ?>"
    <?= $prev ? '' : 'disabled' ?>
    aria-label="Inventory sebelumnya">
    <i class="bi bi-chevron-left"></i>
  </button>

  <div class="text-center px-2">
    <div class="fw-bold report-asset-code"><?= esc($inventory['asset_code']) ?></div>
    <small class="text-muted"><?= esc($inventory['specific_area'] ?? '-') ?></small>
  </div>

  <button
    type="button"
    class="btn btn-outline-secondary btn-sm navInventory report-nav-btn-circle"
    data-id="<?= esc((string) $next) ?>"
    <?= $next ? '' : 'disabled' ?>
    aria-label="Inventory berikutnya">
    <i class="bi bi-chevron-right"></i>
  </button>
</div>

<div class="report-frequency-chip mb-3">
  <span class="badge bg-light text-dark border">Frekuensi: <?= esc($frequencyLabel) ?></span>
</div>

<?php if ($frequency === 'monthly'): ?>
  <?= view('compliance/report/_monthly', [
    'inventory'   => $inventory,
    'masters'     => $masters,
    'monthlyGrid' => $monthlyGrid,
    'findingsByMonth' => $findingsByMonth,
    'checkerByMonth' => $checkerByMonth,
    'year'        => $year,
    'month'       => $month,
    'role'        => $role,
    'itemName'    => $itemName,
    'specificArea' => $specificArea,
    'pic'         => $pic,
    'expired'     => $expired,
    'isFireExtinguisher' => $isFireExtinguisher,
    'isToiletChecklist' => $isToiletChecklist ?? false
  ]) ?>
<?php elseif ($frequency === 'daily'): ?>
  <?= view('compliance/report/_daily', [
    'inventory'   => $inventory,
    'masters'     => $masters,
    'dailyGrid'   => $dailyGrid,
    'dailyDays'   => $dailyDays,
    'checkerByDate' => $checkerByDate,
    'findings'    => $findings,
    'year'        => $year,
    'month'       => $month,
    'holidayDates' => $holidayDates ?? [],
    'role'        => $role,
    'itemName'    => $itemName,
    'specificArea' => $specificArea,
    'pic'         => $pic,
    'expired'     => $expired,
    'isFireExtinguisher' => $isFireExtinguisher,
    'isToiletChecklist' => $isToiletChecklist ?? false
  ]) ?>
<?php elseif ($frequency === 'weekly'): ?>
  <?= view('compliance/report/_weekly', [
    'inventory'   => $inventory,
    'masters'     => $masters,
    'weeklyGrid'  => $weeklyGrid,
    'checkerByWeek' => $checkerByWeek,
    'findings'    => $findings,
    'year'        => $year,
    'month'       => $month,
    'role'        => $role,
    'itemName'    => $itemName,
    'specificArea' => $specificArea,
    'pic'         => $pic,
    'expired'     => $expired,
    'isFireExtinguisher' => $isFireExtinguisher
  ]) ?>
<?php endif; ?>
