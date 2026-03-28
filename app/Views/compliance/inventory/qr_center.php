<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$totalAlbums = isset($totalAlbums) ? (int) $totalAlbums : count($albums ?? []);
$totalQr = isset($totalQr) ? (int) $totalQr : (int) array_sum(array_map(static fn($album) => (int) ($album['count'] ?? 0), $albums ?? []));
?>

<div id="qrCenterPage"
  class="qr-center-page"
  data-url-album="<?= site_url('compliance/inventory/qr-album') ?>"
  data-url-download="<?= site_url('compliance/inventory/qr-album-download') ?>"
  data-url-regen="<?= site_url('compliance/inventory/qr-album-regen') ?>"
  data-url-print="<?= site_url('compliance/inventory/qr-album-print') ?>">

  <section class="card border-0 shadow-sm qr-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="qr-kicker mb-1">Compliance QR</p>
        <h5 class="mb-1 fw-bold">QR Center</h5>
        <p class="text-muted mb-0">Kelola album QR per item, cetak label, download ZIP, dan regenerate dalam satu halaman.</p>
      </div>

      <div class="qr-hero-stats ms-auto">
        <span class="badge text-bg-light border"><strong id="qrAlbumCountLabel"><?= $totalAlbums ?></strong> album</span>
        <span class="badge text-bg-light border"><strong><?= $totalQr ?></strong> QR</span>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm qr-filter-card no-lift mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-8 col-lg-6">
          <label for="qrAlbumSearch" class="form-label form-label-sm mb-1">Cari Album</label>
          <input
            id="qrAlbumSearch"
            type="text"
            class="form-control form-control-sm"
            placeholder="Cari nama item...">
        </div>

        <div class="col-12 col-md-4 col-lg-2 d-grid">
          <button id="btnQrReset" class="btn btn-outline-danger btn-sm" type="button">
            Reset
          </button>
        </div>
      </div>
    </div>
  </section>

  <section id="albumContainer" class="card border-0 shadow-sm no-lift">
    <div class="card-body">
      <div id="qrAlbumGrid" class="row g-3">
        <?php if (empty($albums)): ?>
          <div class="col-12">
            <div class="qr-empty-state text-center text-muted py-5">
              Belum ada data QR untuk ditampilkan.
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($albums as $itemName => $album): ?>
            <?php
            $cover = trim((string) ($album['cover'] ?? ''));
            $coverUrl = $cover !== ''
              ? base_url('uploads/qr/' . rawurlencode($cover))
              : '';
            ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2 qr-album-col">
              <button
                type="button"
                class="qr-album-card w-100 text-start"
                data-name="<?= esc($itemName, 'attr') ?>"
                data-keyword="<?= esc(strtolower($itemName), 'attr') ?>">

                <div class="qr-album-cover">
                  <?php if ($coverUrl !== ''): ?>
                    <img src="<?= esc($coverUrl) ?>?v=<?= time() ?>" alt="Album <?= esc($itemName) ?>" loading="lazy">
                  <?php else: ?>
                    <div class="qr-album-cover-empty">QR</div>
                  <?php endif; ?>
                </div>

                <div class="qr-album-meta">
                  <div class="qr-album-title" title="<?= esc($itemName) ?>"><?= esc($itemName) ?></div>
                  <div class="qr-album-sub"><?= (int) ($album['count'] ?? 0) ?> QR</div>
                </div>
              </button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div id="qrAlbumEmptyFilter" class="qr-empty-state text-center text-muted py-5 d-none">
        Album tidak ditemukan untuk kata kunci ini.
      </div>
    </div>
  </section>

  <section id="albumContent" class="card border-0 shadow-sm no-lift d-none">
    <div class="card-body">
      <div class="d-flex justify-content-end align-items-center mb-3 gap-2 flex-wrap">
        <span id="albumLoading" class="text-muted small d-none">
          <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
          Memuat album...
        </span>
      </div>

      <div id="albumAjax"></div>
    </div>
  </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/qr-center.css?v=' . filemtime(FCPATH . 'assets/css/qr-center.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/qr-center.js?v=' . filemtime(FCPATH . 'js/qr-center.js')) ?>"></script>
<?= $this->endSection() ?>
