<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="fdm-page" x-data="fdmDataCollectionIndex(window.FDM_DATA_COLLECTION_BOOT || {})">
  <section class="card border-0 shadow-sm fdm-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div class="fdm-hero-copy">
        <p class="fdm-kicker mb-1">FDM Data Collection</p>
        <h5 class="fw-bold mb-1">Data Workspace</h5>
        <p class="text-muted mb-0">
          Ruang kerja pengumpulan data FDM. Form utama kita fokuskan ke <strong>Production Section</strong>,
          sementara workspace lain tetap disiapkan menyusul.
        </p>
      </div>

      <div class="fdm-inline-search">
        <label for="fdmCollectionSearch" class="form-label form-label-sm">Cari ruang kerja</label>
        <input
          id="fdmCollectionSearch"
          type="text"
          class="form-control"
          x-model="query"
          placeholder="Cari berdasarkan nama atau fungsi..."
        >
      </div>
    </div>
  </section>

  <div class="row g-3">
    <template x-for="collection in filteredCollections" :key="collection.title">
      <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100 no-lift fdm-collection-card" :class="collection.status === 'ready' ? 'is-ready' : 'is-soon'">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <div>
                <div class="fdm-card-title" x-text="collection.title"></div>
                <div class="text-muted small" x-text="collection.subtitle"></div>
              </div>
              <span class="badge rounded-pill" :class="collection.status === 'ready' ? 'text-bg-success' : 'text-bg-secondary'" x-text="collection.status === 'ready' ? 'Ready' : 'Soon'"></span>
            </div>

            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="fdm-card-icon">
                <i class="bi" :class="collection.icon"></i>
              </div>
              <template x-if="collection.status === 'ready'">
                <div class="fdm-card-focus-note">
                  Form utama yang sudah siap dipakai sekarang.
                </div>
              </template>
            </div>

            <div class="mt-auto pt-2">
              <template x-if="collection.status === 'ready' && collection.href">
                <a :href="collection.href" class="btn btn-primary btn-sm">
                  <i class="bi bi-box-arrow-up-right me-1"></i> Buka Workspace
                </a>
              </template>

              <template x-if="collection.status !== 'ready' || !collection.href">
                <button type="button" class="btn btn-outline-secondary btn-sm" disabled>Segera Disusun</button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div class="col-12" x-show="filteredCollections.length === 0" x-cloak>
      <div class="card border-0 shadow-sm no-lift">
        <div class="card-body text-muted">Ruang kerja FDM yang dicari belum ada.</div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/fdm-data-collection.css?v=<?= filemtime(FCPATH . 'assets/css/fdm-data-collection.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.FDM_DATA_COLLECTION_BOOT = <?= json_encode(['collections' => $collections], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/js/fdm-data-collection.js?v=<?= filemtime(FCPATH . 'js/fdm-data-collection.js') ?>"></script>
<?= $this->endSection() ?>
