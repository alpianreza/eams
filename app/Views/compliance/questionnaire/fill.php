<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  $relative = static function (string $uri): string {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  };
?>

<div class="questionnaire-page <?= $publicMode ? 'questionnaire-page-public' : '' ?>">
  <?php
    $totalQuestions = 0;
    $totalRequired = ($respondentFields['name'] ?? false) ? 1 : 0;

    foreach ($questionGroups as $sectionQuestions) {
      foreach ($sectionQuestions as $question) {
        if (!empty($question['is_auto_timestamp'])) {
          continue;
        }

        $totalQuestions++;

        if ((int) ($question['is_required'] ?? 0) === 1) {
          $totalRequired++;
        }
      }
    }
  ?>

  <?php if ($publicMode): ?>
    <div class="questionnaire-public-bar mb-3">
      <button type="button" class="btn btn-outline-secondary btn-sm js-history-back">
        <i class="bi bi-arrow-left me-1"></i> Kembali
      </button>
      <span class="questionnaire-public-pill">Form <?= esc($questionnaire['title']) ?></span>
    </div>
  <?php endif; ?>

  <section class="card questionnaire-hero no-lift mb-3">
    <div class="card-body">
      <p class="questionnaire-kicker mb-1">Isi Kuesioner</p>
      <h4 class="fw-bold mb-1"><?= esc($questionnaire['title']) ?></h4>
      <?php if (!empty($questionnaire['subtitle'])): ?>
        <p class="text-muted mb-2"><?= esc($questionnaire['subtitle']) ?></p>
      <?php endif; ?>
      <?php if (!empty($questionnaire['description'])): ?>
        <p class="text-muted mb-0"><?= esc($questionnaire['description']) ?></p>
      <?php endif; ?>
    </div>
  </section>

  <form method="post" action="<?= esc($submitPath) ?>" class="js-questionnaire-form">
    <div
      class="questionnaire-progress-slim <?= $publicMode ? 'is-public' : 'is-internal' ?> mb-3 js-progress-card"
      data-total-questions="<?= (int) $totalQuestions ?>"
      data-total-required="<?= (int) $totalRequired ?>">
      <div class="questionnaire-progress-slim-track" aria-hidden="true">
          <div class="questionnaire-progress-bar js-progress-bar" style="width: 0%;"></div>
      </div>
    </div>

    <?php if ($respondentFields['name'] || $respondentFields['phone'] || $respondentFields['email']): ?>
      <div class="card no-lift questionnaire-form-card mb-3">
        <div class="card-body">
          <h5 class="fw-bold mb-3">Identitas Responden</h5>
          <div class="row g-3">
            <?php if ($respondentFields['name']): ?>
              <div class="col-12 col-md-6">
                <label class="form-label">Nama</label>
                <input type="text" name="respondent_name" class="form-control js-progress-input" value="<?= esc(old('respondent_name')) ?>" required data-progress-required="1" data-progress-kind="identity">
              </div>
            <?php endif; ?>
            <?php if ($respondentFields['phone']): ?>
              <div class="col-12 col-md-6">
                <label class="form-label">No telepon</label>
                <input type="text" name="phone" class="form-control" value="<?= esc(old('phone')) ?>" placeholder="Contoh: 0812xxxxxxx">
              </div>
            <?php endif; ?>
            <?php if ($respondentFields['email']): ?>
              <div class="col-12 col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" placeholder="Contoh: nama@email.com">
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php foreach ($questionGroups as $section => $sectionQuestions): ?>
      <div class="card no-lift questionnaire-form-card mb-3">
        <div class="card-body">
          <div class="questionnaire-section-title mb-3"><?= esc($section) ?></div>

          <div class="d-flex flex-column gap-3">
            <?php foreach ($sectionQuestions as $question): ?>
              <?php $answerValue = $oldAnswers[$question['id']] ?? ''; ?>
              <div
                class="questionnaire-answer-block"
                data-question-block="1"
                data-question-required="<?= (int) ($question['is_required'] ?? 0) ?>"
                data-question-auto="<?= !empty($question['is_auto_timestamp']) ? '1' : '0' ?>"
                data-question-name="answer[<?= (int) $question['id'] ?>]">
                <label class="questionnaire-question-label d-block mb-2">
                  <span class="questionnaire-question-code"><?= (int) ($question['display_order'] ?? 0) ?>.</span>
                  <?= esc($question['question_text']) ?>
                  <?php if ((int) ($question['is_required'] ?? 0) === 1): ?>
                    <span class="text-danger">*</span>
                  <?php endif; ?>
                </label>

                <?php if (!empty($question['help_text'])): ?>
                  <div class="small text-muted mb-2"><?= esc($question['help_text']) ?></div>
                <?php endif; ?>

                <?php $isScaleType = in_array($question['answer_type'], ['scale_5', 'scale_10'], true); ?>
                <?php if (!empty($question['is_auto_timestamp'])): ?>
                  <input type="text" class="form-control" value="Otomatis terisi saat formulir dikirim" readonly>
                <?php elseif ($isScaleType): ?>
                  <?php $scaleCount = max(1, count($question['options'])); ?>
                  <div class="questionnaire-scale-inline-wrap">
                    <div class="questionnaire-scale-inline" style="--scale-count: <?= (int) $scaleCount ?>;">
                      <div class="questionnaire-scale-inline-numbers">
                        <?php foreach ($question['options'] as $option): ?>
                          <span><?= esc($option) ?></span>
                        <?php endforeach; ?>
                      </div>
                      <div class="questionnaire-scale-inline-layout">
                        <div class="questionnaire-scale-inline-edge"><?= esc($question['scale_low_label'] ?: '-') ?></div>
                        <div class="questionnaire-scale-inline-options">
                          <?php foreach ($question['options'] as $index => $option): ?>
                            <?php $optionId = 'q' . (int) $question['id'] . '-scale-' . $index; ?>
                            <label class="questionnaire-scale-inline-option" for="<?= esc($optionId) ?>">
                              <input type="radio" id="<?= esc($optionId) ?>" name="answer[<?= (int) $question['id'] ?>]" value="<?= esc($option) ?>" <?= $answerValue === $option ? 'checked' : '' ?>>
                            </label>
                          <?php endforeach; ?>
                        </div>
                        <div class="questionnaire-scale-inline-edge is-right"><?= esc($question['scale_high_label'] ?: '-') ?></div>
                      </div>
                    </div>
                  </div>
                <?php elseif (in_array($question['answer_type'], ['radio', 'select'], true)): ?>
                  <?php if ($question['answer_type'] === 'radio'): ?>
                    <div class="questionnaire-option-grid">
                      <?php foreach ($question['options'] as $index => $option): ?>
                        <?php $optionId = 'q' . (int) $question['id'] . '-' . $index; ?>
                        <label class="questionnaire-option" for="<?= esc($optionId) ?>">
                          <input type="radio" id="<?= esc($optionId) ?>" name="answer[<?= (int) $question['id'] ?>]" value="<?= esc($option) ?>" <?= $answerValue === $option ? 'checked' : '' ?>>
                          <span><?= esc($option) ?></span>
                        </label>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <select name="answer[<?= (int) $question['id'] ?>]" class="form-select">
                      <option value="">Pilih jawaban</option>
                      <?php foreach ($question['options'] as $option): ?>
                        <option value="<?= esc($option) ?>" <?= $answerValue === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php endif; ?>
                <?php elseif ($question['answer_type'] === 'textarea'): ?>
                  <textarea name="answer[<?= (int) $question['id'] ?>]" rows="4" class="form-control" placeholder="<?= esc($question['placeholder'] ?? '') ?>"><?= esc($answerValue) ?></textarea>
                <?php else: ?>
                  <?php
                    $inputTypeMap = [
                      'date' => 'date',
                      'email' => 'email',
                      'phone' => 'text',
                      'number' => 'number',
                      'text' => 'text',
                    ];
                    $inputType = $inputTypeMap[$question['answer_type']] ?? 'text';
                  ?>
                  <input type="<?= esc($inputType) ?>" name="answer[<?= (int) $question['id'] ?>]" class="form-control" value="<?= esc($answerValue) ?>" placeholder="<?= esc($question['placeholder'] ?? '') ?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="d-flex justify-content-end gap-2 mb-4">
      <?php if ($publicMode): ?>
        <button type="reset" class="btn btn-outline-secondary">Reset</button>
      <?php else: ?>
            <a href="<?= esc($relative('compliance/questionnaires')) ?>" class="btn btn-outline-secondary">Batal</a>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i> Simpan Hasil
      </button>
    </div>
  </form>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= esc($relative('assets/css/questionnaire.css') . '?v=' . filemtime(FCPATH . 'assets/css/questionnaire.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  (function() {
    const form = document.querySelector('.js-questionnaire-form');
    const progressCard = document.querySelector('.js-progress-card');

    if (!form || !progressCard) {
      return;
    }

    const progressBar = progressCard.querySelector('.js-progress-bar');
    const totalQuestions = Number(progressCard.getAttribute('data-total-questions') || '0');
    const totalRequired = Number(progressCard.getAttribute('data-total-required') || '0');

    function isFilledInput(input) {
      if (!input) {
        return false;
      }

      if (input.type === 'radio' || input.type === 'checkbox') {
        return input.checked;
      }

      return String(input.value || '').trim() !== '';
    }

    function getQuestionFilled(block) {
      if (!block || block.getAttribute('data-question-auto') === '1') {
        return true;
      }

      const fieldName = block.getAttribute('data-question-name') || '';
      if (!fieldName) {
        return false;
      }

      const inputs = Array.from(form.querySelectorAll('input, select, textarea')).filter(function(input) {
        return input.name === fieldName;
      });
      if (!inputs.length) {
        return false;
      }

      const firstInput = inputs[0];
      if (firstInput.type === 'radio') {
        return Array.from(inputs).some(function(input) {
          return input.checked;
        });
      }

      return isFilledInput(firstInput);
    }

    function syncProgressState() {
      const questionBlocks = Array.from(form.querySelectorAll('[data-question-block="1"]')).filter(function(block) {
        return block.getAttribute('data-question-auto') !== '1';
      });

      let answeredQuestions = 0;
      let answeredRequired = 0;

      questionBlocks.forEach(function(block) {
        const filled = getQuestionFilled(block);
        const required = block.getAttribute('data-question-required') === '1';

        if (filled) {
          answeredQuestions += 1;
          if (required) {
            answeredRequired += 1;
          }
        }
      });

      const requiredInputs = Array.from(form.querySelectorAll('.js-progress-input[data-progress-required="1"]'));
      requiredInputs.forEach(function(input) {
        if (isFilledInput(input)) {
          answeredRequired += 1;
        }
      });

      const percentBase = totalRequired > 0 ? totalRequired : totalQuestions;
      const percentValue = percentBase > 0 ? Math.round((answeredRequired / percentBase) * 100) : 100;

      if (progressBar) {
        progressBar.style.width = percentValue + '%';
      }

      progressCard.classList.remove('is-low', 'is-mid', 'is-high', 'is-complete');

      if (percentValue >= 100) {
        progressCard.classList.add('is-complete');
      } else if (percentValue >= 70) {
        progressCard.classList.add('is-high');
      } else if (percentValue >= 35) {
        progressCard.classList.add('is-mid');
      } else {
        progressCard.classList.add('is-low');
      }
    }

    form.addEventListener('input', syncProgressState);
    form.addEventListener('change', syncProgressState);
    syncProgressState();
  })();
</script>

<?php if ($publicMode): ?>
  <script>
    document.querySelectorAll('.js-history-back').forEach(function(button) {
      const referrer = document.referrer || '';

      try {
        if (!referrer || new URL(referrer).origin !== window.location.origin) {
          button.style.display = 'none';
          return;
        }
      } catch (error) {
        button.style.display = 'none';
        return;
      }

      button.addEventListener('click', function() {
        window.history.back();
      });
    });
  </script>
<?php endif; ?>
<?= $this->endSection() ?>
