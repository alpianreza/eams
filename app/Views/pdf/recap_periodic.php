<table class="table">
  <thead>
    <tr>
      <th>Item</th>
      <th>Lokasi</th>
      <?php foreach ($periodColumns as $p): ?>
        <th class="center"><?= $p ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $item): ?>
      <tr>
        <td><?= esc($item['item_name']) ?></td>
        <td><?= esc($item['location']) ?></td>

        <?php foreach ($periodColumns as $p): ?>
          <td class="center">
            <?= $item['statuses'][$p] ?? '–' ?>
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>