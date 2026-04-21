<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="checklist-page">
  <section class="card border-0 shadow-sm checklist-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <p class="checklist-kicker mb-1">Checklist Compliance</p>
        <h5 class="mb-1 fw-bold"><?= esc($inventory['item_display_name']) ?></h5>
        <p class="text-muted mb-0">No Inventaris: <strong><?= esc($inventory['asset_code']) ?></strong></p>
      </div>
      <span class="badge bg-info text-dark mt-1">Frekuensi: <?= strtoupper(esc($frequency)) ?></span>
    </div>
  </section>

  <div id="checklistAjax" class="checklist-ajax-shell">
    <?= $this->include('compliance/checklist/_calendar') ?>
    <?= $this->include('compliance/checklist/_form') ?>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/checklist.css?v=' . filemtime(FCPATH . 'assets/css/checklist.css')) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/calendar.css?v=' . filemtime(FCPATH . 'assets/css/calendar.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/checklist.js') . '?v=' . filemtime(FCPATH . 'js/checklist.js') ?>"></script>
<script>
  window.CHECKLIST_USER = "<?= esc(session('name')) ?>";
  window.CHECKLIST_FLASH = {
    success: <?= session()->getFlashdata('success') ? json_encode(session('success')) : 'null' ?>,
    error: <?= session()->getFlashdata('error') ? json_encode(session('error')) : 'null' ?>
  };
</script>
<?= $this->endSection() ?>
