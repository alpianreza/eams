<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h5>Checklist Compliance</h5>

<div class="mb-3">
  <strong><?= esc($inventory['item_name']) ?></strong><br>
  <span class="text-muted">
    Periode: <?= esc($periodLabel) ?>
  </span>
</div>

<form method="post" action="<?= base_url('compliance/checklist/submit') ?>">
  <?= csrf_field() ?>

  <input type="hidden" name="inventory_id" value="<?= $inventory['id'] ?>">
  <input type="hidden" name="profile_id" value="<?= $profileId ?>">
  <input type="hidden" name="frequency" value="<?= $frequency ?>">

  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th width="60">No</th>
        <th>Poin Pemeriksaan</th>
        <th width="120" class="text-center">OK</th>
        <th width="120" class="text-center">Not OK</th>
        <th width="200">Catatan</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $i => $item): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= esc($item['item_name']) ?></td>

          <td class="text-center">
            <input type="radio"
              name="item_<?= $item['id'] ?>"
              value="ok"
              required>
          </td>

          <td class="text-center">
            <input type="radio"
              name="item_<?= $item['id'] ?>"
              value="not_ok">
          </td>

          <td>
            <input type="text"
              name="remark_<?= $item['id'] ?>"
              class="form-control form-control-sm"
              placeholder="opsional">
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="mt-3">
    <button class="btn btn-primary">
      Simpan Checklist
    </button>

    <a href="<?= previous_url() ?>" class="btn btn-secondary">
      Batal
    </a>
  </div>
</form>

<?= $this->endSection() ?>