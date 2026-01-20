<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row g-4">

  <!-- TOTAL ASSET IT -->
  <div class="col-xl-3 col-md-6">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="card-label">Total Asset IT</div>
          <div class="card-value"><?= $totalIT ?></div>
        </div>
        <div class="card-icon primary">
          <i class="bi bi-pc-display"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- ASSET DIPAKAI -->
  <div class="col-xl-3 col-md-6">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="card-label">Asset Dipakai</div>
          <div class="card-value"><?= $usedAsset ?></div>
        </div>
        <div class="card-icon success">
          <i class="bi bi-check-circle"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- ASSET RUSAK -->
  <div class="col-xl-3 col-md-6">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="card-label">Asset Rusak</div>
          <div class="card-value"><?= $brokenAsset ?></div>
        </div>
        <div class="card-icon danger">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- COMPLIANCE ASSET -->
  <div class="col-xl-3 col-md-6">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="card-label">Compliance Asset</div>
          <div class="card-value"><?= $complianceAsset ?></div>
        </div>
        <div class="card-icon warning">
          <i class="bi bi-shield-check"></i>
        </div>
      </div>
    </div>
  </div>

</div>

<?= $this->endSection() ?>