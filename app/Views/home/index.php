<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">Halo, <?= session('name') ?> 👋</h4>
      <small class="text-muted">
        Status checklist periode <?= date('F Y', strtotime($selectedMonth)) ?>
      </small>
    </div>
  </div>

  <!-- KPI -->
  <div class="row">

    <?php
    $pendingColor = $summary['pending'] > 0 ? 'text-warning' : 'text-success';
    $notOkColor   = $summary['not_ok'] > 0 ? 'text-danger' : 'text-success';

    $progressColor = 'text-success';
    if ($progress < 50) $progressColor = 'text-danger';
    elseif ($progress < 80) $progressColor = 'text-warning';
    ?>

    <!-- Total Inventory -->
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card shadow-sm h-100 border-left-info">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <small class="text-muted">Total Inventory</small>
              <h3 class="font-weight-bold mb-0">
                <?= $summary['total'] ?>
              </h3>
            </div>
            <i class="fas fa-boxes text-info fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending -->
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card shadow-sm h-100 border-left-warning">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <small class="text-muted">Periode Belum Checklist</small>
              <h3 class="font-weight-bold mb-0 <?= $pendingColor ?>">
                <?= $summary['pending'] ?>
              </h3>
            </div>
            <i class="fas fa-clock text-warning fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Not OK -->
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card shadow-sm h-100 border-left-danger">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <small class="text-muted">Temuan (✗)</small>
              <h3 class="font-weight-bold mb-0 <?= $notOkColor ?>">
                <?= $summary['not_ok'] ?>
              </h3>
            </div>
            <i class="fas fa-exclamation-triangle text-danger fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress -->
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card shadow-sm h-100 border-left-success">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <small class="text-muted">Progress Bulan Ini</small>
              <h3 class="font-weight-bold mb-0 <?= $progressColor ?>">
                <?= $progress ?>%
              </h3>
            </div>
            <i class="fas fa-chart-line text-success fa-2x opacity-50"></i>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Progress -->
  <?php
  $progressColor = 'bg-success';
  if ($progress < 50) $progressColor = 'bg-danger';
  elseif ($progress < 80) $progressColor = 'bg-warning';
  ?>

  <div class="card shadow-sm mt-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Progress Checklist</h6>
        <strong><?= $progress ?>%</strong>
      </div>

      <div class="progress" style="height: 14px;">
        <div class="progress-bar <?= $progressColor ?>"
          role="progressbar"
          style="width: <?= $progress ?>%; transition: width .5s ease;">
        </div>
      </div>
    </div>
  </div>

  <!-- Pending List -->
  <div class="card mt-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h6 class="mb-0">Inventory Belum Checklist</h6>
        <small class="text-muted">
          Periode <?= date('F Y', strtotime($selectedMonth)) ?>
        </small>
      </div>

      <form method="get" class="mb-0">
        <select name="month"
          class="form-control form-control-sm"
          onchange="this.form.submit()">

          <?php
          $start = new DateTime('2026-01-01');
          $end   = new DateTime(date('Y-m-01'));

          while ($start <= $end):

            $value = $start->format('Y-m');
            $label = $start->format('F Y');
          ?>
            <option value="<?= $value ?>"
              <?= $selectedMonth == $value ? 'selected' : '' ?>>
              <?= $label ?>
            </option>
          <?php
            $start->modify('+1 month');
          endwhile;
          ?>

        </select>
      </form>
    </div>

    <div class="card-body p-0">
      <table class="table table-hover table-striped mb-0">
        <thead class="thead-light">
          <tr>
            <th width="5%">No</th>
            <th>Nama Item</th>
            <th>Lokasi</th>
            <th width="12%">Frekuensi</th>
            <th width="10%">Sisa</th>
            <th width="12%">Aksi</th>
          </tr>
        </thead>
        <tbody>

          <?php if (empty($pendingList)) : ?>

            <tr>
              <td colspan="6" class="text-center py-5">
                <i class="fas fa-check-circle text-success fa-3x mb-3"></i><br>
                <strong>Semua periode sudah selesai 🎉</strong><br>
                <small class="text-muted">
                  Pertahankan konsistensi kamu!
                </small>
              </td>
            </tr>

          <?php else: ?>

            <?php foreach ($pendingList as $i => $inv): ?>

              <?php
              if ($inv['remaining'] == 0) {
                $badgeColor = 'bg-success';
              } elseif ($inv['remaining'] <= 3) {
                $badgeColor = 'bg-warning';
              } else {
                $badgeColor = 'bg-danger';
              }
              ?>

              <tr>
                <td><?= $i + 1 ?></td>
                <td>
                  <strong><?= $inv['item_name'] ?? '-' ?></strong>
                </td>
                <td><?= $inv['specific_area'] ?? '-' ?></td>
                <td>
                  <span class="badge badge-light">
                    <?= ucfirst($inv['checklist_frequency']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $badgeColor ?>">
                    <?= $inv['remaining'] ?>
                  </span>
                </td>
                <td>
                  <a href="<?= base_url('compliance/inventory/detail/' . $inv['id']) ?>"
                    class="btn btn-sm btn-primary">
                    <i class="fas fa-check mr-1"></i> Checklist
                  </a>
                </td>
              </tr>

            <?php endforeach ?>

          <?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>

</div>

<?= $this->endSection() ?>