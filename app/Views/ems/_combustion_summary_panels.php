<?php
$reportTitle = $reportTitle ?? 'Combustion';
$sheetTitle = $sheetTitle ?? '';
$companyName = $companyName ?? '';
$address = $address ?? '';
$baselineText = $baselineText ?? '';
$years = $years ?? [];
$sections = $sections ?? [];
$months = $months ?? [];
$sectionSummaries = $sectionSummaries ?? [];
$yearMeta = $yearMeta ?? [];
$totalEmissionLabel = $totalEmissionLabel ?? 'Total Emission per Year';
?>
<section class="card border-0 shadow-sm no-lift">
  <div class="card-header bg-transparent border-0 pb-0 ems-report-sheet-head">
    <div class="ems-sheet-title"><?= esc($sheetTitle) ?></div>
    <div class="ems-sheet-subtitle"><?= esc($companyName) ?></div>
    <div class="ems-sheet-address"><?= esc($address) ?></div>
    <div class="ems-sheet-meta"><?= esc($baselineText) ?></div>
  </div>
  <div class="card-body pt-2">
    <?php foreach ($sections as $sectionKey => $section): ?>
      <div class="ems-combustion-sheet-block<?= $sectionKey !== array_key_first($sections) ? ' mt-4' : '' ?>">
        <div class="ems-combustion-sheet-title"><?= esc($section['label']) ?></div>
        <div class="table-responsive">
          <table class="table table-bordered align-middle ems-table ems-wide-table ems-electric-table">
            <thead>
              <tr>
                <th class="sticky-col">Month</th>
                <th>Unit</th>
                <?php foreach ($years as $index => $year): ?>
                  <th><?= esc($section['consumption_label']) ?> (<?= (int) $year ?>)</th>
                  <?php if ($index > 0): ?>
                    <th>Change (%) Previous Year <?= (int) $years[$index - 1] ?></th>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($sectionSummaries[$sectionKey] ?? []) as $row): ?>
                <tr>
                  <td class="sticky-col fw-semibold"><?= esc($row['label']) ?></td>
                  <td><?= esc($section['unit']) ?></td>
                  <?php foreach ($row['values'] as $index => $cell): ?>
                    <td><?= $cell['value'] !== null ? esc(number_format((float) $cell['value'], 2, ',', '.')) : '-' ?></td>
                    <?php if ($index > 0): ?>
                      <?php
                      $change = $cell['change'];
                      $toneClass = 'is-neutral';
                      if ($change !== null) {
                          if ($change < 0) {
                              $toneClass = 'is-positive';
                          } elseif ($change > 0) {
                              $toneClass = 'is-negative';
                          }
                      }
                      ?>
                      <td class="ems-change-cell <?= esc($toneClass) ?>"><?= $change !== null ? esc(number_format((float) $change, 2, ',', '.')) : '-' ?></td>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
              <tr class="ems-trend-row">
                <td class="sticky-col fw-semibold">Monthly Trend</td>
                <td></td>
                <?php foreach ($years as $index => $year): ?>
                  <?php
                  $trend = $yearMeta[$year]['sections'][$sectionKey]['trend'] ?? [];
                  $maxValue = !empty($trend) ? max($trend) : 0;
                  $prevYear = $years[$index - 1] ?? null;
                  $prevTotal = $prevYear ? (float) ($yearMeta[$prevYear]['sections'][$sectionKey]['total'] ?? 0) : null;
                  $currentTotal = (float) ($yearMeta[$year]['sections'][$sectionKey]['total'] ?? 0);
                  $totalChange = ($prevTotal !== null && $prevTotal > 0) ? (($currentTotal - $prevTotal) / $prevTotal) * 100 : null;
                  $toneClass = 'is-neutral';
                  if ($totalChange !== null) {
                      if ($totalChange < 0) {
                          $toneClass = 'is-positive';
                      } elseif ($totalChange > 0) {
                          $toneClass = 'is-negative';
                      }
                  }
                  ?>
                  <td>
                    <div class="ems-trend-bars">
                      <?php foreach ($months as $monthNum => $labels): ?>
                        <?php $value = (float) ($trend[$monthNum] ?? 0); $height = ($maxValue > 0) ? max(10, round(($value / $maxValue) * 72)) : 10; ?>
                        <span class="ems-trend-bar" style="height: <?= (int) $height ?>px" title="<?= esc($labels['short']) ?>: <?= esc(number_format($value, 2, ',', '.')) ?>"></span>
                      <?php endforeach; ?>
                    </div>
                  </td>
                  <?php if ($index > 0): ?>
                    <td class="ems-change-cell ems-trend-change <?= esc($toneClass) ?>"><?= $totalChange !== null ? esc(number_format((float) $totalChange, 2, ',', '.')) : '-' ?></td>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tr>
              <tr class="ems-summary-row fw-semibold">
                <td class="sticky-col">Total</td>
                <td></td>
                <?php foreach ($years as $index => $year): ?>
                  <td><?= esc(number_format((float) ($yearMeta[$year]['sections'][$sectionKey]['total'] ?? 0), 2, ',', '.')) ?></td>
                  <?php if ($index > 0): ?><td></td><?php endif; ?>
                <?php endforeach; ?>
              </tr>
              <tr class="ems-summary-row">
                <td class="sticky-col">Production Output</td>
                <td></td>
                <?php foreach ($years as $index => $year): ?>
                  <td><?= ($yearMeta[$year]['production_output'] ?? null) !== null ? esc(number_format((float) $yearMeta[$year]['production_output'], 2, ',', '.')) : '-' ?></td>
                  <?php if ($index > 0): ?><td></td><?php endif; ?>
                <?php endforeach; ?>
              </tr>
              <tr class="ems-summary-row">
                <td class="sticky-col">Annual Intensity Average</td>
                <td></td>
                <?php foreach ($years as $index => $year): ?>
                  <td><?= ($yearMeta[$year]['sections'][$sectionKey]['intensity'] ?? null) !== null ? esc(number_format((float) $yearMeta[$year]['sections'][$sectionKey]['intensity'], 5, ',', '.')) : '-' ?></td>
                  <?php if ($index > 0): ?><td></td><?php endif; ?>
                <?php endforeach; ?>
              </tr>
              <tr class="ems-summary-row">
                <td class="sticky-col">tCO2e Emission (Emission Factor=<?= esc(number_format((float) $section['emission_factor'], 3, ',', '.')) ?>)</td>
                <td></td>
                <?php foreach ($years as $index => $year): ?>
                  <td><?= esc(number_format((float) ($yearMeta[$year]['sections'][$sectionKey]['emission'] ?? 0), 5, ',', '.')) ?></td>
                  <?php if ($index > 0): ?><td></td><?php endif; ?>
                <?php endforeach; ?>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="table-responsive mt-3">
      <table class="table table-bordered align-middle ems-table ems-wide-table">
        <tfoot>
          <tr class="ems-electric-foot">
            <th class="sticky-col"><?= esc($totalEmissionLabel) ?></th>
            <?php foreach ($years as $index => $year): ?>
              <th><?= esc(number_format((float) ($yearMeta[$year]['total_emission'] ?? 0), 5, ',', '.')) ?></th>
              <?php if ($index > 0): ?><th></th><?php endif; ?>
            <?php endforeach; ?>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</section>
