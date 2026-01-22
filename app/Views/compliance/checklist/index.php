<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<a href="<?= base_url('compliance/inventory/detail/' . $inventory['id']) ?>"
  class="btn btn-sm btn-secondary mb-3">← Kembali</a>

<h5>Checklist: <?= esc($inventory['item_display_name']) ?></h5>

<p class="text-muted">
  Periode: <strong><?= strtoupper($frequency) ?></strong>
  • Key: <code><?= esc($period_key) ?></code>
</p>

<?php if ($isLocked): ?>
  <div class="alert alert-success">
    🔒 Checklist untuk periode ini <strong>sudah diisi</strong>.
  </div>
<?php else: ?>

  <?php if (empty($questions)): ?>
    <div class="alert alert-warning">
      Tidak ada checklist untuk periode ini.
    </div>
  <?php else: ?>
    <form method="post" action="<?= base_url('compliance/checklist/submit') ?>">
      <?= csrf_field() ?>

      <!-- HIDDEN WAJIB -->
      <input type="hidden" name="inventory_id" value="<?= $inventory['id'] ?>">
      <input type="hidden" name="item_type_id" value="<?= $inventory['item_type_id'] ?>">
      <input type="hidden" name="frequency" value="<?= $frequency ?>">
      <input type="hidden" name="period_key" value="<?= $period_key ?>">

      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>Pertanyaan</th>
            <th class="text-center" width="120">OK</th>
            <th class="text-center" width="120">NOT OK</th>
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
                  required>
              </td>

              <td class="text-center">
                <input type="radio"
                  name="questions[<?= $q['id'] ?>]"
                  value="not_ok"
                  required>
              </td>
            </tr>
          <?php endforeach; ?>

        </tbody>
      </table>

      <button class="btn btn-success">
        ✔ Simpan Checklist
      </button>
    </form>


  <?php endif; ?>
<?php endif; ?>


<?= $this->endSection() ?>