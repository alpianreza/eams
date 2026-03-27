<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid home-page">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4 home-header">
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
    <div class="col-6 col-md-6 col-lg-3 mb-3">
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
    <div class="col-6 col-md-6 col-lg-3 mb-3">
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
    <div class="col-6 col-md-6 col-lg-3 mb-3">
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
    <div class="col-6 col-md-6 col-lg-3 mb-3">
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

      <div class="progress home-progress-bar">
        <div class="progress-bar <?= $progressColor ?>"
          role="progressbar"
          style="width: <?= $progress ?>%; transition: width .5s ease;">
        </div>
      </div>
    </div>
  </div>

  <!-- Pending List -->
  <div class="card mt-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center home-pending-header">
      <div>
        <h6 class="mb-0">Inventory Belum Checklist</h6>
        <small class="text-muted">
          Periode <?= date('F Y', strtotime($selectedMonth)) ?>
        </small>
      </div>

      <form method="get" class="mb-0 home-month-form">
        <select name="month"
          class="form-control form-control-sm home-month-select"
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
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <thead class="thead-light">
            <tr>
              <th width="5%" class="text-center">#</th>
              <th>Nama Item</th>
              <th>Lokasi</th>
              <th class="d-none d-md-table-cell" width="12%">Frekuensi</th>
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
                  <td class="text-center"><?= $i + 1 ?></td>
                  <td>
                    <strong><?= $inv['item_name'] ?? '-' ?></strong>
                  </td>
                  <td><?= $inv['specific_area'] ?? '-' ?></td>
                  <td class="d-none d-md-table-cell">
                    <span class="badge bg-light text-dark">
                      <?= ucfirst($inv['checklist_frequency']) ?>
                    </span>
                  </td>
                  <td>
                    <button
                      class="badge <?= $badgeColor ?> border-0 open-popover"
                      data-id="<?= $inv['id'] ?>"
                      data-frequency="<?= $inv['checklist_frequency'] ?>"
                      data-missing='<?= json_encode($inv['missing_periods'] ?? []) ?>'>
                      <?= $inv['remaining'] ?>
                    </button>
                  </td>
                  <td>
                    <button
                      class="btn btn-sm btn-primary open-popover w-100 w-md-auto"
                      data-id="<?= $inv['id'] ?>"
                      data-frequency="<?= $inv['checklist_frequency'] ?>"
                      data-missing='<?= json_encode($inv['missing_periods'] ?? []) ?>'>
                      <i class="fas fa-check"></i>
                      <span class="d-none d-md-inline">Checklist</span>
                    </button>
                  </td>
                </tr>

              <?php endforeach ?>

            <?php endif; ?>

          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<style>
  @media(max-width:768px) {

    .home-page .w-md-auto {
      width: auto !important;
    }

    .home-page .home-header,
    .home-page .home-pending-header {
      align-items: flex-start !important;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .home-page .home-month-form {
      width: 100%;
    }

    .home-page .home-month-select {
      width: 100%;
    }

    /* font tabel */
    .home-page .table {
      font-size: .78rem;
    }

    /* padding sel */
    .home-page .table td,
    .home-page .table th {
      padding: .35rem .45rem;
      line-height: 1.2;
    }

    /* header lebih kecil */
    .home-page .table thead th {
      font-size: .7rem;
      font-weight: 600;
    }

    /* badge kecil */
    .home-page .badge {
      font-size: .65rem;
      padding: .25em .45em;
    }

    /* tombol kecil */
    .home-page .btn-sm {
      font-size: .7rem;
      padding: .25rem .4rem;
    }

    /* judul card */
    .home-page .card-header h6 {
      font-size: .85rem;
    }

    /* subtitle */
    .home-page .card-header small {
      font-size: .7rem;
    }

  }
</style>


<!-- ================= POPOVER JS ================= -->
<script>
  document.addEventListener("click", function(e) {

    const btn = e.target.closest(".open-popover");
    if (!btn) return;

    const id = btn.dataset.id;
    const freq = btn.dataset.frequency;
    const missing = JSON.parse(btn.dataset.missing || "[]");

    let html = "";

    if (missing.length === 0) {
      html = `<span class="text-success">Semua selesai</span>`;
    } else {

      html += `<div class="d-flex flex-wrap gap-1">`;

      missing.forEach(p => {

        let periodKey;

        if (freq === 'daily') {
          periodKey = "<?= $selectedMonth ?>-" + p;
        } else if (freq === 'weekly') {
          periodKey = "<?= $selectedMonth ?>-W" + p;
        } else {
          periodKey = "<?= $selectedMonth ?>";
        }

        html += `
      <a href="<?= base_url('compliance/checklist') ?>/${id}?period_key=${periodKey}"
         class="badge bg-warning text-decoration-none">
         ${p}
      </a>`;
      });

      html += "</div>";
    }

    // destroy popover lama
    if (btn._popover) {
      btn._popover.dispose();
    }

    btn._popover = new bootstrap.Popover(btn, {
      html: true,
      content: html,
      trigger: 'focus',
      placement: window.innerWidth < 768 ? 'bottom' : 'left'
    });

    btn._popover.show();

  });
</script>

<?= $this->endSection() ?>
