<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>


<div class="container-fluid">

  <div class="mb-3">
    <h4 class="mb-0">Checklist Master</h4>
    <small class="text-muted">Kelompok berdasarkan kategori</small>
  </div>
  <div class="mb-3">
    <a href="<?= site_url('compliance/item/create') ?>" class="btn btn-sm btn-primary">
      <i class="fa-solid fa-plus me-1"></i>
      Tambah Item Checklist
    </a>
  </div>
</div>

<div class="row">

  <?php if (empty($categories)): ?>
    <div class="col-12">
      <div class="alert alert-warning">
        Belum ada kategori inventory.
      </div>
    </div>
  <?php endif; ?>

  <?php
  $styleMap = [
    'Fire Safety' => ['color' => 'danger',  'icon' => 'fa-solid fa-fire-extinguisher'],
    'HSE'         => ['color' => 'warning', 'icon' => 'fa-solid fa-helmet-safety'],
    'CTPAT'      => ['color' => 'primary', 'icon' => 'fa-solid fa-shield-halved'],
    'EMS'         => ['color' => 'success', 'icon' => 'fa-solid fa-leaf'],
    'Utility'     => ['color' => 'info',    'icon' => 'fa-solid fa-bolt'],
    'Maintenance' => ['color' => 'secondary', 'icon' => 'fa-solid fa-screwdriver-wrench'],
    'Maintenance (Machinery)' => ['color' => 'primary', 'icon' => 'fa-solid fa-industry'],
    'Social'      => ['color' => 'success', 'icon' => 'fa-solid fa-users'],
  ];
  ?>

  <?php foreach ($categories as $cat): ?>
    <?php
    $name  = $cat['name'];
    $color = $styleMap[$name]['color'] ?? 'dark';
    $icon  = $styleMap[$name]['icon']  ?? 'fa-solid fa-layer-group';
    $text  = in_array($color, ['warning', 'light']) ? 'dark' : 'white';
    ?>

    <div class="col-lg-3 col-md-4 col-sm-6 col-12">
      <a href="<?= site_url('compliance/checklist/master/category/' . $cat['id']) ?>"
        class="small-box bg-<?= $color ?> checklist-box text-<?= $text ?>"
        style="text-decoration:none;">

        <div class="inner">
          <h4 class="fw-bold mb-2"><?= esc($name) ?></h4>
        </div>

        <div class="icon">
          <i class="<?= $icon ?>"></i>
        </div>

        <div class="small-box-footer">
          Kelola Item <i class="fa-solid fa-arrow-circle-right"></i>
        </div>

      </a>
    </div>

  <?php endforeach; ?>

</div>

</div>


<?= $this->endSection() ?>