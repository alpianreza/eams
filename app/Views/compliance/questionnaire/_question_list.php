<div class="card no-lift mb-3">
  <div class="card-body">
    <?php
      $relative = static function (string $uri): string {
        $path = parse_url(base_url($uri), PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : base_url($uri);
      };
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h5 class="fw-bold mb-0">Daftar Pertanyaan</h5>
        <?php if ($isWriteAllowed): ?>
          <div class="small text-muted mt-1">Geser kartu lewat ikon titik di kiri untuk ubah urutan pertanyaan.</div>
        <?php endif; ?>
      </div>
      <span class="badge text-bg-primary"><?= count($questions) ?> pertanyaan</span>
    </div>

    <?php if (empty($questionGroups)): ?>
      <div class="text-muted">Belum ada pertanyaan untuk kuesioner ini.</div>
    <?php else: ?>
      <?php foreach ($questionGroups as $section => $sectionQuestions): ?>
        <div class="questionnaire-section-block mb-4">
          <div class="questionnaire-section-title mb-2"><?= esc($section) ?></div>
          <div class="questionnaire-sort-list" data-question-sort-list>
          <?php foreach ($sectionQuestions as $question): ?>
            <?php
              $currentSection = trim((string) ($question['section_label'] ?? ''));
              $questionSectionOptions = $sectionOptions;
              $builderState = json_encode([
                'answerType' => (string) ($question['answer_type'] ?? 'radio'),
                'questionText' => (string) ($question['question_text'] ?? ''),
                'optionsText' => implode("\n", $question['options']),
                'placeholder' => (string) ($question['placeholder'] ?? ''),
                'helpText' => (string) ($question['help_text'] ?? ''),
                'required' => (int) ($question['is_required'] ?? 0) === 1,
                'scaleLowLabel' => (string) ($question['scale_low_label'] ?? ''),
                'scaleHighLabel' => (string) ($question['scale_high_label'] ?? ''),
                'sectionLabel' => $currentSection !== '' ? $currentSection : '',
              ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
              if ($currentSection !== '' && !in_array($currentSection, $questionSectionOptions, true)) {
                $questionSectionOptions[] = $currentSection;
              }
            ?>
            <details class="questionnaire-question-item mb-2 <?= $isWriteAllowed ? 'is-sortable' : '' ?>" data-question-id="<?= (int) $question['id'] ?>" <?= $isWriteAllowed ? 'draggable="true"' : '' ?> <?= (int) ($openQuestionId ?? 0) === (int) $question['id'] ? 'open' : '' ?>>
              <summary>
                <div class="questionnaire-question-copy">
                  <?php if ($isWriteAllowed): ?>
                    <span class="questionnaire-drag-handle" title="Geser untuk ubah urutan">
                      <i class="bi bi-grip-vertical"></i>
                    </span>
                  <?php endif; ?>
                  <span class="questionnaire-question-code"><?= (int) ($question['display_order'] ?? 0) ?></span>
                  <strong class="questionnaire-question-title"><?= esc($question['question_text']) ?></strong>
                </div>
                <div class="questionnaire-question-meta">
                  <span class="questionnaire-builder-type-badge"><?= esc($answerTypeLabels[$question['answer_type']] ?? ucfirst((string) $question['answer_type'])) ?></span>
                  <?php if ((int) ($question['is_required'] ?? 0) === 1): ?>
                    <span class="badge text-bg-warning">Wajib</span>
                  <?php endif; ?>
                </div>
              </summary>

              <div class="pt-3 px-3 pb-3">
                <?php if (!empty($question['options'])): ?>
                  <div class="small text-muted mb-2">Pilihan: <?= esc(implode(' | ', $question['options'])) ?></div>
                <?php endif; ?>

                <?php if ($isWriteAllowed): ?>
                  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="small text-muted">
                      Posisi saat ini: <strong><?= (int) ($question['display_order'] ?? 0) ?></strong>
                    </div>
                  </div>

                  <div
                    class="questionnaire-question-form questionnaire-builder-form questionnaire-builder-inline"
                    x-data='questionnaireBuilder(<?= $builderState ?>)'>
                    <div class="questionnaire-builder-surface">
                      <div class="row g-3">
                        <div class="col-12 col-lg-4">
                          <label class="form-label">Bagian Form</label>
                          <select name="section_label" class="form-select" x-model="sectionLabel" form="questionEdit<?= (int) $question['id'] ?>">
                            <option value="">Bagian umum</option>
                            <?php foreach ($questionSectionOptions as $sectionOption): ?>
                              <option value="<?= esc($sectionOption) ?>" <?= $currentSection === $sectionOption ? 'selected' : '' ?>><?= esc($sectionOption) ?></option>
                            <?php endforeach; ?>
                            <option value="__new__">Buat bagian baru</option>
                          </select>
                          <input type="text" name="section_label_custom" class="form-control mt-2" x-cloak x-show="sectionLabel === '__new__'" placeholder="Contoh: Evaluasi Perilaku" form="questionEdit<?= (int) $question['id'] ?>">
                        </div>

                        <div class="col-12 col-lg-3">
                          <label class="form-label">Urutan Tampil</label>
                          <input type="number" name="sort_order" class="form-control" placeholder="Sekarang: <?= (int) ($question['display_order'] ?? 0) ?>" min="1" form="questionEdit<?= (int) $question['id'] ?>">
                        </div>

                        <div class="col-12 col-lg-5">
                          <label class="form-label">Bentuk Jawaban</label>
                          <select name="answer_type" class="form-select" x-model="answerType" required form="questionEdit<?= (int) $question['id'] ?>">
                            <?php foreach ($answerTypeLabels as $type => $label): ?>
                              <option value="<?= esc($type) ?>" <?= ($question['answer_type'] ?? 'radio') === $type ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div class="col-12">
                          <label class="form-label">Pertanyaan Utama</label>
                          <textarea name="question_text" rows="3" class="form-control questionnaire-builder-question" x-model="questionText" required form="questionEdit<?= (int) $question['id'] ?>"><?= esc($question['question_text']) ?></textarea>
                        </div>

                        <div class="col-12" x-cloak x-show="showChoiceOptions">
                          <label class="form-label">Daftar Pilihan Jawaban</label>
                          <textarea name="options_text" rows="4" class="form-control" x-model="optionsText" form="questionEdit<?= (int) $question['id'] ?>"><?= esc(implode("\n", $question['options'])) ?></textarea>
                        </div>

                        <div class="col-12" x-cloak x-show="showScaleLabels">
                          <div class="row g-3">
                            <div class="col-12 col-md-6">
                              <label class="form-label">Label Skala Kiri</label>
                              <input type="text" name="scale_low_label" class="form-control" x-model="scaleLowLabel" value="<?= esc($question['scale_low_label'] ?? '') ?>" placeholder="Contoh: Tidak puas" form="questionEdit<?= (int) $question['id'] ?>">
                            </div>
                            <div class="col-12 col-md-6">
                              <label class="form-label">Label Skala Kanan</label>
                              <input type="text" name="scale_high_label" class="form-control" x-model="scaleHighLabel" value="<?= esc($question['scale_high_label'] ?? '') ?>" placeholder="Contoh: Sangat puas" form="questionEdit<?= (int) $question['id'] ?>">
                            </div>
                          </div>
                        </div>

                        <div class="col-12 col-lg-6">
                          <label class="form-label">Petunjuk Singkat untuk Responden</label>
                          <input type="text" name="help_text" class="form-control" x-model="helpText" value="<?= esc($question['help_text'] ?? '') ?>" form="questionEdit<?= (int) $question['id'] ?>">
                        </div>

                        <div class="col-12 col-lg-6">
                          <label class="form-label">Teks Contoh di Kolom Jawaban</label>
                          <input type="text" name="placeholder" class="form-control" x-model="placeholder" value="<?= esc($question['placeholder'] ?? '') ?>" form="questionEdit<?= (int) $question['id'] ?>">
                        </div>

                        <div class="col-12">
                          <div class="form-check form-switch questionnaire-builder-switch">
                            <input class="form-check-input" type="checkbox" name="is_required" value="1" id="required<?= (int) $question['id'] ?>" x-model="required" <?= (int) ($question['is_required'] ?? 0) === 1 ? 'checked' : '' ?> form="questionEdit<?= (int) $question['id'] ?>">
                            <label class="form-check-label" for="required<?= (int) $question['id'] ?>">Pertanyaan ini wajib dijawab</label>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="questionnaire-builder-preview mt-3">
                      <div class="questionnaire-builder-preview-head">
                        <span class="questionnaire-link-label mb-0">Pratinjau Responden</span>
                        <span class="questionnaire-builder-type-badge" x-text="currentAnswerLabel"></span>
                      </div>
                      <div class="questionnaire-builder-preview-question">
                        <span x-text="previewQuestionText"></span><template x-if="required"><span class="text-danger"> *</span></template>
                      </div>
                      <div class="questionnaire-builder-preview-help text-muted small" x-cloak x-show="trimmedHelpText !== ''" x-text="trimmedHelpText"></div>
                      <div class="js-builder-preview-body" x-html="previewMarkup"></div>
                    </div>

                    <div class="col-12 d-flex flex-wrap justify-content-between gap-2 align-items-center mt-3">
                      <div class="small text-muted">
                        Posisi saat ini: <strong><?= (int) ($question['display_order'] ?? 0) ?></strong>
                      </div>
                      <div class="d-flex gap-2">
                        <form id="questionEdit<?= (int) $question['id'] ?>" method="post" action="<?= esc($relative('compliance/questionnaires/questions/update/' . $question['id'])) ?>" @submit.prevent="submitQuestionForm($event)"></form>
                        <button type="submit" class="btn btn-primary btn-sm" form="questionEdit<?= (int) $question['id'] ?>">Simpan Perubahan</button>
                        <form method="post" action="<?= esc($relative('compliance/questionnaires/questions/delete/' . $question['id'])) ?>" data-delete-title="Hapus pertanyaan ini?" data-delete-text="Pertanyaan akan dihapus permanen jika belum memiliki jawaban." @submit.prevent="confirmDelete($event, true)">
                          <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </details>
          <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
