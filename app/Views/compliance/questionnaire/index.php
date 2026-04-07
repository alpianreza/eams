<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  $relative = static function (string $uri): string {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  };
?>

<div class="questionnaire-page" x-data="questionnaireIndexPage()">
  <section class="card questionnaire-hero mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="questionnaire-kicker mb-1">Compliance</p>
        <h4 class="fw-bold mb-1">Pusat Kuesioner</h4>
        <p class="text-muted mb-0">Kelola berbagai jenis kuesioner, isi form responden, dan simpan hasilnya ke PDF.</p>
      </div>

      <?php if (hasRole(['admin', 'compliance'])): ?>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= esc($relative('compliance/questionnaires/analytics')) ?>" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-bar-chart-line"></i>
            Analitik
          </a>
          <a href="<?= esc($relative('compliance/questionnaires/create')) ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i>
            Tambah Kuesioner
          </a>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (empty($templates)): ?>
    <div class="card no-lift">
      <div class="card-body py-5 text-center text-muted">
        Belum ada template kuesioner. Silakan tambahkan kuesioner baru.
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($templates as $template): ?>
        <div class="col-12 col-lg-6">
          <div class="card questionnaire-card h-100 no-lift">
            <div class="card-body d-flex flex-column gap-3">
              <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                  <div class="questionnaire-card-kicker">Template Kuesioner</div>
                  <h5 class="mb-1"><?= esc($template['title']) ?></h5>
                  <?php if (!empty($template['subtitle'])): ?>
                    <p class="text-muted mb-2"><?= esc($template['subtitle']) ?></p>
                  <?php endif; ?>
                </div>

                <span class="badge <?= (int) ($template['active'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                  <?= (int) ($template['active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?>
                </span>
              </div>

              <?php if (!empty($template['description'])): ?>
                <p class="text-muted small mb-0"><?= esc($template['description']) ?></p>
              <?php endif; ?>

              <div class="questionnaire-stats">
                <div>
                  <span class="questionnaire-stat-label">Pertanyaan</span>
                  <strong><?= (int) ($template['question_count'] ?? 0) ?></strong>
                </div>
                <div>
                  <span class="questionnaire-stat-label">Respon</span>
                  <strong><?= (int) ($template['response_count'] ?? 0) ?></strong>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2 mt-auto">
                <?php if ((int) ($template['active'] ?? 0) === 1): ?>
                  <a href="<?= esc($template['public_path']) ?>" class="btn btn-success btn-sm" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Form
                  </a>
                <?php else: ?>
                  <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                    <i class="bi bi-slash-circle me-1"></i> Form Nonaktif
                  </button>
                <?php endif; ?>
                <button
                  type="button"
                  class="btn btn-outline-success btn-sm"
                  @click="copyLink('<?= esc($template['public_path']) ?>')"
                  <?= (int) ($template['active'] ?? 0) === 1 ? '' : 'disabled' ?>>
                  <i class="bi bi-link-45deg me-1"></i> Salin Link
                </button>
                <a href="<?= esc($relative('compliance/questionnaires/' . $template['id'])) ?>" class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-layout-text-window-reverse me-1"></i> Detail
                </a>
                <a href="<?= esc($relative('compliance/questionnaires/analytics') . '?questionnaire_id=' . (int) $template['id']) ?>" class="btn btn-outline-info btn-sm">
                  <i class="bi bi-bar-chart-line me-1"></i> Analitik
                </a>
                <?php if (hasRole(['admin', 'compliance'])): ?>
                  <a href="<?= esc($relative('compliance/questionnaires/edit/' . $template['id'])) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= esc($relative('assets/css/questionnaire.css') . '?v=' . filemtime(FCPATH . 'assets/css/questionnaire.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('alpine:init', function() {
    Alpine.data('questionnaireIndexPage', function() {
      return {
        async copyLink(path) {
          if (!path) {
            return;
          }

          const fullUrl = window.location.origin + path;

          try {
            if (navigator.clipboard && window.isSecureContext) {
              await navigator.clipboard.writeText(fullUrl);
            } else {
              const tempInput = document.createElement('input');
              tempInput.value = fullUrl;
              document.body.appendChild(tempInput);
              tempInput.select();
              document.execCommand('copy');
              tempInput.remove();
            }

            safeToast('Link kuesioner berhasil disalin.', 'success');
          } catch (error) {
            safeToast('Link belum bisa disalin otomatis. Silakan salin manual.', 'warning');
          }
        }
      };
    });
  });
</script>
<?= $this->endSection() ?>
