<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>


<div class="container-fluid">

  <div class="row mb-4">
    <div class="col-md-12">
      <h4>Halo, <?= session('name') ?> 👋</h4>
      <p>Berikut status checklist kamu bulan ini.</p>
    </div>
  </div>

  <!-- KPI -->
  <div class="row">

    <div class="col-lg-3 col-6">
      <div class="small-box bg-info">
        <div class="inner">
          <h3><?= $summary['total'] ?></h3>
          <p>Total Inventory</p>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-6">
      <div class="small-box bg-warning">
        <div class="inner">
          <h3><?= $summary['pending'] ?></h3>
          <p>Belum Checklist</p>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-6">
      <div class="small-box bg-danger">
        <div class="inner">
          <h3><?= $summary['not_ok'] ?></h3>
          <p>Temuan (✗)</p>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-6">
      <div class="small-box bg-success">
        <div class="inner">
          <h3><?= $progress ?>%</h3>
          <p>Progress</p>
        </div>
      </div>
    </div>

  </div>

  <!-- Progress Bar -->
  <div class="card">
    <div class="card-body">
      <h5>Progress Checklist</h5>
      <div class="progress">
        <div class="progress-bar bg-success"
          style="width: <?= $progress ?>%">
          <?= $progress ?>%
        </div>
      </div>
    </div>
  </div>

  <!-- Pending List -->
  <div class="card mt-4">
    <div class="card-header">
      <h5>Inventory Belum Checklist</h5>
    </div>
    <div class="card-body p-0">
      <table class="table table-bordered mb-0">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Item</th>
            <th>Lokasi</th>
            <th>Frekuensi</th>
            <th>Sisa Periode</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pendingList)) : ?>
            <tr>
              <td colspan="6" class="text-center">
                Semua sudah checklist ✅
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($pendingList as $i => $inv): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= $inv['item_name'] ?? '-' ?></td>
                <td><?= $inv['specific_area'] ?? '-' ?></td>
                <td><?= ucfirst($inv['checklist_frequency']) ?></td>
                <td>
                  <span class="badge bg-warning">
                    <?= $inv['remaining'] ?>
                  </span>
                </td>
                <td>
                  <a href="<?= base_url('compliance/checklist/detail/' . $inv['id']) ?>"
                    class="btn btn-sm btn-primary">
                    Checklist
                  </a>
                </td>
              </tr>
            <?php endforeach ?>
          <?php endif ?>
        </tbody>
      </table>

    </div>
  </div>

</div>


<?= $this->endSection() ?>