<?php
  $relative = static function (string $uri): string {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  };
?>

<div class="row g-3 mb-3">
  <div class="col-12 col-xl-5">
    <div class="card questionnaire-card no-lift h-100">
      <div class="card-body">
        <div class="questionnaire-link-label mb-1">Ringkasan Kuesioner</div>
        <?php if ($selectedQuestionnaire): ?>
          <h5 class="fw-bold mb-1"><?= esc($selectedQuestionnaire['title']) ?></h5>
          <?php if (!empty($selectedQuestionnaire['subtitle'])): ?>
            <p class="text-muted mb-2"><?= esc($selectedQuestionnaire['subtitle']) ?></p>
          <?php endif; ?>
          <div class="questionnaire-analytics-summary-grid">
            <div>
              <span class="questionnaire-stat-label">Status</span>
              <strong><?= (int) ($selectedQuestionnaire['active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?></strong>
            </div>
            <div>
              <span class="questionnaire-stat-label">Pertanyaan</span>
              <strong><?= (int) ($selectedQuestionnaire['question_count'] ?? 0) ?></strong>
            </div>
            <div>
              <span class="questionnaire-stat-label">Total Respon</span>
              <strong><?= (int) ($selectedQuestionnaire['response_count'] ?? 0) ?></strong>
            </div>
            <div>
              <span class="questionnaire-stat-label">Respon Terakhir</span>
              <strong><?= esc($selectedQuestionnaire['latest_submitted'] ?: '-') ?></strong>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="<?= esc($relative('compliance/questionnaires/' . $selectedQuestionnaire['id'])) ?>" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-layout-text-window-reverse me-1"></i> Buka Detail
            </a>
            <?php if ((int) ($selectedQuestionnaire['active'] ?? 0) === 1): ?>
              <a href="<?= esc($selectedQuestionnaire['public_path']) ?>" class="btn btn-outline-success btn-sm" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right me-1"></i> Buka Form
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="text-muted">Belum ada kuesioner yang bisa dianalisis.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-7">
    <div class="card questionnaire-card no-lift h-100">
      <div class="card-body">
        <div class="questionnaire-link-label mb-1">Tren Pengisian 7 Hari</div>
        <h5 class="fw-bold mb-3">Aktivitas Respon</h5>

        <?php if (empty($submissionTrend)): ?>
          <div class="text-muted">Belum ada aktivitas respon.</div>
        <?php else: ?>
          <div class="questionnaire-analytics-trend">
            <?php foreach ($submissionTrend as $trend): ?>
              <div class="questionnaire-analytics-trend-row">
                <span class="questionnaire-analytics-trend-label"><?= esc($trend['label']) ?></span>
                <div class="questionnaire-analytics-trend-bar">
                  <span style="width: <?= (int) ($trend['width'] ?? 0) ?>%;"></span>
                </div>
                <span class="questionnaire-analytics-trend-count"><?= (int) ($trend['count'] ?? 0) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card questionnaire-card no-lift mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <div class="questionnaire-link-label mb-1">Daftar Form</div>
        <h5 class="fw-bold mb-0">Semua Template Kuesioner</h5>
      </div>
      <span class="badge text-bg-primary"><?= count($templates) ?> form</span>
    </div>

    <div class="row g-3">
      <?php foreach ($templates as $template): ?>
        <div class="col-12 col-lg-6 col-xl-4">
          <a
            href="<?= esc($relative('compliance/questionnaires/analytics') . '?questionnaire_id=' . (int) $template['id']) ?>"
            class="questionnaire-analytics-template <?= (int) $selectedQuestionnaireId === (int) $template['id'] ? 'is-active' : '' ?>"
            @click.prevent="$dispatch('questionnaire-select-template', { id: <?= (int) $template['id'] ?> })">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <strong><?= esc($template['title']) ?></strong>
              <span class="badge <?= (int) ($template['active'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                <?= (int) ($template['active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </div>
            <div class="questionnaire-analytics-template-meta">
              <span><?= (int) ($template['question_count'] ?? 0) ?> pertanyaan</span>
              <span><?= (int) ($template['response_count'] ?? 0) ?> respon</span>
            </div>
            <div class="small text-muted">Respon terakhir: <?= esc($template['latest_submitted'] ?: '-') ?></div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card questionnaire-card no-lift mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <div class="questionnaire-link-label mb-1">Analisis Pertanyaan</div>
        <h5 class="fw-bold mb-0">Breakdown Jawaban</h5>
      </div>
      <?php if ($selectedQuestionnaire): ?>
        <span class="badge text-bg-light"><?= esc($selectedQuestionnaire['title']) ?></span>
      <?php endif; ?>
    </div>

    <?php if (empty($questionAnalyses)): ?>
      <div class="text-muted">Belum ada data jawaban untuk dianalisis pada kuesioner ini.</div>
    <?php else: ?>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($questionAnalyses as $analysis): ?>
          <div class="questionnaire-analytics-question">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
              <div>
                <div class="questionnaire-analytics-question-title">
                  <?= (int) ($analysis['display_order'] ?? 0) ?>. <?= esc($analysis['question_text'] ?? '') ?>
                </div>
                <?php if (!empty($analysis['help_text'])): ?>
                  <div class="text-muted small mt-1"><?= esc($analysis['help_text']) ?></div>
                <?php endif; ?>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <span class="badge text-bg-light"><?= esc($analysis['answer_type']) ?></span>
                <span class="badge text-bg-primary"><?= (int) ($analysis['response_count'] ?? 0) ?> jawaban</span>
                <?php if (isset($analysis['average_score']) && $analysis['average_score'] !== null): ?>
                  <span class="badge text-bg-success">Rata-rata <?= esc((string) $analysis['average_score']) ?></span>
                <?php endif; ?>
              </div>
            </div>

            <?php if (!empty($analysis['distribution'])): ?>
              <?php if (!empty($analysis['scale_low_label']) || !empty($analysis['scale_high_label'])): ?>
                <div class="questionnaire-analytics-scale-caption">
                  <span><?= esc($analysis['scale_low_label'] ?: '-') ?></span>
                  <span><?= esc($analysis['scale_high_label'] ?: '-') ?></span>
                </div>
              <?php endif; ?>

              <div class="questionnaire-analytics-bars">
                <?php foreach ($analysis['distribution'] as $item): ?>
                  <div class="questionnaire-analytics-bar-row">
                    <span class="questionnaire-analytics-bar-label"><?= esc($item['label']) ?></span>
                    <div class="questionnaire-analytics-bar-track">
                      <span style="width: <?= (int) ($item['width'] ?? 0) ?>%;"></span>
                    </div>
                    <span class="questionnaire-analytics-bar-value"><?= (int) ($item['count'] ?? 0) ?> / <?= esc((string) ($item['percent'] ?? 0)) ?>%</span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php elseif (!empty($analysis['number_stats'])): ?>
              <div class="questionnaire-analytics-number-stats">
                <div>
                  <span class="questionnaire-stat-label">Rata-rata</span>
                  <strong><?= esc((string) ($analysis['number_stats']['avg'] ?? '-')) ?></strong>
                </div>
                <div>
                  <span class="questionnaire-stat-label">Minimum</span>
                  <strong><?= esc((string) ($analysis['number_stats']['min'] ?? '-')) ?></strong>
                </div>
                <div>
                  <span class="questionnaire-stat-label">Maksimum</span>
                  <strong><?= esc((string) ($analysis['number_stats']['max'] ?? '-')) ?></strong>
                </div>
              </div>
              <?php if (!empty($analysis['sample_answers'])): ?>
                <div class="questionnaire-analytics-answer-list mt-3">
                  <?php foreach ($analysis['sample_answers'] as $sample): ?>
                    <div class="questionnaire-analytics-answer-item">
                      <strong><?= esc($sample['respondent_name']) ?></strong>
                      <span><?= esc($sample['value']) ?></span>
                      <small><?= esc($sample['submitted_at']) ?></small>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            <?php elseif (!empty($analysis['sample_answers'])): ?>
              <div class="questionnaire-analytics-answer-list">
                <?php foreach ($analysis['sample_answers'] as $sample): ?>
                  <div class="questionnaire-analytics-answer-item">
                    <strong><?= esc($sample['respondent_name']) ?></strong>
                    <span><?= esc(function_exists('mb_strimwidth') ? mb_strimwidth($sample['value'], 0, 170, '...') : substr($sample['value'], 0, 170)) ?></span>
                    <small><?= esc($sample['submitted_at']) ?></small>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-muted small">Belum ada jawaban masuk untuk pertanyaan ini.</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card questionnaire-card no-lift">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <div class="questionnaire-link-label mb-1">Respon Terbaru</div>
        <h5 class="fw-bold mb-0">Data Masuk Terakhir</h5>
      </div>
      <span class="badge text-bg-secondary"><?= count($recentResponses) ?> baris</span>
    </div>

    <?php if (empty($recentResponses)): ?>
      <div class="text-muted">Belum ada respon terbaru untuk ditampilkan.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle questionnaire-table mb-0">
          <thead>
            <tr>
              <th>Kuesioner</th>
              <th>Kode</th>
              <th>Nama</th>
              <th>Kontak</th>
              <th>Dikirim</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentResponses as $response): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= esc($response['questionnaire_title'] ?: '-') ?></div>
                </td>
                <td><span class="badge text-bg-light"><?= esc($response['response_code']) ?></span></td>
                <td><?= esc($response['respondent_name'] ?: 'Anonim') ?></td>
                <td>
                  <div><?= esc($response['phone'] ?: '-') ?></div>
                  <div class="small text-muted"><?= esc($response['email'] ?: '-') ?></div>
                </td>
                <td><?= esc($response['submitted_at'] ?: '-') ?></td>
                <td class="text-end">
                  <a href="<?= esc($response['detail_path']) ?>" class="btn btn-outline-primary btn-sm">Detail</a>
                  <a href="<?= esc($response['pdf_path']) ?>" class="btn btn-outline-success btn-sm" target="_blank">PDF</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
