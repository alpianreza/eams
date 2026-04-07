<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  $relative = static function (string $uri): string {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  };
?>

<div class="questionnaire-page questionnaire-public-shell">
  <section class="card questionnaire-hero questionnaire-thanks-card no-lift">
    <div class="card-body text-center px-4 py-5">
      <div class="questionnaire-thanks-icon mb-3">
        <i class="bi bi-check2-circle"></i>
      </div>

      <p class="questionnaire-kicker mb-2">Kuesioner Berhasil Dikirim</p>
      <h2 class="fw-bold mb-3">Data Anda Telah Direkam</h2>

      <p class="questionnaire-thanks-copy mb-2">
        Jawaban untuk <strong><?= esc($questionnaire['title']) ?></strong> sudah berhasil kami simpan.
      </p>
      <p class="questionnaire-thanks-copy mb-4">
        Terima kasih telah meluangkan waktu untuk mengisi kuesioner ini.
      </p>

      <?php if (!empty($submitInfo)): ?>
        <div class="questionnaire-thanks-meta mx-auto mb-4">
          <?php if (!empty($submitInfo['submitted_at'])): ?>
            <div>
              <span>Waktu pengiriman</span>
              <strong><?= esc($submitInfo['submitted_at']) ?></strong>
            </div>
          <?php endif; ?>
          <?php if (!empty($submitInfo['response_code'])): ?>
            <div>
              <span>Kode respon</span>
              <strong><?= esc($submitInfo['response_code']) ?></strong>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="d-flex flex-wrap justify-content-center gap-2">
        <a href="<?= esc($backToFormPath) ?>" class="btn btn-outline-primary">
          <i class="bi bi-arrow-repeat me-1"></i> Buka Form Lagi
        </a>
        <button type="button" class="btn btn-primary js-thanks-done" data-fallback-url="<?= esc($backToFormPath) ?>">
          <i class="bi bi-check-lg me-1"></i> Selesai
        </button>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= esc($relative('assets/css/questionnaire.css') . '?v=' . filemtime(FCPATH . 'assets/css/questionnaire.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.querySelectorAll('.js-thanks-done').forEach(function(button) {
    button.addEventListener('click', function() {
      const fallbackUrl = button.getAttribute('data-fallback-url') || '/';

      if (window.history.length > 1) {
        window.history.back();
        return;
      }

      window.location.href = fallbackUrl;
    });
  });
</script>
<?= $this->endSection() ?>
