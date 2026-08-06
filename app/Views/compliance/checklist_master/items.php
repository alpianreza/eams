<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="checklist-master-page">
  <section class="card checklist-master-hero no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-master-kicker mb-1">Checklist Master</p>
        <h5 class="mb-1 fw-bold"><?= esc($category['name']) ?></h5>
        <p class="text-muted mb-0">Pilih item untuk mengelola pertanyaan checklist.</p>
      </div>

      <a href="<?= site_url('compliance/checklist/master') ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        Kembali ke Kategori
      </a>
    </div>
  </section>

  <?php if (empty($items)): ?>
    <div class="card no-lift">
      <div class="card-body py-5 text-center text-muted">
        Belum ada item checklist pada kategori ini.
      </div>
    </div>
  <?php else: ?>
    <?php
    $itemIcons = [
      'Fire Extinguisher' => 'bi bi-fire',
      'CCTV' => 'bi bi-camera-video',
      'AC' => 'bi bi-snow',
      'Kursi' => 'bi bi-person-workspace',
      'Meja' => 'bi bi-table',
      'Laptop' => 'bi bi-laptop',
      'Komputer' => 'bi bi-pc-display',
      'Emergency Exit Door' => 'bi bi-door-open',
      'Emergency Light' => 'bi bi-lightbulb',
      'Exit Light Sign' => 'bi bi-signpost-2',
      'Smoke Detector' => 'bi bi-cloud-haze2',
      'Fire Alarm' => 'bi bi-bell',
      'Hydrant' => 'bi bi-moisture',
      'Heat Detector' => 'bi bi-thermometer-high',
      'intursion Alarm' => 'bi bi-bell-slash',
    ];

    $tones = ['primary', 'success', 'info', 'warning', 'secondary', 'danger'];
    ?>

    <div class="row g-3">
      <?php foreach ($items as $item): ?>
        <?php
        $itemName = (string) $item['name'];
        $icon = $itemIcons[$itemName] ?? 'bi bi-box-seam';
        $tone = $tones[$item['id'] % count($tones)];
        ?>

        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
          <a href="<?= site_url('compliance/checklist/master/item/' . $item['id']) ?>" class="checklist-master-card tone-<?= esc($tone) ?>">
            <div class="checklist-master-card-icon">
              <i class="<?= esc($icon) ?>"></i>
            </div>
            <div class="checklist-master-card-body">
              <h6 class="mb-1"><?= esc($itemName) ?></h6>
              <span class="checklist-master-card-link">Kelola Pertanyaan <i class="bi bi-arrow-right-short"></i></span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/checklist-master.css?v=' . filemtime(FCPATH . 'assets/css/checklist-master.css')) ?>">
<?= $this->endSection() ?>
