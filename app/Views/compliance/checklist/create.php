<?php
$isWritable = $isWritable ?? false;
?>

<div class="card shadow-sm border-0">
  <div class="card-body">

    <h5 class="mb-3"><?= esc($checklist['name']) ?></h5>

    <div class="mb-2 text-muted">
      <?= esc($inventory['asset_type']) ?>
      — <?= esc($inventory['asset_code']) ?>
    </div>

    <form method="post" enctype="multipart/form-data">

      <!-- STATUS -->
      <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
          <option value="">-- pilih --</option>
          <option value="OK">OK</option>
          <option value="TIDAK">TIDAK</option>
          <option value="NA">NA</option>
        </select>

      </div>

      <!-- REMARK -->
      <div class="mb-3">
        <label class="form-label">Catatan</label>
        <textarea name="remark"
          class="form-control"
          rows="3"></textarea>
      </div>

      <!-- FOTO -->
      <div class="mb-3">
        <label class="form-label">
          Foto <?= $checklist['require_photo'] ? '(Wajib)' : '(Opsional)' ?>
        </label>
        <input type="file"
          name="photo"
          accept="image/*"
          capture="environment"
          class="form-control"
          <?= $checklist['require_photo'] ? 'required' : '' ?>>
      </div>

      <button class="btn btn-primary w-100">
        Simpan Checklist
      </button>

    </form>

  </div>
</div>