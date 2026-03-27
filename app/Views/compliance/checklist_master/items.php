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
      'Fire Extinguisher' => 'fa-solid fa-fire-extinguisher',
      'CCTV' => 'fa-solid fa-video',
      'AC' => 'fa-solid fa-snowflake',
      'Kursi' => 'fa-solid fa-chair',
      'Meja' => 'fa-solid fa-table',
      'Laptop' => 'fa-solid fa-laptop',
      'Komputer' => 'fa-solid fa-desktop',
      'Emergency Exit Door' => 'fa-solid fa-door-open',
      'Emergency Light' => 'fa-solid fa-lightbulb',
      'Exit Light Sign' => 'fa-solid fa-signs-post',
      'Smoke Detector' => 'fa-solid fa-smog',
      'Fire Alarm' => 'fa-solid fa-bell',
      'Hydrant' => 'fa-solid fa-faucet',
      'Heat Detector' => 'fa-solid fa-temperature-high',
      'intursion Alarm' => 'fa-solid fa-bell-slash',
    ];

    $tones = ['primary', 'success', 'info', 'warning', 'secondary', 'danger'];
    ?>

    <div class="row g-3">
      <?php foreach ($items as $item): ?>
        <?php
        $itemName = (string) $item['name'];
        $icon = $itemIcons[$itemName] ?? 'fa-solid fa-box';
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
