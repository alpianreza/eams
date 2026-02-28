<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Device Control Center</h3>
    </div>

    <div class="row g-3 mb-3">

      <?php
      $cards = [
        ['Total Device', $kpi['total'] ?? 0, 'bi-hdd-network', 'info'],
        ['Healthy', $kpi['healthy'] ?? 0, 'bi-check-circle', 'success'],
        ['Warning', $kpi['warning'] ?? 0, 'bi-exclamation-circle', 'warning'],
        ['Critical', $kpi['critical'] ?? 0, 'bi-x-circle', 'danger'],
        ['Offline', $kpi['offline'] ?? 0, 'bi-wifi-off', 'secondary'],
        ['Need Update', $kpi['update'] ?? 0, 'bi-arrow-repeat', 'primary'],
      ];
      ?>

      <?php foreach ($cards as $c): ?>
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card shadow-sm border-0">
            <div class="card-body py-3">

              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="text-muted small"><?= $c[0] ?></div>
                  <div class="fs-4 fw-semibold"><?= $c[1] ?></div>
                </div>

                <div class="text-<?= $c[3] ?>">
                  <i class="bi <?= $c[2] ?> fs-3"></i>
                </div>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach ?>

    </div>
    
    <div class="card-body">

      <input type="text" id="searchDevice" class="form-control mb-3" placeholder="Cari device...">

      <div id="deviceAjax">
        <!-- ajax load -->
      </div>

    </div>
  </div>

</div>

<?= $this->section('scripts') ?>
<script src="/js/it-devices.js"></script>
<script src="/js/device-remote.js"></script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>