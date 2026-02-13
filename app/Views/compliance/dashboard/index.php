<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <!-- HEADER CONTROL -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Compliance Control Center</h4>

    <form method="get" class="d-flex align-items-center gap-2">
      <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach ($availableYears as $year): ?>
          <option value="<?= $year ?>" <?= $year == $selectedYear ? 'selected' : '' ?>>
            <?= $year ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <!-- KPI ROW -->
  <div class="row">

    <?php
    $cards = [
      ['label' => 'Total Item', 'value' => $kpi['total'], 'color' => 'secondary'],
      ['label' => 'Sesuai', 'value' => $kpi['sesuai'], 'color' => 'success'],
      ['label' => 'Tidak Sesuai', 'value' => $kpi['tidak_sesuai'], 'color' => 'danger'],
      ['label' => 'Tidak Berlaku', 'value' => $kpi['tidak_berlaku'], 'color' => 'info'],
      ['label' => 'Late', 'value' => $kpi['late'], 'color' => 'warning'],
    ];
    ?>

    <?php foreach ($cards as $card): ?>
      <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card text-center border-<?= $card['color'] ?>">
          <div class="card-body">
            <h5 class="card-title text-<?= $card['color'] ?>">
              <?= $card['value'] ?>
            </h5>
            <p class="card-text small mb-0">
              <?= $card['label'] ?>
            </p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

  </div>

  <!-- ROW 2: GRAFIK -->
  <div class="row mt-4">

    <div class="col-lg-8 mb-4">
      <div class="card">
        <div class="card-header">
          <strong>Trend Checklist Bulanan</strong>
        </div>
        <div class="card-body">
          <div style="height:300px;">
            <canvas id="trendChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4 mb-4">
      <div class="card">
        <div class="card-header">
          <strong>Distribusi Status</strong>
        </div>
        <div class="card-body">
          <div style="height:300px;">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="row">

    <div class="col-md-3">
      <div class="small-box bg-danger">
        <div class="inner">
          <h3><?= $followUpStats['open'] ?></h3>
          <p>Temuan Open</p>
        </div>
        <div class="icon">
          <i class="fas fa-exclamation-circle"></i>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="small-box bg-warning">
        <div class="inner">
          <h3><?= $followUpStats['monitoring'] ?></h3>
          <p>Monitoring</p>
        </div>
        <div class="icon">
          <i class="fas fa-search"></i>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="small-box bg-success">
        <div class="inner">
          <h3><?= $followUpStats['closed_this_month'] ?></h3>
          <p>Closed Bulan Ini</p>
        </div>
        <div class="icon">
          <i class="fas fa-check-circle"></i>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="small-box bg-dark">
        <div class="inner">
          <h3><?= $followUpStats['over_30_days'] ?></h3>
          <p>Open &gt; 30 Hari ⚠</p>
        </div>
        <div class="icon">
          <i class="fas fa-clock"></i>
        </div>
      </div>
    </div>

  </div>


  <!-- ROW 3: NOTIFIKASI & FOTO -->
  <div class="row">

    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header">
          <strong>Notifikasi Aktif</strong>
        </div>
        <div class="card-body">
          <ul class="list-group list-group-flush">

            <?php if (!empty($notifications)): ?>
              <?php foreach ($notifications as $notif): ?>

                <li class="list-group-item d-flex justify-content-between align-items-center">

                  <a href="<?= base_url('compliance/inventory/detail/' . $notif['inventory_id']) ?>"
                    class="text-decoration-none d-block">

                    <?php if ($notif['type'] === 'late'): ?>
                      <span class="text-warning">
                        ⚠ <?= esc($notif['item']) ?> - <?= esc($notif['area']) ?> →
                        <?= esc($notif['message']) ?>
                      </span>
                    <?php elseif ($notif['type'] === 'not_ok'): ?>
                      <span class="text-danger">
                        ✗ <?= esc($notif['item']) ?> - <?= esc($notif['area']) ?> →
                        <?= esc($notif['message']) ?>
                      </span>
                    <?php endif; ?>

                  </a>

                </li>

              <?php endforeach; ?>
            <?php else: ?>

              <li class="list-group-item text-muted">
                Tidak ada notifikasi
              </li>

            <?php endif; ?>

          </ul>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header">
          <strong>Temuan Tidak Sesuai</strong>
        </div>
        <div class="card-body">
          <div class="row">

            <?php if (!empty($notOkPhotos)): ?>

              <?php foreach ($notOkPhotos as $log): ?>
                <div class="col-6 mb-3">

                  <div class="card">
                    <img
                      src="<?= base_url('uploads/checklist/' . $log['photo']) ?>"
                      class="card-img-top"
                      style="height:150px; object-fit:cover; cursor:pointer;"
                      onclick="showImageModal(this.src)">

                    <div class="card-body p-2">
                      <small class="text-danger">
                        ✗ <?= esc($log['remark']) ?>
                      </small>
                    </div>
                  </div>

                </div>
              <?php endforeach; ?>

            <?php else: ?>

              <div class="col-12 text-muted text-center">
                Tidak ada temuan tidak sesuai
              </div>

            <?php endif; ?>

          </div>

        </div>
      </div>
    </div>

  </div>

  <!-- ROW 4: CHECKLIST PER ITEM -->
  <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Item</th>
          <th>Area</th>
          <th>Frekuensi</th>
          <th>Status Periode Aktif</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>

        <?php if (!empty($overview)): ?>
          <?php foreach ($overview as $row): ?>
            <tr class="<?= $row['raw_status'] === 'late' ? 'table-warning' : '' ?>">

              <td><?= esc($row['item']) ?></td>
              <td><?= esc($row['area']) ?></td>
              <td><?= esc($row['frequency']) ?></td>

              <td class="text-center">
                <?= $row['status'] ?>
              </td>

              <td class="text-center">
                <a href="<?= base_url('compliance/inventory/detail/' . $row['id']) ?>"
                  class="btn btn-sm btn-outline-primary">
                  Detail
                </a>
              </td>

            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center text-muted">
              Tidak ada data
            </td>
          </tr>
        <?php endif; ?>

      </tbody>
    </table>
  </div>


</div>

<script>
  const monthlyTrend = <?= json_encode(array_values($monthlyTrend)) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  window.dashboardData = {
    monthlyTrend: <?= json_encode(array_values($monthlyTrend)) ?>,
    complianceTrend: <?= isset($complianceTrend) ? json_encode(array_values($complianceTrend)) : 'null' ?>,
    statusData: [
      <?= $kpi['sesuai'] ?>,
      <?= $kpi['tidak_sesuai'] ?>,
      <?= $kpi['tidak_berlaku'] ?>,
      <?= $kpi['late'] ?>
    ]
  };
</script>

<script src="<?= base_url('js/dashboard.js') ?>"></script>


<?= $this->endSection() ?>