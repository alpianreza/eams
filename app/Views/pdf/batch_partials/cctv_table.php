<?php
$resolveSpecialInspectionName = static function (int $number): ?string {
  return match ($number) {
    33 => 'Monitor',
    34 => 'Hardisk',
    35 => 'DVR',
    default => null,
  };
};

$displayCctvStatus = static function (?string $status): string {
  return match ($status) {
    'ok' => '✔',
    'not_ok' => 'X',
    'na' => '-',
    default => '',
  };
};

$resolveInspectionName = static function (array $inventory, int $rowNumber) use ($resolveSpecialInspectionName): string {
  $specialRowName = $resolveSpecialInspectionName($rowNumber);
  if ($specialRowName !== null) {
    return $specialRowName;
  }

  $assetCode = trim((string) ($inventory['asset_code'] ?? ''));

  if ($assetCode !== '' && preg_match('/(\d+)\s*$/', $assetCode, $matches)) {
    $assetNumber = (int) $matches[1];
    $specialAssetName = $resolveSpecialInspectionName($assetNumber);

    return $specialAssetName ?? ('Camera ' . $assetNumber);
  }

  return $assetCode !== '' ? $assetCode : 'CCTV';
};
?>

<table class="batch-cctv-table">
  <thead>
    <tr>
      <th rowspan="2" class="col-no cctv-head">No</th>
      <th rowspan="2" class="col-checker cctv-head">Jenis Pemeriksa</th>
      <th rowspan="2" class="col-location cctv-head">Lokasi</th>
      <th colspan="<?= count($dailyPeriods) ?>" class="cctv-head">Bulan : <?= esc($layout['period']['value'] ?? '-') ?></th>
      <th rowspan="2" class="col-paraf cctv-head">Paraf</th>
    </tr>
    <tr>
      <?php foreach ($dailyPeriods as $period): ?>
        <th class="col-day cctv-day-head <?= !empty($period['is_offday']) ? 'cctv-day-off' : '' ?>">
          <?= esc((string) ($period['day'] ?? '')) ?>
        </th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($inventories as $index => $inventory): ?>
      <?php $inventoryId = (int) ($inventory['id'] ?? 0); ?>
      <tr>
        <td><?= $index + 1 ?></td>
        <td class="text-left"><?= esc($resolveInspectionName($inventory, $index + 1)) ?></td>
        <td class="text-left"><?= esc(trim((string) ($inventory['specific_area'] ?? '')) !== '' ? $inventory['specific_area'] : '-') ?></td>

        <?php foreach ($dailyPeriods as $period): ?>
          <?php
          $periodKey = (string) ($period['period_key'] ?? '');
          $status = $dailyChecklistMatrix[$inventoryId][$periodKey] ?? null;
          $cellClass = 'cctv-answer';

          if (!empty($period['is_offday'])) {
            $cellClass .= ' cctv-offday';
          } elseif ($status === 'not_ok') {
            $cellClass .= ' cctv-not-ok';
          }
          ?>
          <td class="<?= esc($cellClass) ?>">
            <?php if (empty($period['is_offday'])): ?>
              <?= esc($displayCctvStatus($status)) ?>
            <?php endif; ?>
          </td>
        <?php endforeach; ?>

        <td></td>
      </tr>
    <?php endforeach; ?>
    <tr class="batch-cctv-footer">
      <td colspan="3">Paraf</td>
      <td colspan="<?= count($dailyPeriods) + 1 ?>"></td>
    </tr>
  </tbody>
</table>
