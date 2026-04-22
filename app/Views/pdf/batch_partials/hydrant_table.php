<?php
$resolveHydrantLabel = static function (array $inventory): string {
  $assetCode = trim((string) ($inventory['asset_code'] ?? ''));
  if ($assetCode !== '' && preg_match('/(\d+)\s*$/', $assetCode, $matches)) {
    return 'Hydrant ' . ((int) $matches[1]);
  }

  $location = trim((string) ($inventory['specific_area'] ?? ''));
  if ($location !== '' && preg_match('/hidr?an?t?\s*(\d+)/i', $location, $matches)) {
    return 'Hydrant ' . ((int) $matches[1]);
  }

  return $assetCode !== '' ? $assetCode : 'Hydrant';
};
?>

<table class="batch-weekly-table">
  <thead>
    <tr>
      <th rowspan="3" class="col-no">NO</th>
      <th rowspan="3" class="col-info">KETERANGAN</th>
      <th colspan="<?= count($inventories) * 4 ?>" class="month-band">
        <?= esc($period['label'] ?? 'BULAN') ?>:
        <?php if (!empty($period['value'])): ?>
          <?= esc($period['value']) ?>
        <?php endif; ?>
      </th>
      <th rowspan="3" class="col-note">KET</th>
    </tr>
    <tr>
      <?php foreach ($inventories as $inventory): ?>
        <th colspan="4" class="question-head"><?= esc($resolveHydrantLabel($inventory)) ?></th>
      <?php endforeach; ?>
    </tr>
    <tr>
      <?php foreach ($inventories as $inventory): ?>
        <?php foreach ([1, 2, 3, 4] as $weekNumber): ?>
          <th class="week-head col-week"><?= $weekNumber ?></th>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($masters as $index => $master): ?>
      <?php $templateId = (int) ($master['id'] ?? 0); ?>
      <tr>
        <td><?= $index + 1 ?></td>
        <td class="text-left"><?= esc(trim((string) ($master['question'] ?? '')) !== '' ? $master['question'] : '-') ?></td>
        <?php foreach ($inventories as $inventory): ?>
          <?php foreach ([1, 2, 3, 4] as $weekNumber): ?>
            <?php $status = $weeklyChecklistMatrix[$inventory['id']][$templateId][$weekNumber] ?? null; ?>
            <td class="week-answer col-week"><?= esc($displayStatus($status)) ?></td>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <td class="col-note"></td>
      </tr>
    <?php endforeach; ?>
    <tr>
      <td colspan="2"><strong>Temuan</strong></td>
      <td colspan="<?= count($inventories) * 4 + 1 ?>"></td>
    </tr>
  </tbody>
</table>
