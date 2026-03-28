<?php
$albumName = trim((string) ($itemName ?? ''));
if ($albumName === '' && !empty($rows[0]['item_name'])) {
  $albumName = (string) $rows[0]['item_name'];
}
?>

<div class="qr-album-panel-head d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
  <div>
    <p class="qr-album-kicker mb-1">Detail Album</p>
    <h6 class="fw-bold mb-1"><?= esc($albumName !== '' ? $albumName : 'Album QR') ?></h6>
    <p class="text-muted small mb-0"><?= count($rows) ?> QR tersedia.</p>
  </div>

  <?php if (!empty($rows)): ?>
    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-sm btn-outline-primary qr-album-action" data-action="print" data-album="<?= esc($albumName, 'attr') ?>">
        <i class="bi bi-printer me-1"></i> Print
      </button>

      <button class="btn btn-sm btn-outline-success qr-album-action" data-action="download" data-album="<?= esc($albumName, 'attr') ?>">
        <i class="bi bi-download me-1"></i> Download ZIP
      </button>

      <button class="btn btn-sm btn-outline-warning qr-album-action" data-action="regen" data-album="<?= esc($albumName, 'attr') ?>">
        <i class="bi bi-arrow-repeat me-1"></i> Regenerate
      </button>
    </div>
  <?php endif; ?>
</div>

<?php if (empty($rows)): ?>
  <div class="qr-empty-state text-center text-muted py-5">
    Data QR untuk album ini tidak ditemukan.
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($rows as $inv): ?>
      <?php
      $qrImage = trim((string) ($inv['qr_image'] ?? ''));
      $qrUrl = $qrImage !== '' ? base_url('uploads/qr/' . rawurlencode($qrImage)) : '';
      ?>

      <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <article class="qr-code-card h-100">
          <div class="qr-code-image-wrap">
            <?php if ($qrUrl !== ''): ?>
              <img src="<?= esc($qrUrl) ?>?v=<?= time() ?>" alt="QR <?= esc($inv['asset_code'] ?? '-') ?>" loading="lazy">
            <?php else: ?>
              <div class="qr-code-image-empty">QR</div>
            <?php endif; ?>
          </div>

          <div class="qr-code-meta">
            <div class="qr-code-asset" title="<?= esc($inv['asset_code'] ?? '-') ?>"><?= esc($inv['asset_code'] ?? '-') ?></div>
            <div class="qr-code-area" title="<?= esc($inv['specific_area'] ?? '-') ?>"><?= esc($inv['specific_area'] ?? '-') ?></div>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
