<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="checklist-master-page">
  <section class="card checklist-master-hero no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-master-kicker mb-1">Checklist Master</p>
        <h5 class="mb-1 fw-bold">Kategori Checklist Compliance</h5>
        <p class="text-muted mb-0">Pilih kategori untuk mengelola item dan pertanyaan checklist.</p>
      </div>

      <a href="<?= site_url('compliance/item/create') ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        Tambah Item Checklist
      </a>
    </div>
  </section>

  <?php if (empty($categories)): ?>
    <div class="card no-lift">
      <div class="card-body py-5 text-center text-muted">
        Belum ada kategori inventory aktif.
      </div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php
      $styleMap = [
        'Fire Safety' => ['tone' => 'danger',  'icon' => 'fa-solid fa-fire-extinguisher'],
        'HSE' => ['tone' => 'warning', 'icon' => 'fa-solid fa-helmet-safety'],
        'CTPAT' => ['tone' => 'primary', 'icon' => 'fa-solid fa-shield-halved'],
        'EMS' => ['tone' => 'success', 'icon' => 'fa-solid fa-leaf'],
        'Utility' => ['tone' => 'info', 'icon' => 'fa-solid fa-bolt'],
        'Maintenance' => ['tone' => 'secondary', 'icon' => 'fa-solid fa-screwdriver-wrench'],
        'Maintenance (Machinery)' => ['tone' => 'primary', 'icon' => 'fa-solid fa-industry'],
        'Social' => ['tone' => 'success', 'icon' => 'fa-solid fa-users'],
      ];
      ?>

      <?php foreach ($categories as $cat): ?>
        <?php
        $name = (string) $cat['name'];
        $tone = $styleMap[$name]['tone'] ?? 'dark';
        $icon = $styleMap[$name]['icon'] ?? 'fa-solid fa-layer-group';
        ?>

        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
          <a href="<?= site_url('compliance/checklist/master/category/' . $cat['id']) ?>" class="checklist-master-card tone-<?= esc($tone) ?>">
            <div class="checklist-master-card-icon">
              <i class="<?= esc($icon) ?>"></i>
            </div>
            <div class="checklist-master-card-body">
              <h6 class="mb-1"><?= esc($name) ?></h6>
              <span class="checklist-master-card-link">Kelola Item <i class="bi bi-arrow-right-short"></i></span>
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
