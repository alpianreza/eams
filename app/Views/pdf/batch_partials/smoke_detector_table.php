<table class="batch-smoke-table">
  <thead>
    <tr>
      <th class="col-no">No.</th>
      <th class="col-location">Lokasi</th>
      <?php foreach ($masters as $master): ?>
        <th class="smoke-head vertical-head">
          <span><?= esc($master['question'] ?? '') ?></span>
        </th>
      <?php endforeach; ?>
      <th class="col-note">Keterangan</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($inventories as $index => $inventory): ?>
      <tr>
        <td><?= $index + 1 ?></td>
        <td class="text-left"><?= esc(trim((string) ($inventory['specific_area'] ?? '')) !== '' ? trim((string) $inventory['specific_area']) : '-') ?></td>
        <?php foreach ($masters as $master): ?>
          <?php $status = $checklistMatrix[$inventory['id']][(int) ($master['id'] ?? 0)] ?? null; ?>
          <td class="answer-cell"><?= esc($displayStatus($status)) ?></td>
        <?php endforeach; ?>
        <td class="col-note"></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
