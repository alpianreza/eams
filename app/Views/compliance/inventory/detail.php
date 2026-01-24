<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="<?= base_url('css/inventory-detail.css') ?>">

<a href="<?= base_url('compliance/inventory') ?>" class="btn btn-sm btn-secondary mb-3">
  <i class="bi bi-arrow-left"></i> Kembali
</a>

<h5 class="mb-4 fw-semibold">Detail Inventory</h5>

<div class="row g-4">

  <!-- ================= FOTO ================= -->
  <div class="col-md-4">
    <div class="card checklist-card">
      <div class="card-body text-center">

        <?php if ($inventory['photo']): ?>
          <img src="<?= base_url('uploads/inventory/' . $inventory['photo']) ?>"
            class="img-fluid rounded mb-3"
            style="max-height:260px">
        <?php else: ?>
          <div class="text-muted mb-3">
            <i class="bi bi-image fs-1"></i><br>
            Belum ada foto
          </div>
        <?php endif; ?>

        <form action="<?= base_url('compliance/inventory/update-photo/' . $inventory['id']) ?>"
          method="post"
          enctype="multipart/form-data">
          <?= csrf_field() ?>

          <input type="file"
            name="photo"
            class="form-control form-control-sm mb-2"
            accept="image/*"
            required>

          <button class="btn btn-sm btn-primary w-100">
            <i class="bi bi-camera"></i> Ganti Foto
          </button>
        </form>

      </div>
    </div>
  </div>

  <!-- ================= INFO ================= -->
  <div class="col-md-8">
    <div class="card checklist-card">
      <div class="card-body">

        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle inventory-info-table mb-0">
            <tbody>
              <tr>
                <th>Nama Item</th>
                <td><?= esc($inventory['item_display_name']) ?></td>
              </tr>
              <tr>
                <th>No Inventaris</th>
                <td class="fw-semibold"><?= esc($inventory['asset_code']) ?></td>
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
                <td>
                  <span class="badge bg-info"><?= esc($inventory['status']) ?></span>
                </td>
              </tr>

              <?php if (!empty($inventory['expired_date'])): ?>
                <tr>
                  <th>Expired Date</th>
                  <td><?= esc($inventory['expired_date']) ?></td>
                </tr>
              <?php endif; ?>

              <tr>
                <th>Remark</th>
                <td><?= esc($inventory['remark'] ?: '-') ?></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-3 text-end">
          <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>"
            class="btn btn-success">
            <i class="bi bi-clipboard-check"></i> Checklist
          </a>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- ================= REKAP ================= -->
<h5 class="mt-5 mb-3 fw-semibold">Rekap Checklist Bulan <?= date('F Y', strtotime($ym . '-01')) ?></h5>

<div class="card checklist-card mb-4">
  <div class="card-body">
    <div class="row text-center g-3">

      <div class="col-6 col-md">
        <div class="fw-bold fs-4"><?= $rekap['total'] ?? 0 ?></div>
        <div class="text-muted">Total</div>
      </div>

      <div class="col-6 col-md">
        <div class="fw-bold fs-4 text-success"><?= $rekap['ok_count'] ?? 0 ?></div>
        <div class="text-muted">OK</div>
      </div>

      <div class="col-6 col-md">
        <div class="fw-bold fs-4 text-danger"><?= $rekap['ng_count'] ?? 0 ?></div>
        <div class="text-muted">NG</div>
      </div>

      <div class="col-6 col-md">
        <div class="fw-bold fs-4 text-warning"><?= $rekap['late_count'] ?? 0 ?></div>
        <div class="text-muted">Late</div>
      </div>

    </div>
  </div>
</div>

<!-- ================= NAV BULAN ================= -->
<div class="d-flex justify-content-center align-items-center gap-3 mb-3">
  <a href="?ym=<?= date('Y-m', strtotime($ym . ' -1 month')) ?>"
    class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-chevron-left"></i>
  </a>

  <span class="fw-semibold">
    <?= date('F Y', strtotime($ym . '-01')) ?>
  </span>

  <a href="?ym=<?= date('Y-m', strtotime($ym . ' +1 month')) ?>"
    class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-chevron-right"></i>
  </a>
</div>

<!-- ================= TABEL CHECKLIST ================= -->
<div class="card checklist-card">
  <div class="card-body p-0">

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle table-checklist mb-0">
        <thead class="table-light">
          <tr>
            <th width="20%">Tanggal</th>
            <th width="20%" class="text-center">Periode</th>
            <th width="15%" class="text-center">Status</th>
            <th>Dicek Oleh</th>
          </tr>
        </thead>
        <tbody>

          <?php if (empty($checklists)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                Tidak ada data checklist
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach ($checklists as $c): ?>
            <tr>
              <td><?= esc($c['check_date']) ?></td>
              <td class="text-center text-uppercase"><?= esc($c['period_key']) ?></td>
              <td class="text-center">
                <?php if ($c['status'] === 'ok'): ?>
                  <span class="badge bg-success">OK</span>
                <?php elseif ($c['status'] === 'ng'): ?>
                  <span class="badge bg-danger">NOT OK</span>
                <?php else: ?>
                  <span class="badge bg-secondary">N/A</span>
                <?php endif; ?>
              </td>
              <td><?= esc($c['checked_by'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>

        </tbody>
      </table>
    </div>

  </div>
</div>

<?= $this->endSection() ?>