<table class="batch-weekly-table">
  <thead>
    <tr>
      <th rowspan="3" class="col-no">NO</th>
      <th rowspan="3" class="col-info">KETERANGAN</th>
      <th colspan="<?= array_sum(array_map(static fn(array $group): int => count($group['columns'] ?? []), $groupedColumns)) ?>" class="month-band">
        <?= esc($period['label'] ?? 'BULAN') ?>:
        <?php if (!empty($period['value'])): ?>
          <?= esc($period['value']) ?>
        <?php endif; ?>
      </th>
      <th rowspan="3" class="col-note">KET</th>
    </tr>
    <tr>
      <?php foreach ($groupedColumns as $group): ?>
        <th colspan="<?= count($group['columns'] ?? []) ?>" class="question-head">
          <?= esc($group['label'] ?? '') ?>
        </th>
      <?php endforeach; ?>
    </tr>
    <tr>
      <?php foreach ($groupedColumns as $group): ?>
        <?php foreach (($group['columns'] ?? []) as $column): ?>
          <th class="week-head col-week"><?= esc($column['label'] ?? '') ?></th>
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
          <?php
          $templateId = (int) ($group['template_id'] ?? 0);
          ?>
          <?php foreach (($group['columns'] ?? []) as $column): ?>
            <?php
            $weekNumber = (int) ($column['week'] ?? 0);
            $status = $weeklyChecklistMatrix[$inventory['id']][$templateId][$weekNumber] ?? null;
            ?>
            <td class="week-answer col-week"><?= esc($displayStatus($status)) ?></td>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <td class="col-note"></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
