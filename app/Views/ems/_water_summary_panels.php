<?php
$years = $years ?? [];
$baselineYear = $baselineYear ?? (int) date('Y');
$yearMeta = $yearMeta ?? [];
$monthlySummary = $monthlySummary ?? [];
$summaryRows = $summaryRows ?? [];
?>
<section class="card border-0 shadow-sm no-lift mb-3">
  <div class="card-header bg-transparent border-0 pb-0">
    <p class="ems-section-kicker mb-1">Monthly Summary</p>
    <h6 class="fw-semibold mb-1">Water Consumption</h6>
  </div>
  <div class="card-body pt-2">
    <div class="table-responsive">
      <table class="table table-bordered align-middle ems-table ems-wide-table">
        <thead>
          <tr>
            <th class="sticky-col">Month</th>
            <?php foreach ($years as $index => $year): ?>
              <th><?= (int) $year ?></th>
              <?php if ($index > 0): ?>
                <th>Change vs <?= (int) $years[$index - 1] ?> (%)</th>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($monthlySummary as $row): ?>
            <tr>
              <td class="sticky-col fw-semibold"><?= esc($row['label']) ?></td>
              <?php foreach ($row['values'] as $index => $cell): ?>
                <td><?= $cell['value'] !== null ? esc(number_format((float) $cell['value'], 2, ',', '.')) : '-' ?></td>
                <?php if ($index > 0): ?>
                  <td><?= $cell['change'] !== null ? esc(number_format((float) $cell['change'], 2, ',', '.')) . '%' : '-' ?></td>
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
              <td><?= esc(number_format((float) $row['waterUsage'], 2, ',', '.')) ?></td>
              <td><?= $row['productionOutput'] !== null ? esc(number_format((float) $row['productionOutput'], 2, ',', '.')) : '-' ?></td>
              <td><?= $row['intensity'] !== null ? esc(number_format((float) $row['intensity'], 5, ',', '.')) : '-' ?></td>
              <td><?= $row['changeVsBaseline'] !== null ? esc(number_format((float) $row['changeVsBaseline'], 2, ',', '.')) . '%' : 'Baseline' ?></td>
              <td><span class="badge text-bg-<?= esc($statusTone) ?>"><?= esc($row['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
