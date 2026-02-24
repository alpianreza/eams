<div class="d-flex justify-content-between align-items-center mb-3">

  <div>
    <b><?= esc($rows[0]['item_name'] ?? '') ?></b>
    <span class="badge bg-secondary"><?= count($rows) ?> QR</span>
  </div>

  <div>
    <button class="btn btn-sm btn-primary"
      onclick="printAlbum('<?= esc($rows[0]['item_name'] ?? '') ?>')">
      Print
    </button>

    <button class="btn btn-sm btn-success"
      onclick="downloadAlbum('<?= esc($rows[0]['item_name'] ?? '') ?>')">
      Download
    </button>

    <button class="btn btn-sm btn-warning"
      onclick="regenAlbum('<?= esc($rows[0]['item_name'] ?? '') ?>')">
      Regenerate QR
    </button>
  </div>

</div>

<div class="row g-3">

  <?php foreach ($rows as $inv): ?>

    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
      <div class="qr-card">

        <img src="<?= base_url('uploads/qr/' . $inv['qr_image']) . '?v=' . time() ?>" class="img-fluid">

        <div class="text-muted small text-center">
          <?= esc($inv['specific_area']) ?>
        </div>

      </div>
    </div>

  <?php endforeach; ?>

</div>