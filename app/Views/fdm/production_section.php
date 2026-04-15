<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="fdm-page" x-data="fdmProductionSectionPage(window.FDM_PRODUCTION_SECTION_BOOT || {})">
  <section class="card border-0 shadow-sm fdm-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div class="fdm-hero-copy">
        <p class="fdm-kicker mb-1">FDM Data Collection</p>
        <h5 class="fw-bold mb-1">Production Section</h5>
        <p class="text-muted mb-0">
          Form bulanan untuk retail produksi. Total <strong>Finished Product Assembler</strong> dihitung otomatis dari retail,
          dan baris <strong>Number of Full Time employee</strong> tetap bisa diisi terpisah.
        </p>
      </div>

      <div class="fdm-save-indicator" :class="saveStateClass" x-text="saveMessage"></div>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div class="d-flex flex-wrap gap-2">
        <template x-for="year in availableYears" :key="year">
          <a
            class="btn btn-sm"
            :class="selectedYear === year ? 'btn-primary' : 'btn-outline-primary'"
            :href="yearHref(year)"
            x-text="year"
          ></a>
        </template>
      </div>

      <button type="button" class="btn btn-success btn-sm" @click="addRetail()">
        <i class="bi bi-plus-circle me-1"></i> Tambah Retail
      </button>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle fdm-production-table mb-0">
          <thead>
            <tr>
              <th class="fdm-sticky-col fdm-col-label">Production Section</th>
              <th class="fdm-col-frequency">Frequency</th>
              <template x-for="month in monthSequence" :key="month.key">
                <th class="text-center fdm-col-month" x-text="month.label"></th>
              </template>
              <th class="text-center fdm-col-action">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr class="fdm-row-total">
              <td class="fdm-sticky-col">
                <div class="fdm-row-label-wrap">
                  <div class="fdm-logo-badge is-total">
                    <i class="bi bi-box-seam"></i>
                  </div>
                  <div>
                    <div class="fw-semibold" x-text="aggregateRow.label"></div>
                    <div class="text-muted small">Terhitung otomatis dari seluruh retail di bawahnya.</div>
                  </div>
                </div>
              </td>
              <td class="text-center" x-text="aggregateRow.frequency"></td>
              <template x-for="month in monthSequence" :key="'aggregate-' + month.key">
                <td class="text-end fdm-cell-readonly" x-text="formatNumber(aggregateValues[month.key])"></td>
              </template>
              <td></td>
            </tr>

            <template x-for="(retail, index) in retailers" :key="retail.uid">
              <tr>
                <td class="fdm-sticky-col">
                  <div class="fdm-row-label-wrap">
                    <div class="fdm-logo-badge" :style="logoStyle(retail.label)" x-text="logoText(retail.label)"></div>
                    <div class="flex-grow-1">
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        x-model="retail.label"
                        @input="queueSave()"
                        placeholder="Nama retail"
                      >
                    </div>
                  </div>
                </td>
                <td class="text-center">
                  <span class="fdm-frequency-pill" x-text="retail.frequency"></span>
                </td>
                <template x-for="month in monthSequence" :key="retail.uid + '-' + month.key">
                  <td>
                    <input
                      type="number"
                      step="0.01"
                      class="form-control form-control-sm text-end"
                      x-model="retail.values[month.key]"
                      @input="queueSave()"
                    >
                  </td>
                </template>
                <td class="text-center">
                  <button type="button" class="btn btn-outline-danger btn-sm" @click="removeRetail(index)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </template>

            <tr class="fdm-row-workforce">
              <td class="fdm-sticky-col">
                <div class="fdm-row-label-wrap">
                  <div class="fdm-logo-badge is-workforce">
                    <i class="bi bi-people"></i>
                  </div>
                  <div>
                    <div class="fw-semibold" x-text="workforce.label"></div>
                    <div class="text-muted small">Diisi manual per bulan.</div>
                  </div>
                </div>
              </td>
              <td class="text-center" x-text="workforce.frequency"></td>
              <template x-for="month in monthSequence" :key="'workforce-' + month.key">
                <td>
                  <input
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm text-end"
                    x-model="workforce.values[month.key]"
                    @input="queueSave()"
                  >
                </td>
              </template>
              <td></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/fdm-data-collection.css?v=<?= filemtime(FCPATH . 'assets/css/fdm-data-collection.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.FDM_PRODUCTION_SECTION_BOOT = <?= json_encode($boot, JSON_UNESCAPED_UNICODE) ?>;
  window.FDM_PRODUCTION_SECTION_CSRF = {
    name: <?= json_encode($csrfName) ?>,
    hash: <?= json_encode($csrfHash) ?>,
  };
</script>
<script src="/js/fdm-data-collection.js?v=<?= filemtime(FCPATH . 'js/fdm-data-collection.js') ?>"></script>
<?= $this->endSection() ?>
