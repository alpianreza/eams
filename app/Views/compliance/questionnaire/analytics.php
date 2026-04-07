<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  $relative = static function (string $uri): string {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  };
  $analyticsPath = $relative('compliance/questionnaires/analytics');
  $analyticsState = json_encode([
    'fetchPath' => $analyticsPath,
    'selectedQuestionnaireId' => (int) $selectedQuestionnaireId,
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div
  class="questionnaire-page questionnaire-analytics-page"
  x-data='questionnaireAnalyticsPage(<?= $analyticsState ?>)'
  @questionnaire-select-template.window="pickTemplate($event.detail.id)">
  <section class="card questionnaire-hero mb-3 no-lift">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="questionnaire-kicker mb-1">Compliance</p>
        <h4 class="fw-bold mb-1">Pusat Analitik Kuesioner</h4>
        <p class="text-muted mb-0">Lihat performa form, respon terbaru, dan pola jawaban per pertanyaan dalam satu layar.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= esc($relative('compliance/questionnaires')) ?>" class="btn btn-outline-primary">
          <i class="bi bi-layout-text-window-reverse me-1"></i> Kembali ke Pusat Kuesioner
        </a>
      </div>
    </div>
  </section>

  <div class="row g-3 mb-3">
    <div class="col-6 col-lg-2">
      <div class="card questionnaire-card no-lift questionnaire-analytics-stat h-100">
        <div class="card-body">
          <div class="questionnaire-card-kicker">Template</div>
          <div class="questionnaire-analytics-stat-value"><?= (int) ($overviewStats['total_templates'] ?? 0) ?></div>
          <div class="questionnaire-analytics-stat-note">Total form aktif dan arsip</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="card questionnaire-card no-lift questionnaire-analytics-stat h-100">
        <div class="card-body">
          <div class="questionnaire-card-kicker">Aktif</div>
          <div class="questionnaire-analytics-stat-value"><?= (int) ($overviewStats['active_templates'] ?? 0) ?></div>
          <div class="questionnaire-analytics-stat-note">Form yang bisa diisi</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="card questionnaire-card no-lift questionnaire-analytics-stat h-100">
        <div class="card-body">
          <div class="questionnaire-card-kicker">Respon</div>
          <div class="questionnaire-analytics-stat-value"><?= (int) ($overviewStats['total_responses'] ?? 0) ?></div>
          <div class="questionnaire-analytics-stat-note">Total data masuk</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="card questionnaire-card no-lift questionnaire-analytics-stat h-100">
        <div class="card-body">
          <div class="questionnaire-card-kicker">Hari Ini</div>
          <div class="questionnaire-analytics-stat-value"><?= (int) ($overviewStats['responses_today'] ?? 0) ?></div>
          <div class="questionnaire-analytics-stat-note">Respon masuk hari ini</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="card questionnaire-card no-lift questionnaire-analytics-stat h-100">
        <div class="card-body">
          <div class="questionnaire-card-kicker">7 Hari</div>
          <div class="questionnaire-analytics-stat-value"><?= (int) ($overviewStats['responses_week'] ?? 0) ?></div>
          <div class="questionnaire-analytics-stat-note">Aktivitas sepekan</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-2">
      <div class="card questionnaire-card no-lift questionnaire-analytics-stat h-100">
        <div class="card-body">
          <div class="questionnaire-card-kicker">Pertanyaan</div>
          <div class="questionnaire-analytics-stat-value"><?= (int) ($overviewStats['total_questions'] ?? 0) ?></div>
          <div class="questionnaire-analytics-stat-note">Total seluruh pertanyaan</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card no-lift questionnaire-form-card mb-3">
    <div class="card-body">
      <form
        method="get"
        action="<?= esc($analyticsPath) ?>"
        class="row g-3 align-items-end"
        @submit.prevent="fetchAnalytics()">
        <div class="col-12 col-lg-6">
          <label class="form-label">Pilih Kuesioner untuk Dianalisis</label>
          <select name="questionnaire_id" class="form-select" x-model="selectedQuestionnaireId" @change="fetchAnalytics()">
            <?php foreach ($templates as $template): ?>
              <option value="<?= (int) $template['id'] ?>" <?= (int) $selectedQuestionnaireId === (int) $template['id'] ? 'selected' : '' ?>>
                <?= esc($template['title']) ?> (<?= (int) ($template['response_count'] ?? 0) ?> respon)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-lg-6">
          <div class="questionnaire-analytics-filter-note" :class="{ 'is-loading': loading }">
            <template x-if="!loading">
              <span>Fokus analisis bisa diganti kapan saja. Data respon terbaru di bawah akan ikut menyesuaikan dengan kuesioner yang dipilih.</span>
            </template>
            <template x-if="loading">
              <span>Sedang memuat analitik terbaru untuk form yang dipilih...</span>
            </template>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="questionnaire-analytics-content-wrap">
    <div class="questionnaire-analytics-loading" x-cloak x-show="loading">
      <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
      <span>Memuat analitik...</span>
    </div>
    <div x-ref="analyticsContent" :class="{ 'is-loading': loading }">
      <?= view('compliance/questionnaire/_analytics_content', [
        'templates' => $templates,
        'selectedQuestionnaireId' => $selectedQuestionnaireId,
        'selectedQuestionnaire' => $selectedQuestionnaire,
        'submissionTrend' => $submissionTrend,
        'recentResponses' => $recentResponses,
        'questionAnalyses' => $questionAnalyses,
      ]) ?>
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
    Alpine.data('questionnaireAnalyticsPage', function(config) {
      return {
        fetchPath: config.fetchPath || window.location.pathname,
        selectedQuestionnaireId: String(config.selectedQuestionnaireId || ''),
        loading: false,

        async fetchAnalytics() {
          if (!this.selectedQuestionnaireId) {
            return;
          }

          const query = new URLSearchParams({
            questionnaire_id: this.selectedQuestionnaireId
          });

          const url = this.fetchPath + '?' + query.toString();
          this.loading = true;

          try {
            const response = await fetch(url, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
              throw new Error(result.message || 'Analitik belum berhasil dimuat.');
            }

            if (this.$refs.analyticsContent && typeof result.html === 'string') {
              this.$refs.analyticsContent.innerHTML = result.html;
            }

            if (result.selectedQuestionnaireId) {
              this.selectedQuestionnaireId = String(result.selectedQuestionnaireId);
            }

            window.history.replaceState({}, '', url);
          } catch (error) {
            safeToast(error.message || 'Analitik belum berhasil dimuat.', 'error');
          } finally {
            this.loading = false;
          }
        },

        pickTemplate(questionnaireId) {
          this.selectedQuestionnaireId = String(questionnaireId || '');
          this.fetchAnalytics();
        }
      };
    });
  });
</script>
<?= $this->endSection() ?>
