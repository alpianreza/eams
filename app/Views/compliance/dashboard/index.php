<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<form method="get" class="row g-2 mb-4">
  <div class="col-md-4">
    <select name="category" class="form-select">
      <option value="">-- Semua Kategori --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= esc($c['category']) ?>"
          <?= $selectedCategory === $c['category'] ? 'selected' : '' ?>>
          <?= esc($c['category']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-4">
    <select name="location" class="form-select">
      <option value="">-- Semua Lokasi --</option>
      <?php foreach ($locations as $l): ?>
        <option value="<?= esc($l['location']) ?>"
          <?= $selectedLocation === $l['location'] ? 'selected' : '' ?>>
          <?= esc($l['location']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-4">
    <button class="btn btn-primary w-100">
      Filter
    </button>
  </div>
</form>

<div class="row mb-4">

  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <h6>Total Inventory</h6>
        <h3><?= $summary['total_inventory'] ?></h3>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body text-center text-success">
        <h6>Checklist OK</h6>
        <h3><?= $summary['ok'] ?></h3>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body text-center text-warning">
        <h6>Due Hari Ini</h6>
        <h3><?= $summary['due'] ?></h3>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card shadow-sm">
      <a href="<?= base_url(
                  'compliance/overdue?category=' . urlencode($selectedCategory) .
                    '&location=' . urlencode($selectedLocation)
                ) ?>"
        class="text-decoration-none text-danger">

        <h6>Overdue</h6>
        <h3><?= $summary['overdue'] ?></h3>
      </a>
    </div>
  </div>
</div>


<div class="alert alert-info">
  Dashboard ini digunakan untuk monitoring compliance di komputer.
  Untuk input checklist gunakan halaman inventory / scan QR.
</div>

<?= $this->endSection() ?>