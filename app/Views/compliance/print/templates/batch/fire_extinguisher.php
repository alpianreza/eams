<?php
$groupedColumns = $layout['groupedColumns'] ?? [];

$formatDate = static function (?string $date): string {
  if (empty($date)) {
    return '-';
  }

  $timestamp = strtotime($date);
  if ($timestamp === false) {
    return (string) $date;
  }

  $months = [
    1 => 'Jan',
    2 => 'Feb',
    3 => 'Mar',
    4 => 'Apr',
    5 => 'Mei',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Agu',
    9 => 'Sep',
    10 => 'Okt',
    11 => 'Nov',
    12 => 'Des',
  ];

  $monthNumber = (int) date('n', $timestamp);

  return date('d', $timestamp) . '-' . ($months[$monthNumber] ?? date('M', $timestamp)) . '-' . date('y', $timestamp);
};

$displayField = static function (array $inventory, array $column) use ($formatDate): string {
  $key = $column['key'] ?? '';

  if ($key === 'expired_date') {
    return $formatDate($inventory['expired_date'] ?? null);
  }

  $value = $inventory[$key] ?? '-';
  $value = trim((string) $value);

  return $value !== '' ? $value : '-';
};

$displayStatus = static function (?string $status): string {
  return match ($status) {
    'ok' => 'V',
    'not_ok' => 'X',
    'na' => '-',
    default => '',
  };
};
?>

<?php if (empty($inventories)): ?>
  <div class="empty-state">Belum ada inventory untuk item ini.</div>
<?php else: ?>
  <table class="batch-checklist-table">
    <thead>
      <tr>
        <th rowspan="2" class="col-no">NO</th>
        <th rowspan="2" class="col-location">LOKASI</th>
        <th rowspan="2" class="col-pic">PIC</th>

        <?php foreach ($groupedColumns as $group): ?>
          <th colspan="<?= count($group['columns'] ?? []) ?>">
            <?= esc(strtoupper((string) ($group['label'] ?? 'CHECKLIST'))) ?>
          </th>
        <?php endforeach; ?>
      </tr>

      <tr>
        <?php foreach ($groupedColumns as $group): ?>
          <?php foreach (($group['columns'] ?? []) as $column): ?>
            <th class="<?= esc($column['class'] ?? 'col-question') ?>">
              <?= esc($column['label'] ?? '') ?>
            </th>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($inventories as $index => $inventory): ?>
        <tr>
          <td><?= $index + 1 ?></td>
          <td class="text-left"><?= esc($inventory['specific_area'] ?? '-') ?></td>
          <td class="text-left"><?= esc($inventory['pic'] ?? '-') ?></td>

          <?php foreach ($groupedColumns as $group): ?>
            <?php foreach (($group['columns'] ?? []) as $column): ?>
              <?php if (($column['type'] ?? 'question') === 'field'): ?>
                <td><?= esc($displayField($inventory, $column)) ?></td>
              <?php else: ?>
                <?php
                $templateId = (int) ($column['id'] ?? 0);
                $status = $checklistMatrix[$inventory['id']][$templateId] ?? null;
                ?>
                <td class="answer-cell"><?= esc($displayStatus($status)) ?></td>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
