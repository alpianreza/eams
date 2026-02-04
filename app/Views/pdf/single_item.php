<?= $this->include('pdf/_style') ?>
<?= $this->include('pdf/_header') ?>

<table class="table-checklist">
  <thead>
    <tr>
      <th class="col-no">No</th>
      <th class="col-question">Pertanyaan</th>
      <th class="col-status">Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($questions as $i => $q): ?>
      <tr>
        <td class="col-no"><?= $i + 1 ?></td>
        <td class="col-question"><?= esc($q['question']) ?></td>
        <td class="col-status"><?= $q['status_symbol'] ?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?php
$allEmpty = true;
foreach ($questions as $q) {
  if ($q['status_symbol'] !== '–') {
    $allEmpty = false;
    break;
  }
}
?>

<?php if ($allEmpty): ?>
  <p style="margin-top:8px; font-size:10px;">
    Catatan: Checklist pada periode ini belum dilakukan.
  </p>
<?php endif; ?>