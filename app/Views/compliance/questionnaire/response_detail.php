<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  $relative = static function (string $uri): string {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  };
?>

<div class="questionnaire-page">
  <section class="card questionnaire-hero no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between gap-3 align-items-start">
      <div>
        <p class="questionnaire-kicker mb-1">Hasil Kuesioner</p>
        <h4 class="fw-bold mb-1"><?= esc($questionnaire['title']) ?></h4>
        <?php if (!empty($questionnaire['subtitle'])): ?>
          <p class="text-muted mb-1"><?= esc($questionnaire['subtitle']) ?></p>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-2 small text-muted">
          <span>Kode: <strong><?= esc($response['response_code']) ?></strong></span>
          <span>Dikirim: <strong><?= esc($response['submitted_at'] ?: '-') ?></strong></span>
        </div>
      </div>

      <div class="d-flex gap-2">
        <a href="<?= esc($pdfPath) ?>" target="_blank" class="btn btn-success">
          <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </a>
        <?php if (hasRole(['admin', 'compliance'])): ?>
          <form method="post" action="<?= esc($relative('compliance/questionnaires/response/delete/' . $response['id'])) ?>" class="js-delete-form" data-delete-title="Hapus respon ini?" data-delete-text="Jawaban responden akan dihapus permanen.">
            <button type="submit" class="btn btn-outline-danger">
              <i class="bi bi-trash me-1"></i> Hapus
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($respondentFields['name'] || $respondentFields['phone'] || $respondentFields['email']): ?>
    <div class="card questionnaire-form-card no-lift mb-3">
      <div class="card-body">
        <h5 class="fw-bold mb-3">Data Responden</h5>
        <div class="row g-3">
          <?php if ($respondentFields['name']): ?>
            <div class="col-12 col-md-6 col-xl-4">
              <div class="questionnaire-meta-label">Nama</div>
              <div class="questionnaire-meta-value"><?= esc($response['respondent_name']) ?></div>
            </div>
          <?php endif; ?>
          <?php if ($respondentFields['phone']): ?>
            <div class="col-12 col-md-6 col-xl-4">
              <div class="questionnaire-meta-label">No telepon</div>
              <div class="questionnaire-meta-value"><?= esc($response['phone'] ?: '-') ?></div>
            </div>
          <?php endif; ?>
          <?php if ($respondentFields['email']): ?>
            <div class="col-12 col-md-6 col-xl-4">
              <div class="questionnaire-meta-label">Email</div>
              <div class="questionnaire-meta-value"><?= esc($response['email'] ?: '-') ?></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php foreach ($questionGroups as $section => $sectionQuestions): ?>
    <div class="card questionnaire-form-card no-lift mb-3">
      <div class="card-body">
        <div class="questionnaire-section-title mb-3"><?= esc($section) ?></div>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($sectionQuestions as $question): ?>
            <?php $answerValue = $answersMap[$question['id']] ?? ''; ?>
            <div class="questionnaire-answer-review">
              <div class="questionnaire-question-label mb-2">
                <span class="questionnaire-question-code"><?= (int) ($question['display_order'] ?? 0) ?>.</span>
                <?= esc($question['question_text']) ?>
              </div>

              <?php if (in_array($question['answer_type'], ['scale_5', 'scale_10'], true)): ?>
                <div class="questionnaire-scale-wrap">
                  <div class="questionnaire-scale-labels">
                    <span><?= esc($question['scale_low_label'] ?: '-') ?></span>
                    <span><?= esc($question['scale_high_label'] ?: '-') ?></span>
                  </div>
                  <div class="questionnaire-scale-grid <?= count($question['options']) > 5 ? 'is-wide' : '' ?>">
                    <?php foreach ($question['options'] as $option): ?>
                      <div class="questionnaire-scale-option <?= $answerValue === $option ? 'selected' : '' ?>">
                        <span class="questionnaire-scale-number"><?= esc($option) ?></span>
                        <span class="questionnaire-option-mark"><?= $answerValue === $option ? 'V' : '' ?></span>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php elseif (!empty($question['options'])): ?>
                <div class="questionnaire-option-review">
                  <?php foreach ($question['options'] as $option): ?>
                    <div class="questionnaire-option-review-item <?= $answerValue === $option ? 'selected' : '' ?>">
                      <span class="questionnaire-option-mark"><?= $answerValue === $option ? 'V' : '' ?></span>
                      <span><?= esc($option) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="questionnaire-free-answer"><?= esc($answerValue !== '' ? $answerValue : '-') ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= esc($relative('assets/css/questionnaire.css') . '?v=' . filemtime(FCPATH . 'assets/css/questionnaire.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.querySelectorAll('.js-delete-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();

      Swal.fire({
        title: form.getAttribute('data-delete-title') || 'Hapus data ini?',
        text: form.getAttribute('data-delete-text') || 'Data yang dihapus tidak bisa dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d'
      }).then(function(result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
<?= $this->endSection() ?>
