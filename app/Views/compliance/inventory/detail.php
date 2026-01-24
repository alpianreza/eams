<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<a href="<?= base_url('compliance/inventory') ?>" class="btn btn-sm btn-secondary mt-3">
  ← Kembali
</a>
<h5 class="mb-3">Detail Inventory</h5>

<div class="row">
  <div class="col-md-4">

    <!-- FOTO -->
    <div class="card">
      <div class="card-body text-center">

        <?php if ($inventory['photo']): ?>
          <img src="<?= base_url('uploads/inventory/' . $inventory['photo']) ?>"
            class="img-fluid rounded mb-2"
            style="max-height:250px">
        <?php else: ?>
          <div class="text-muted">Belum ada foto</div>
        <?php endif; ?>

        <!-- FORM GANTI FOTO -->
        <form action="<?= base_url('compliance/inventory/update-photo/' . $inventory['id']) ?>"
          method="post"
          enctype="multipart/form-data">
          <?= csrf_field() ?>

          <input type="file"
            name="photo"
            class="form-control form-control-sm mb-2"
            accept="image/*"
            required>

          <button class="btn btn-sm btn-primary">
            Ganti Foto
          </button>
        </form>

      </div>
    </div>

  </div>

  <div class="col-md-8">

    <!-- DETAIL DATA -->
    <table class="table table-sm table-bordered">
      <tr>
        <th width="30%">Nama Item</th>
        <td><?= esc($inventory['item_display_name']) ?></td>
      </tr>
      <tr>
        <th>No Inventaris</th>
        <td><?= esc($inventory['asset_code']) ?></td>
      </tr>
      <tr>
        <th>Kategori</th>
        <td><?= esc($inventory['category_name']) ?></td>
      </tr>
      <tr>
        <th>Area</th>
        <td><?= esc($inventory['area_name']) ?> — <?= esc($inventory['specific_area']) ?></td>
      </tr>
      <tr>
        <th>PIC</th>
        <td><?= esc($inventory['pic']) ?></td>
      </tr>
      <tr>
        <th>Status</th>
        <td><?= esc($inventory['status']) ?></td>
      </tr>

      <?php if (!empty($inventory['expired_date'])): ?>
        <tr>
          <th>Expired Date</th>
          <td><?= esc($inventory['expired_date']) ?></td>
        </tr>
      <?php endif; ?>

      <tr>
        <th>Remark</th>
        <td><?= esc($inventory['remark']) ?></td>
      </tr>
    </table>

  </div>
</div>

<a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>"
  class="btn btn-success">
  ✔ Checklist
</a>

<h5 class="mt-4">Checklist</h5>

<div class="card mb-4">
  <div class="card-header">
    <strong>Rekap Checklist Bulan <?= date('F Y', strtotime($ym . '-01')) ?></strong>
  </div>

  <div class="card-body">
    <div class="row text-center">

      <div class="col">
        <div class="fw-bold fs-4"><?= $rekap['total'] ?? 0 ?></div>
        <div class="text-muted">Total</div>
      </div>

      <div class="col">
        <div class="fw-bold fs-4 text-success"><?= $rekap['ok_count'] ?? 0 ?></div>
        <div class="text-muted">OK</div>
      </div>

      <div class="col">
        <div class="fw-bold fs-4 text-danger"><?= $rekap['ng_count'] ?? 0 ?></div>
        <div class="text-muted">NG</div>
      </div>

      <div class="col">
        <div class="fw-bold fs-4 text-warning"><?= $rekap['late_count'] ?? 0 ?></div>
        <div class="text-muted">Late</div>
      </div>

    </div>
  </div>
</div>

<div class="mb-3">
  <a href="?ym=<?= date('Y-m', strtotime($ym . ' -1 month')) ?>"
    class="btn btn-sm btn-outline-secondary">⏪</a>

  <span class="mx-2 fw-bold">
    <?= date('F Y', strtotime($ym . '-01')) ?>
  </span>

  <a href="?ym=<?= date('Y-m', strtotime($ym . ' +1 month')) ?>"
    class="btn btn-sm btn-outline-secondary">⏩</a>
</div>



<table class="table table-bordered table-sm align-middle">
  <thead class="table-light">
    <tr>
      <th width="20%">Tanggal</th>
      <th width="15%">Periode</th>
      <th width="15%">Status</th>
      <th>Dicek Oleh</th>
    </tr>
  </thead>
  <tbody>

    <?php foreach ($checklists as $c): ?>

      <?php
      $rowClass = '';
      if ($c['status'] === 'ok') {
        $rowClass = 'table-success';
      } elseif ($c['status'] === 'ng') {
        $rowClass = 'table-danger';
      } else {
        $rowClass = 'table-secondary';
      }
      ?>

      <tr class="<?= $rowClass ?>">
        <td><?= esc($c['check_date']) ?></td>

        <td class="text-uppercase text-center">
          <?= esc($c['period_key']) ?>
        </td>

        <td class="text-center fw-semibold">
          <?php if ($c['status'] === 'ok'): ?>
            ✅ OK
          <?php elseif ($c['status'] === 'ng'): ?>
            ❌ NOT OK
          <?php else: ?>
            N/A
          <?php endif; ?>
        </td>

        <td><?= esc($c['checked_by'] ?? '-') ?></td>
      </tr>

    <?php endforeach; ?>


  </tbody>
</table>


<?= $this->endSection() ?>