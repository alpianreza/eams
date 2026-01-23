<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<a href="<?= base_url('compliance/inventory/detail/' . $inventory['id']) ?>"
  class="btn btn-sm btn-secondary mb-3">
  ← Kembali
</a>

<h5>Checklist: <?= esc($inventory['item_display_name']) ?></h5>

<p class="text-muted mb-3">
  Frekuensi: <strong><?= strtoupper($frequency) ?></strong><br>
  Periode aktif: <strong><?= esc($period_label) ?></strong>
</p>

<!-- ================= KALENDER PERIODE ================= -->
<div id="checklist-calendar"
  data-inventory="<?= $inventory['id'] ?>"
  data-frequency="<?= $frequency ?>"
  data-periods='<?= json_encode($periods) ?>'>
</div>

<hr class="my-4">

<?php if ($isLocked): ?>
  <div class="alert alert-info">
    Checklist untuk periode ini sudah diisi dan terkunci.
  </div>
<?php endif; ?>

<?php if (! $isAllowed): ?>
  <div class="alert alert-danger">
    Periode checklist sudah lewat dan tidak bisa diisi.
  </div>
<?php endif; ?>

<?php if ($isAllowed && !empty($questions)): ?>

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
          <th width="25%" class="text-center">Status</th>
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

              <!-- REMARK -->
              <div class="mt-2 d-none" id="remark-box-<?= $q['id'] ?>">
                <textarea
                  name="remarks[<?= $q['id'] ?>]"
                  class="form-control form-control-sm"
                  placeholder="Wajib diisi jika NOT OK"></textarea>
              </div>

              <!-- FOTO -->
              <div class="mt-2 d-none" id="photo-box-<?= $q['id'] ?>">
                <input type="file"
                  name="photos[<?= $q['id'] ?>]"
                  class="form-control form-control-sm"
                  accept="image/*">
              </div>

            </td>
          </tr>
        <?php endforeach; ?>

      </tbody>
    </table>

    <button class="btn btn-success"
      <?= ($isLocked || ! $isAllowed) ? 'disabled' : '' ?>>
      Simpan Checklist
    </button>

  </form>

<?php elseif (empty($questions)): ?>
  <div class="alert alert-warning">
    Tidak ada checklist untuk periode ini.
  </div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/checklist-calendar.js') ?>"></script>

<script>
  document.querySelectorAll('.status-radio').forEach(radio => {
    radio.addEventListener('change', function() {
      const qid = this.dataset.qid;

      const remarkBox = document.getElementById('remark-box-' + qid);
      const photoBox = document.getElementById('photo-box-' + qid);

      const remarkInput = remarkBox.querySelector('textarea');
      const photoInput = photoBox.querySelector('input');

      if (this.value === 'ng') {
        remarkBox.classList.remove('d-none');
        photoBox.classList.remove('d-none');

        remarkInput.required = true;
        photoInput.required = true;
      } else {
        remarkBox.classList.add('d-none');
        photoBox.classList.add('d-none');

        remarkInput.required = false;
        remarkInput.value = '';

        photoInput.required = false;
        photoInput.value = '';
      }
    });
  });
</script>
<?= $this->endSection() ?>