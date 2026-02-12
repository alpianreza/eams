<?php if ($ev): ?>

  <div class="row">

    <div class="col-md-6">
      <img src="<?= base_url('uploads/checklist/' . $ev['photo']) ?>"
        class="img-fluid rounded shadow-sm">
    </div>

    <div class="col-md-6">

      <h5 class="text-danger mb-3">
        ✗ <?= esc($ev['item_name']) ?>
      </h5>

      <p class="mb-1">
        <strong>Kode:</strong>
        <?= esc($ev['asset_code'] ?? '-') ?>
      </p>

      <p class="mb-1">
        <strong>Lokasi:</strong>
        <?= esc($ev['specific_area'] ?? '-') ?>
      </p>

      <p class="mb-1">
        <strong>Periode:</strong>
        <?= esc($ev['period_key']) ?>
      </p>

      <p class="mb-1">
        <strong>Status:</strong>
        <span class="badge bg-danger">✗ Tidak Sesuai</span>
      </p>

      <hr>

      <strong>Catatan:</strong>
      <p>
        <?php
        $remark = trim((string) $ev['remark']);

        if ($remark === '') {
          echo '<span class="text-muted">Tidak ada catatan</span>';
        } else {
          echo esc(strlen($remark) > 60
            ? substr($remark, 0, 60) . '...'
            : $remark);
        }
        ?>
      </p>

      <hr>

      <h6>Status Tindak Lanjut</h6>

      <?php
      $statusColor = match ($ev['follow_up_status']) {
        'closed' => 'success',
        'monitoring' => 'warning',
        default => 'danger'
      };
      ?>

      <span class="badge badge-<?= $statusColor ?>">
        <?= ucfirst($ev['follow_up_status']) ?>
      </span>

      <?php if (hasRole(['admin', 'compliance'])): ?>

        <form id="followUpForm" class="mt-3">
          <input type="hidden" name="id" value="<?= $ev['id'] ?>">

          <?php $currentStatus = $ev['follow_up_status'] ?? 'open'; ?>

          <select name="follow_up_status" class="form-control mb-2">

            <option value="open" <?= $currentStatus === 'open' ? 'selected' : '' ?>>
              Open
            </option>

            <option value="monitoring" <?= $currentStatus === 'monitoring' ? 'selected' : '' ?>>
              Monitoring
            </option>

            <option value="closed" <?= $currentStatus === 'closed' ? 'selected' : '' ?>>
              Closed
            </option>

          </select>


          <textarea name="follow_up_note"
            class="form-control mb-2"
            placeholder="Catatan tindak lanjut..."><?= esc($ev['follow_up_note']) ?></textarea>

          <button type="submit" class="btn btn-success btn-sm">
            Simpan
          </button>
        </form>

      <?php endif; ?>



      <?php if (!empty($ev['inventory_id'])): ?>
        <a href="<?= base_url('compliance/inventory/detail/' . $ev['inventory_id']) ?>"
          class="btn btn-primary btn-sm mt-3">
          Lihat Inventory
        </a>
      <?php endif; ?>

    </div>

  </div>

<?php else: ?>
  <div class="text-center text-muted">
    Data tidak ditemukan
  </div>
<?php endif; ?>