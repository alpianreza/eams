<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  $relative = static function (string $uri): string {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  };
  $detailState = json_encode([
    'reorderUrl' => $isWriteAllowed ? $relative('compliance/questionnaires/' . $questionnaire['id'] . '/questions/reorder') : '',
    'respondentSettingsUrl' => $isWriteAllowed ? $relative('compliance/questionnaires/' . $questionnaire['id'] . '/respondent-settings') : '',
    'respondentFields' => $respondentFields,
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div class="questionnaire-page" x-data='questionnaireDetailPage(<?= $detailState ?>)' x-init="init()">
  <section class="card questionnaire-hero mb-3 no-lift">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="questionnaire-kicker mb-1">Detail Kuesioner</p>
        <h4 class="fw-bold mb-1"><?= esc($questionnaire['title']) ?></h4>
        <?php if (!empty($questionnaire['subtitle'])): ?>
          <p class="text-muted mb-1"><?= esc($questionnaire['subtitle']) ?></p>
        <?php endif; ?>
        <?php if (!empty($questionnaire['description'])): ?>
          <p class="text-muted mb-0"><?= esc($questionnaire['description']) ?></p>
        <?php endif; ?>
      </div>

      <div class="questionnaire-hero-meta col-12">
        <?php if ((int) ($questionnaire['active'] ?? 0) === 1): ?>
          <div class="questionnaire-hero-inline">
            <span class="questionnaire-link-label mb-0">Tautan publik</span>
            <code class="questionnaire-hero-code"><?= esc($publicPath) ?></code>
            <button type="button" class="btn btn-outline-primary btn-sm" @click="copyLink('<?= esc($publicPath) ?>')">
              <i class="bi bi-copy me-1"></i> Salin
            </button>
          </div>
        <?php endif; ?>

        <?php if ($isWriteAllowed): ?>
          <div class="questionnaire-hero-inline questionnaire-respondent-inline">
            <span class="questionnaire-link-label mb-0">Identitas responden</span>

            <label class="form-check form-switch questionnaire-inline-switch mb-0">
              <input class="form-check-input" type="checkbox" :checked="respondentFields.name" @change="saveRespondentSettings('name', $event.target.checked)">
              <span class="form-check-label">Nama</span>
            </label>

            <label class="form-check form-switch questionnaire-inline-switch mb-0">
              <input class="form-check-input" type="checkbox" :checked="respondentFields.phone" @change="saveRespondentSettings('phone', $event.target.checked)">
              <span class="form-check-label">No HP</span>
            </label>

            <label class="form-check form-switch questionnaire-inline-switch mb-0">
              <input class="form-check-input" type="checkbox" :checked="respondentFields.email" @change="saveRespondentSettings('email', $event.target.checked)">
              <span class="form-check-label">Email</span>
            </label>

            <span class="questionnaire-inline-status" x-cloak x-show="isSavingRespondentSettings">Menyimpan...</span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <div class="row g-3">
    <?php if ($isWriteAllowed): ?>
      <div class="col-12 col-xl-4">
        <div class="card no-lift questionnaire-form-card questionnaire-builder-card h-100">
          <div class="card-body">
            <div class="questionnaire-builder-head mb-3">
              <div class="questionnaire-link-label mb-1">Susun Form</div>
              <h5 class="fw-bold mb-1">Tambah Pertanyaan Baru</h5>
              <p class="text-muted small mb-0">Atur bentuk jawaban, isi pertanyaan, lalu lihat pratinjau tampilannya untuk responden.</p>
            </div>

            <form
              method="post"
              action="<?= esc($relative('compliance/questionnaires/' . $questionnaire['id'] . '/questions/store')) ?>"
              class="row g-3 questionnaire-question-form questionnaire-builder-form js-question-create-form"
              x-data="questionnaireBuilder({
                answerType: 'radio',
                questionText: '',
                optionsText: '',
                placeholder: '',
                helpText: '',
                required: true,
                scaleLowLabel: '',
                scaleHighLabel: ''
              })"
              x-on:question-builder-reset.window="if ($event.detail === 'create') resetBuilder()"
              @submit.prevent="submitQuestionForm($event)">
              <div class="col-12">
                <div class="questionnaire-builder-surface">
                  <div class="row g-3">
                    <div class="col-12 col-md-7">
                      <label class="form-label">Bagian Form</label>
                      <select name="section_label" class="form-select" x-model="sectionLabel">
                        <option value="">Bagian umum</option>
                        <?php foreach ($sectionOptions as $sectionOption): ?>
                          <option value="<?= esc($sectionOption) ?>"><?= esc($sectionOption) ?></option>
                        <?php endforeach; ?>
                        <option value="__new__">Buat bagian baru</option>
                      </select>
                      <input type="text" name="section_label_custom" class="form-control mt-2" x-cloak x-show="sectionLabel === '__new__'" placeholder="Contoh: Data Responden atau Evaluasi Perilaku">
                    </div>

                    <div class="col-12 col-md-5">
                      <label class="form-label">Urutan Tampil</label>
                      <input type="number" name="sort_order" class="form-control" placeholder="Kosongkan untuk otomatis" min="1">
                    </div>

                    <div class="col-12">
                      <label class="form-label">Bentuk Jawaban</label>
                      <select name="answer_type" class="form-select" x-model="answerType" required>
                        <?php foreach ($answerTypeLabels as $type => $label): ?>
                          <option value="<?= esc($type) ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-12">
                      <label class="form-label">Pertanyaan Utama</label>
                      <textarea name="question_text" rows="3" class="form-control questionnaire-builder-question" x-model="questionText" placeholder="Tulis pertanyaan seperti yang akan dibaca responden" required></textarea>
                    </div>

                    <div class="col-12" x-cloak x-show="showChoiceOptions">
                      <label class="form-label">Daftar Pilihan Jawaban</label>
                      <textarea name="options_text" rows="5" class="form-control" x-model="optionsText" placeholder="Satu pilihan per baris"></textarea>
                      <div class="small text-muted mt-2">Cocok untuk pilihan satu jawaban atau daftar pilihan.</div>
                    </div>

                    <div class="col-12" x-cloak x-show="showScaleLabels">
                      <div class="row g-3">
                        <div class="col-12 col-md-6">
                          <label class="form-label">Label Skala Kiri</label>
                          <input type="text" name="scale_low_label" class="form-control" x-model="scaleLowLabel" placeholder="Contoh: Tidak puas">
                        </div>
                        <div class="col-12 col-md-6">
                          <label class="form-label">Label Skala Kanan</label>
                          <input type="text" name="scale_high_label" class="form-control" x-model="scaleHighLabel" placeholder="Contoh: Sangat puas">
                        </div>
                      </div>
                    </div>

                    <div class="col-12">
                      <label class="form-label">Petunjuk Singkat untuk Responden</label>
                      <textarea name="help_text" rows="2" class="form-control" x-model="helpText" placeholder="Contoh: Pilih jawaban yang paling sesuai dengan kondisi Anda"></textarea>
                    </div>

                    <div class="col-12">
                      <label class="form-label">Teks Contoh di Kolom Jawaban</label>
                      <input type="text" name="placeholder" class="form-control" x-model="placeholder" placeholder="Opsional, misalnya: Tulis jawaban Anda di sini">
                    </div>

                    <div class="col-12">
                      <div class="form-check form-switch questionnaire-builder-switch">
                        <input class="form-check-input" type="checkbox" name="is_required" id="isRequiredAdd" value="1" x-model="required" checked>
                        <label class="form-check-label" for="isRequiredAdd">Pertanyaan ini wajib dijawab</label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="questionnaire-builder-preview">
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
              </div>

              <div class="col-12 d-grid">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-plus-circle me-1"></i> Simpan Pertanyaan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="col-12 <?= $isWriteAllowed ? 'col-xl-8' : '' ?>">
      <div id="questionnaireQuestionList" data-question-list-root>
        <?= view('compliance/questionnaire/_question_list', [
          'questions' => $questions,
          'questionGroups' => $questionGroups,
          'sectionOptions' => $sectionOptions,
          'answerTypeLabels' => $answerTypeLabels,
          'isWriteAllowed' => $isWriteAllowed,
          'openQuestionId' => $openQuestionId ?? null,
        ]) ?>
      </div>

      <div class="card no-lift">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Hasil Pengisian</h5>
            <span class="badge text-bg-secondary"><?= count($responses) ?> respon</span>
          </div>

          <?php if (empty($responses)): ?>
            <div class="text-muted">Belum ada hasil pengisian untuk kuesioner ini.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle questionnaire-table mb-0">
                <thead>
                  <tr>
                    <th>Kode</th>
                    <th x-show="respondentFields.name" x-cloak>Nama</th>
                    <th x-show="respondentFields.phone" x-cloak>Telepon</th>
                    <th x-show="respondentFields.email" x-cloak>Email</th>
                    <th>Dikirim</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($responses as $response): ?>
                    <tr>
                      <td><span class="badge text-bg-light"><?= esc($response['response_code']) ?></span></td>
                      <td x-show="respondentFields.name" x-cloak><?= esc($response['respondent_name']) ?></td>
                      <td x-show="respondentFields.phone" x-cloak><?= esc($response['phone'] ?: '-') ?></td>
                      <td x-show="respondentFields.email" x-cloak><?= esc($response['email'] ?: '-') ?></td>
                      <td><?= esc($response['submitted_at'] ?: '-') ?></td>
                      <td class="text-end">
                        <a href="<?= esc($response['detail_path']) ?>" class="btn btn-outline-primary btn-sm">Detail</a>
                        <a href="<?= esc($response['pdf_path']) ?>" class="btn btn-outline-success btn-sm" target="_blank">PDF</a>
                        <?php if ($isWriteAllowed): ?>
                          <form method="post" action="<?= esc($relative('compliance/questionnaires/response/delete/' . $response['id'])) ?>" class="d-inline" data-delete-title="Hapus respon ini?" data-delete-text="Jawaban responden akan dihapus permanen." @submit.prevent="confirmDelete($event)">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= esc($relative('assets/css/questionnaire.css') . '?v=' . filemtime(FCPATH . 'assets/css/questionnaire.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('alpine:init', function() {
    const answerLabels = <?= json_encode($answerTypeLabels, JSON_UNESCAPED_UNICODE) ?>;

    function escapeHtml(text) {
      return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    Alpine.data('questionnaireBuilder', function(initial) {
      return {
        answerType: initial.answerType || 'radio',
        questionText: initial.questionText || '',
        optionsText: initial.optionsText || '',
        placeholder: initial.placeholder || '',
        helpText: initial.helpText || '',
        required: !!initial.required,
        scaleLowLabel: initial.scaleLowLabel || '',
        scaleHighLabel: initial.scaleHighLabel || '',
        sectionLabel: initial.sectionLabel || '',

        get showChoiceOptions() {
          return ['radio', 'select'].includes(this.answerType);
        },

        get showScaleLabels() {
          return ['scale_5', 'scale_10'].includes(this.answerType);
        },

        get currentAnswerLabel() {
          return answerLabels[this.answerType] || this.answerType;
        },

        get trimmedHelpText() {
          return String(this.helpText || '').trim();
        },

        get previewQuestionText() {
          const text = String(this.questionText || '').trim();
          return text !== '' ? text : 'Pertanyaan akan tampil di sini';
        },

        get normalizedOptions() {
          const parsed = String(this.optionsText || '')
            .split(/\r\n|\r|\n/)
            .map(function(item) {
              return item.trim();
            })
            .filter(function(item) {
              return item !== '';
            });

          if (this.answerType === 'scale_5' && parsed.length === 0) {
            return ['1', '2', '3', '4', '5'];
          }

          if (this.answerType === 'scale_10' && parsed.length === 0) {
            return ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
          }

          if (parsed.length === 0) {
            return ['Pilihan 1', 'Pilihan 2'];
          }

          return parsed;
        },

        get previewMarkup() {
          const safePlaceholder = escapeHtml(this.placeholder || 'Jawaban akan ditulis di sini');

          if (this.answerType === 'radio') {
            return '<div class="questionnaire-builder-preview-options">' +
              this.normalizedOptions.slice(0, 4).map(function(option) {
                return '<div class="questionnaire-builder-preview-option"><span class="questionnaire-builder-preview-control is-radio"></span><span>' + escapeHtml(option) + '</span></div>';
              }).join('') +
            '</div>';
          }

          if (this.answerType === 'select') {
            return '<div class="questionnaire-builder-preview-field is-select">' + safePlaceholder + '</div>' +
              '<div class="questionnaire-builder-preview-note">Responden akan memilih satu jawaban dari daftar.</div>';
          }

          if (this.answerType === 'scale_5' || this.answerType === 'scale_10') {
            return '<div class="questionnaire-builder-scale-preview" style="--builder-scale-count:' + this.normalizedOptions.length + ';">' +
              '<div class="questionnaire-builder-scale-numbers">' +
                this.normalizedOptions.map(function(option) {
                  return '<span>' + escapeHtml(option) + '</span>';
                }).join('') +
              '</div>' +
              '<div class="questionnaire-builder-scale-layout">' +
                '<span class="questionnaire-builder-scale-edge">' + escapeHtml(this.scaleLowLabel || 'Label kiri') + '</span>' +
                '<div class="questionnaire-builder-scale-grid">' +
                  this.normalizedOptions.map(function() {
                    return '<label class="questionnaire-builder-scale-option"><span class="questionnaire-builder-preview-control is-radio"></span></label>';
                  }).join('') +
                '</div>' +
                '<span class="questionnaire-builder-scale-edge is-right">' + escapeHtml(this.scaleHighLabel || 'Label kanan') + '</span>' +
              '</div>' +
            '</div>';
          }

          if (this.answerType === 'textarea') {
            return '<div class="questionnaire-builder-preview-field is-textarea">' + safePlaceholder + '</div>';
          }

          if (this.answerType === 'date') {
            return '<div class="questionnaire-builder-preview-field">Pilih tanggal</div>';
          }

          if (this.answerType === 'email') {
            return '<div class="questionnaire-builder-preview-field">' + escapeHtml(this.placeholder || 'nama@email.com') + '</div>';
          }

          if (this.answerType === 'phone') {
            return '<div class="questionnaire-builder-preview-field">' + escapeHtml(this.placeholder || '08xxxxxxxxxx') + '</div>';
          }

          if (this.answerType === 'number') {
            return '<div class="questionnaire-builder-preview-field">' + escapeHtml(this.placeholder || 'Tulis angka') + '</div>';
          }

          return '<div class="questionnaire-builder-preview-field">' + safePlaceholder + '</div>';
        },

        resetBuilder() {
          this.answerType = 'radio';
          this.questionText = '';
          this.optionsText = '';
          this.placeholder = '';
          this.helpText = '';
          this.required = true;
          this.scaleLowLabel = '';
          this.scaleHighLabel = '';
          this.sectionLabel = '';
        }
      };
    });

    Alpine.data('questionnaireDetailPage', function(config) {
      return {
        reorderUrl: config.reorderUrl || '',
        respondentSettingsUrl: config.respondentSettingsUrl || '',
        respondentFields: {
          name: !!(config.respondentFields && config.respondentFields.name),
          phone: !!(config.respondentFields && config.respondentFields.phone),
          email: !!(config.respondentFields && config.respondentFields.email)
        },
        activeDragId: null,
        isReordering: false,
        isSavingRespondentSettings: false,

        init() {
          const root = this.getQuestionListRoot();
          if (!root) {
            return;
          }

          root.addEventListener('dragstart', this.handleDragStart.bind(this));
          root.addEventListener('dragover', this.handleDragOver.bind(this));
          root.addEventListener('drop', this.handleDrop.bind(this));
          root.addEventListener('dragend', this.handleDragEnd.bind(this));
        },

        getQuestionListRoot() {
          return document.querySelector('[data-question-list-root]');
        },

        async copyLink(path) {
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
        },

        async saveRespondentSettings(field, checked) {
          if (!this.respondentSettingsUrl) {
            return;
          }

          const previousState = { ...this.respondentFields };
          this.respondentFields[field] = checked;
          this.isSavingRespondentSettings = true;

          try {
            const formData = new FormData();
            if (this.respondentFields.name) {
              formData.append('collect_name', '1');
            }
            if (this.respondentFields.phone) {
              formData.append('collect_phone', '1');
            }
            if (this.respondentFields.email) {
              formData.append('collect_email', '1');
            }

            const response = await fetch(this.respondentSettingsUrl, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              },
              body: formData
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
              throw new Error(result.message || 'Pengaturan identitas belum berhasil diperbarui.');
            }

            if (result.respondentFields) {
              this.respondentFields = {
                name: !!result.respondentFields.name,
                phone: !!result.respondentFields.phone,
                email: !!result.respondentFields.email
              };
            }
          } catch (error) {
            this.respondentFields = previousState;
            safeToast(error.message || 'Pengaturan identitas belum berhasil diperbarui.', 'error');
          } finally {
            this.isSavingRespondentSettings = false;
          }
        },

        syncCreateSectionOptions(sectionOptions) {
          const form = document.querySelector('.js-question-create-form');
          if (!form) {
            return;
          }

          const select = form.querySelector('select[name="section_label"]');
          if (!select) {
            return;
          }

          const currentValue = select.value;
          const optionsHtml = ['<option value="">Bagian umum</option>']
            .concat((sectionOptions || []).map(function(option) {
              return '<option value="' + escapeHtml(option) + '">' + escapeHtml(option) + '</option>';
            }))
            .concat(['<option value="__new__">Buat bagian baru</option>'])
            .join('');

          select.innerHTML = optionsHtml;
          if ((sectionOptions || []).includes(currentValue) || currentValue === '__new__' || currentValue === '') {
            select.value = currentValue;
          } else {
            select.value = '';
          }
        },

        collectQuestionOrder() {
          return Array.from(document.querySelectorAll('[data-question-list-root] [data-question-id]'))
            .map(function(item) {
              return Number(item.getAttribute('data-question-id') || 0);
            })
            .filter(function(id) {
              return id > 0;
            });
        },

        getDragAfterElement(container, clientY) {
          const draggableElements = Array.from(container.querySelectorAll('[data-question-id]:not(.is-dragging)'));

          return draggableElements.reduce(function(closest, child) {
            const box = child.getBoundingClientRect();
            const offset = clientY - box.top - (box.height / 2);

            if (offset < 0 && offset > closest.offset) {
              return { offset: offset, element: child };
            }

            return closest;
          }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
        },

        handleDragStart(event) {
          const item = event.target.closest('[data-question-id]');
          if (!item || !item.classList.contains('is-sortable') || !this.reorderUrl) {
            return;
          }

          this.activeDragId = Number(item.getAttribute('data-question-id') || 0);
          item.classList.add('is-dragging');

          if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(this.activeDragId));
          }
        },

        handleDragOver(event) {
          const container = event.target.closest('[data-question-sort-list]');
          const dragging = document.querySelector('[data-question-id].is-dragging');

          if (!container || !dragging || !container.contains(dragging)) {
            return;
          }

          event.preventDefault();
          const afterElement = this.getDragAfterElement(container, event.clientY);

          if (!afterElement) {
            container.appendChild(dragging);
            return;
          }

          if (afterElement !== dragging) {
            container.insertBefore(dragging, afterElement);
          }
        },

        async persistQuestionOrder() {
          if (!this.reorderUrl || this.isReordering) {
            return;
          }

          const order = this.collectQuestionOrder();
          if (order.length === 0) {
            return;
          }

          this.isReordering = true;

          try {
            const formData = new FormData();
            order.forEach(function(questionId) {
              formData.append('order[]', String(questionId));
            });

            const response = await fetch(this.reorderUrl, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              },
              body: formData
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
              throw new Error(result.message || 'Urutan pertanyaan belum berhasil diperbarui.');
            }

            const questionListRoot = this.getQuestionListRoot();
            if (questionListRoot && typeof result.html === 'string') {
              questionListRoot.innerHTML = result.html;
            }

            this.syncCreateSectionOptions(result.sectionOptions || []);
          } catch (error) {
            safeToast(error.message || 'Urutan pertanyaan belum berhasil diperbarui.', 'error');
          } finally {
            this.isReordering = false;
            this.activeDragId = null;
          }
        },

        async handleDrop(event) {
          const container = event.target.closest('[data-question-sort-list]');
          const dragging = document.querySelector('[data-question-id].is-dragging');

          if (!container || !dragging || !container.contains(dragging)) {
            return;
          }

          event.preventDefault();
          await this.persistQuestionOrder();
        },

        handleDragEnd(event) {
          const item = event.target.closest('[data-question-id]');
          if (!item) {
            return;
          }

          item.classList.remove('is-dragging');
        },

        async submitQuestionForm(event) {
          const form = event.currentTarget || event.target;
          if (!form || typeof form.querySelector !== 'function') {
            safeToast('Form aksi belum siap diproses. Coba ulangi sekali lagi.', 'warning');
            return;
          }

          const submitButton = form.querySelector('[type="submit"]');
          const originalText = submitButton ? submitButton.innerHTML : '';

          try {
            if (submitButton) {
              submitButton.disabled = true;
              submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
            }

            const response = await fetch(form.action, {
              method: (form.method || 'post').toUpperCase(),
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              },
              body: new FormData(form)
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
              throw new Error(result.message || 'Perubahan belum berhasil diproses.');
            }

            const questionListRoot = this.getQuestionListRoot();
            if (questionListRoot && typeof result.html === 'string') {
              questionListRoot.innerHTML = result.html;
            }

            this.syncCreateSectionOptions(result.sectionOptions || []);

            if (form.classList.contains('js-question-create-form')) {
              window.dispatchEvent(new CustomEvent('question-builder-reset', { detail: 'create' }));
              form.reset();
            }

            if (result.openQuestionId) {
              this.$nextTick(function() {
                const target = document.querySelector('[data-question-id="' + result.openQuestionId + '"]');
                if (target) {
                  target.open = true;
                  target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
              });
            }

            safeToast(result.message || 'Perubahan berhasil disimpan.', 'success');
          } catch (error) {
            safeToast(error.message || 'Perubahan belum berhasil diproses.', 'error');
          } finally {
            if (submitButton) {
              submitButton.disabled = false;
              submitButton.innerHTML = originalText;
            }
          }
        },

        confirmDelete(event, useAjax = false) {
          const form = event.currentTarget || event.target;
          if (!form) {
            return;
          }

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
            if (!result.isConfirmed) {
              return;
            }

            if (useAjax) {
              this.submitQuestionForm({ target: form });
              return;
            }

            form.submit();
          }.bind(this));
        }
      };
    });
  });
</script>
<?= $this->endSection() ?>
