<h5><?= esc($inventory['item_display_name']) ?></h5>

<p class="text-muted mb-4">
  Frekuensi: <strong><?= strtoupper($frequency) ?></strong><br>
  Periode aktif: <strong><?= esc($period_label) ?></strong>
</p>

<?php if ($isLocked): ?>
  <div class="alert alert-info">
    Checklist untuk periode ini sudah diisi dan terkunci.
  </div>
<?php endif ?>

<?php if (! $isLocked && ! empty($questions)): ?>

  <form action="<?= base_url('compliance/checklist/submit') ?>"
    method="post"
    enctype="multipart/form-data">

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
          <th width="30%" class="text-center">Status</th>
        </tr>
      </thead>
      <tbody>

        <?php foreach ($questions as $i => $q): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= esc($q['question']) ?></td>
            <td class="text-center">

              <div class="form-check form-check-inline">
                <input class="form-check-input status-radio"
                  type="radio"
                  name="questions[<?= $q['id'] ?>]"
                  value="ok"
                  data-qid="<?= $q['id'] ?>"
                  required>
                <label class="form-check-label">✅ OK</label>
              </div>

              <div class="form-check form-check-inline">
                <input class="form-check-input status-radio"
                  type="radio"
                  name="questions[<?= $q['id'] ?>]"
                  value="ng"
                  data-qid="<?= $q['id'] ?>"
                  required>
                <label class="form-check-label">❌ NOT OK</label>
              </div>

              <div class="mt-2 d-none" id="remark-box-<?= $q['id'] ?>">
                <textarea name="remarks[<?= $q['id'] ?>]"
                  class="form-control form-control-sm"
                  placeholder="Wajib diisi jika NOT OK"></textarea>
              </div>

              <div class="mt-2 d-none" id="photo-box-<?= $q['id'] ?>">
                <input type="file"
                  name="photos[<?= $q['id'] ?>]"
                  class="form-control form-control-sm"
                  accept="image/*">
              </div>

            </td>
          </tr>
        <?php endforeach ?>

      </tbody>
    </table>

    <button class="btn btn-success">
      Simpan Checklist
    </button>

  </form>

<?php else: ?>
  <div class="alert alert-warning">
    Tidak ada checklist untuk periode ini.
  </div>
<?php endif ?>