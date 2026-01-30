<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <div class="mb-3">
    <h4 class="mb-0"><?= esc($category['name']) ?></h4>
    <small class="text-muted">Pilih item untuk kelola checklist</small>
  </div>

  <div class="row mb-3">
    <div class="col-12">
      <a href="<?= site_url('compliance/checklist/master') ?>" class="btn btn-sm btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>
        Kembali ke Kategori
      </a>
    </div>
  </div>

  <div class="row">

    <?php if (empty($items)): ?>
      <div class="col-12">
        <div class="alert alert-warning">
          Belum ada item pada kategori ini.
        </div>
      </div>
    <?php endif; ?>

    <?php
    // icon mapping (boleh ditambah)
    $itemIcons = [
      'Fire Extinguisher' => 'fa-solid fa-fire-extinguisher',
      'CCTV'      => 'fa-solid fa-video',
      'AC'        => 'fa-solid fa-snowflake',
      'Kursi'     => 'fa-solid fa-chair',
      'Meja'      => 'fa-solid fa-table',
      'Laptop'    => 'fa-solid fa-laptop',
      'Komputer'  => 'fa-solid fa-desktop',
      'Emergency Exit Door' => 'fa-solid fa-door-open',
      'Emergency Light'      => 'fa-solid fa-lightbulb',
      'Exit Light Sign'    => 'fa-solid fa-signs-post',
      'Smoke Detector'    => 'fa-solid fa-smog',
      'Fire Alarm'    => 'fa-solid fa-bell',
      'Hydrant'    => 'fa-solid fa-faucet',
      'Heat Detector'    => 'fa-solid fa-temperature-high',
      'intursion Alarm'    => 'fa-solid fa-bell-slash',
    ];

    // PALET WARNA (AMAN DI MATA)
    $colors = ['primary', 'success', 'info', 'warning', 'secondary', 'dark', 'danger'];
    ?>

    <?php foreach ($items as $item): ?>
      <?php
      // RANDOM WARNA TAPI KONSISTEN
      $color = $colors[$item['id'] % count($colors)];

      $itemName = $item['name'];
      $icon     = $itemIcons[$itemName] ?? 'fa-solid fa-box';
      ?>

      <div class="col-lg-3 col-md-4 col-sm-6 col-12">
        <a href="<?= site_url('compliance/checklist/master/item/' . $item['id']) ?>"
          class="small-box bg-<?= $color ?> checklist-box text-<?= in_array($color, ['warning', 'light']) ? 'dark' : 'white' ?>">

          <div class="inner">
            <h5 class="fw-bold mb-2"><?= esc($itemName) ?></h5>

          </div>

          <div class="icon">
            <i class="<?= $icon ?>"></i>
          </div>

          <div class="small-box-footer">
            Kelola Checklist <i class="fa-solid fa-arrow-circle-right"></i>
          </div>

        </a>
      </div>

    <?php endforeach; ?>

  </div>

</div>

<?= $this->endSection() ?>