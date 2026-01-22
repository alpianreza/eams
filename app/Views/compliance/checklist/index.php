<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<a href="<?= base_url('compliance/inventory/detail/' . $inventory['id']) ?>"
  class="btn btn-sm btn-secondary mb-3">← Kembali</a>

<h5>Checklist: <?= esc($inventory['item_display_name']) ?></h5>

<p class="text-muted">
  Periode:
  <strong><?= esc(strtoupper($frequency)) ?></strong>
  &nbsp;•&nbsp;
  <?= esc($period_label) ?>
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
    <form action="<?= base_url('compliance/checklist/submit') ?>" method="post">
      <?= csrf_field() ?>

      <input type="hidden" name="inventory_id" value="<?= $inventory['id'] ?>">
      <input type="hidden" name="item_type_id" value="<?= $inventory['item_type_id'] ?>">
      <input type="hidden" name="frequency" value="<?= $frequency ?>">
      <input type="hidden" name="period_key" value="<?= $period_key ?>">

      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th width="5%">No</th>
            <th>Pertanyaan</th>
            <th width="20%" class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>

          <?php foreach ($questions as $i => $q): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= esc($q['question']) ?></td>
              <td class="text-center">

                <div class="form-check form-check-inline">
                  <input class="form-check-input"
                    type="radio"
                    name="questions[<?= $q['id'] ?>]"
                    value="ok"
                    required>
                  <label class="form-check-label">✅ OK</label>
                </div>

                <div class="form-check form-check-inline">
                  <input class="form-check-input"
                    type="radio"
                    name="questions[<?= $q['id'] ?>]"
                    value="nok"
                    required>
                  <label class="form-check-label">❌ NOT OK</label>
                </div>

              </td>
            </tr>
          <?php endforeach; ?>

        </tbody>
      </table>

      <?php if ($isLocked): ?>
        <div class="alert alert-warning">
          Checklist untuk periode ini sudah diisi dan terkunci.
        </div>
      <?php else: ?>
        <button class="btn btn-success">
          Simpan Checklist
        </button>
      <?php endif; ?>

    </form>

  <?php endif; ?>
<?php endif; ?>


<?= $this->endSection() ?>