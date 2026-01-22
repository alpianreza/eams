<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<a href="<?= base_url('compliance/inventory/detail/' . $inventory['id']) ?>"
  class="btn btn-sm btn-secondary mb-3">
  ← Kembali
</a>

<h5>Checklist: <?= esc($inventory['item_display_name']) ?></h5>

<p class="text-muted">
  Periode: <strong><?= strtoupper($frequency) ?></strong>
  • Key: <code><?= esc($period_key) ?></code>
</p>

<?php if (empty($questions)): ?>
  <div class="alert alert-warning">
    Tidak ada checklist untuk periode ini.
  </div>
<?php else: ?>

  <form action="<?= base_url('compliance/checklist/submit') ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <input type="hidden" name="inventory_id" value="<?= $inventory['id'] ?>">
    <input type="hidden" name="item_type_id" value="<?= $inventory['item_type_id'] ?>">
    <input type="hidden" name="frequency" value="<?= $frequency ?>">
    <input type="hidden" name="period_key" value="<?= $period_key ?>">


    <table class="table table-bordered table-sm">
      <thead>
        <tr>
          <th>Pertanyaan</th>
          <th width="120">Status</th>
          <th>Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($questions as $q): ?>
          <tr>
            <td><?= esc($q['question']) ?></td>
            <td class="text-center">
              <input type="radio"
                name="questions[<?= $q['id'] ?>]"
                value="ok"
                required> ✅
            </td>
            <td class="text-center">
              <input type="radio"
                name="questions[<?= $q['id'] ?>]"
                value="na"> ❌
            </td>
          </tr>
        <?php endforeach; ?>

      </tbody>
    </table>

    <button class="btn btn-success">Simpan Checklist</button>
  </form>

<?php endif; ?>


<?= $this->endSection() ?>