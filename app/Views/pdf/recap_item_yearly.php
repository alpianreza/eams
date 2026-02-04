<htmlpagefooter name="footer">
  <?= $this->include('pdf/_footer') ?>
</htmlpagefooter>

<sethtmlpagefooter name="footer" value="on" />

<?= $this->include('pdf/_header') ?>

<table class="table">
  <thead>
    <tr>
      <th>Poin Pemeriksaan</th>
      <?php foreach (['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'] as $m): ?>
        <th class="center"><?= $m ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($checks as $check): ?>
      <tr>
        <td><?= esc($check['label']) ?></td>
        <?php foreach ($check['months'] as $status): ?>
          <td class="center"><?= $status ?></td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p class="note">
  Keterangan: ✓ = sesuai, ✗ = tidak sesuai, – = tidak berlaku
</p>