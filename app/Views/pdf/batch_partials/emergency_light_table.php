<?php
$resolveApplicability = static function (array $inventory, array $group, array $checklistMatrix): bool {
  $inventoryId = (int) ($inventory['id'] ?? 0);
  $groupKey = (string) ($group['group_key'] ?? '');
  $questionIds = array_values(array_filter(array_map('intval', $group['question_ids'] ?? [])));
  $typeDescription = strtolower(trim((string) ($inventory['type_description'] ?? '')));

  $hasConcreteStatus = false;
  $hasNaStatus = false;

  foreach ($questionIds as $questionId) {
    $status = $checklistMatrix[$inventoryId][$questionId] ?? null;

    if ($status === 'ok' || $status === 'not_ok') {
      $hasConcreteStatus = true;
      break;
    }

    if ($status === 'na') {
      $hasNaStatus = true;
    }
  }

  if ($hasConcreteStatus) {
    return true;
  }

  if ($typeDescription !== '') {
    $exitHints = ['exit', 'keluar'];
    $daruratHints = ['eye', 'cat', 'emergency', 'darurat'];

    $looksLikeExit = false;
    foreach ($exitHints as $hint) {
      if (strpos($typeDescription, $hint) !== false) {
        $looksLikeExit = true;
        break;
      }
    }

    $looksLikeDarurat = false;
    foreach ($daruratHints as $hint) {
      if (strpos($typeDescription, $hint) !== false) {
        $looksLikeDarurat = true;
        break;
      }
    }

    if ($groupKey === 'lampu_exit' && $looksLikeExit) {
      return true;
    }

    if ($groupKey === 'lampu_darurat' && ($looksLikeDarurat || !$looksLikeExit)) {
      return true;
    }
  }

  if ($hasNaStatus) {
    return false;
  }

  return $groupKey === 'lampu_darurat';
};
?>

<table class="batch-emergency-table">
  <thead>
    <tr>
      <th rowspan="2" class="col-no">NO</th>
      <th rowspan="2" class="col-location">LOKASI</th>
      <?php foreach ($groupedColumns as $group): ?>
        <th colspan="<?= count($group['columns'] ?? []) ?>" class="emergency-band">
          <?= esc($group['label'] ?? '') ?>
        </th>
      <?php endforeach; ?>
    </tr>
    <tr>
      <?php foreach ($groupedColumns as $group): ?>
        <?php foreach (($group['columns'] ?? []) as $column): ?>
          <th class="<?= esc($column['class'] ?? 'col-question') ?> emergency-head">
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
        <td class="text-left"><?= esc(trim((string) ($inventory['specific_area'] ?? '')) !== '' ? $inventory['specific_area'] : '-') ?></td>

        <?php foreach ($groupedColumns as $group): ?>
          <?php $isApplicable = $resolveApplicability($inventory, $group, $checklistMatrix); ?>

          <?php foreach (($group['columns'] ?? []) as $column): ?>
            <?php if (($column['type'] ?? '') === 'field'): ?>
              <?php
              $fieldValue = trim((string) ($inventory[$column['key'] ?? ''] ?? ''));
              $fieldClass = $isApplicable ? '' : 'na-cell';
              ?>
              <td class="<?= esc(trim($fieldClass . ' ' . ($column['class'] ?? ''))) ?>">
                <?= $isApplicable ? esc($fieldValue !== '' ? $fieldValue : '-') : '' ?>
              </td>
            <?php else: ?>
              <?php
              $templateId = (int) ($column['id'] ?? 0);
              $status = $templateId > 0 ? ($checklistMatrix[$inventory['id']][$templateId] ?? null) : null;
              $isNa = $status === 'na' || (!$isApplicable && ($status === null || $status === ''));
              ?>
              <td class="<?= esc(trim(($column['class'] ?? '') . ($isNa ? ' na-cell' : ''))) ?>">
                <?= $isNa ? '' : esc($displayStatus($status)) ?>
              </td>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
