<?= $this->extend('layouts/main') ?>

<?php
$years = $boot['years'] ?? [];
$selectedYear = $boot['selectedYear'] ?? (int) date('Y');
$months = $boot['months'] ?? [];
$sections = $boot['sections'] ?? [];
?>

<?= $this->section('content') ?>
<div class="ems-page combustion-report-page" x-data="<?= $alpineComponent ?>(window.<?= $bootVar ?> || {})" x-init="init()">
  <section class="card border-0 shadow-sm ems-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="ems-kicker mb-1">EMS Report</p>
        <h5 class="fw-bold mb-1"><?= esc($boot['reportTitle'] ?? 'Combustion Report') ?></h5>
        <p class="text-muted mb-0"><?= esc($boot['reportSubtitle'] ?? 'Input konsumsi bulanan dan emisi otomatis.') ?></p>
      </div>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="ems-save-chip" :class="saveStateClass" x-cloak>
          <i class="bi" :class="saveStateIcon"></i>
          <span x-text="saveStateLabel"></span>
        </div>
        <a href="/ems-reports" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Kembali ke EMS
        </a>
      </div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($years as $year): ?>
          <button type="button" class="btn btn-sm rounded-pill px-3" :class="selectedYear === <?= (int) $year ?> ? 'btn-primary' : 'btn-outline-primary'" @click="selectYear(<?= (int) $year ?>)">
            <?= (int) $year ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <div class="row g-3">
    <div class="col-xl-4">
      <section class="card border-0 shadow-sm no-lift h-100">
        <div class="card-header bg-transparent border-0 pb-0">
          <p class="ems-section-kicker mb-1">Input Bulanan</p>
          <h6 class="fw-semibold mb-1">Form Tahun <span x-text="selectedYear"></span></h6>
        </div>
        <div class="card-body pt-2">
          <div class="mb-3">
            <label for="combustion_production_output" class="form-label form-label-sm">Production Output</label>
            <input id="combustion_production_output" type="number" step="0.01" min="0" class="form-control" x-model="editor.productionOutput" @input="scheduleAutosave()" placeholder="0.00">
          </div>

          <?php foreach ($sections as $sectionKey => $section): ?>
            <div class="ems-combustion-editor-block mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold"><?= esc($section['label']) ?></div>
                <span class="badge text-bg-light border"><?= esc($section['unit']) ?></span>
              </div>
              <div class="ems-month-grid">
                <?php foreach ($months as $monthNum => $labels): ?>
                  <div class="ems-month-field">
                    <label class="form-label form-label-sm" for="<?= esc($sectionKey) ?>_month_<?= (int) $monthNum ?>"><?= esc($labels['long']) ?></label>
                    <input
                      id="<?= esc($sectionKey) ?>_month_<?= (int) $monthNum ?>"
                      type="number"
                      step="0.01"
                      min="0"
                      class="form-control form-control-sm"
                      x-model="editor.sections.<?= esc($sectionKey) ?>[<?= (int) $monthNum ?>]"
                      @input="scheduleAutosave()">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <div class="col-xl-8">
      <div x-ref="summaryPanels">
        <?= view('ems/_combustion_summary_panels', [
          'reportTitle' => $boot['reportTitle'] ?? '',
          'reportSubtitle' => $boot['reportSubtitle'] ?? '',
          'sheetTitle' => $boot['sheetTitle'] ?? '',
          'companyName' => $boot['companyName'] ?? '',
          'address' => $boot['address'] ?? '',
          'baselineText' => $boot['baselineText'] ?? '',
          'years' => $boot['years'] ?? [],
          'sections' => $boot['sections'] ?? [],
          'months' => $boot['months'] ?? [],
          'sectionSummaries' => $boot['sectionSummaries'] ?? [],
          'yearMeta' => $boot['yearMeta'] ?? [],
          'totalEmissionLabel' => $boot['totalEmissionLabel'] ?? 'Total Emission per Year',
        ]) ?>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/ems-report.css?v=<?= filemtime(FCPATH . 'assets/css/ems-report.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.<?= $bootVar ?> = <?= json_encode([
    'dataset' => $boot,
    'saveUrl' => $saveUrl,
    'csrfName' => $csrfName,
    'csrfHash' => $csrfHash,
  ], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/js/ems-report.js?v=<?= filemtime(FCPATH . 'js/ems-report.js') ?>"></script>
<?= $this->endSection() ?>
