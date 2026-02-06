<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

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
          <img
            id="inventoryPhoto"
            src="<?= base_url('uploads/inventory/' . $inventory['photo']) ?>"
            class="img-fluid rounded mb-3"
            style="max-height:260px; cursor:zoom-in;"
            data-bs-toggle="modal"
            data-bs-target="#modalZoomPhoto">
        <?php else: ?>
          <div class="text-muted mb-3">
            <i class="bi bi-image fs-1"></i><br>
            Belum ada foto
          </div>
        <?php endif; ?>

        <!-- FORM UPLOAD (FORM BIASA, TOAST JALAN) -->
        <form
          action="<?= base_url('compliance/inventory/update-photo/' . $inventory['id']) ?>"
          method="post"
          enctype="multipart/form-data">

          <?= csrf_field() ?>

          <input
            type="file"
            name="photo"
            class="form-control form-control-sm mb-2"
            accept="image/*"
            capture="environment"
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

        <div class="mt-3 d-flex justify-content-end gap-2">
          <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>" class="btn btn-success">
            <i class="bi bi-clipboard-check"></i> Checklist
          </a>

          <?php if (session('role') === 'admin'): ?>
            <?php
            $initialPeriod = match ($inventory['checklist_frequency']) {
              'daily'   => $ym . '-01',
              'weekly'  => $ym . '-W1',
              default   => $ym,
            };
            ?>
            <a
              class="btn btn-danger"
              target="_blank"
              href="<?= site_url('pdf/checklist/single/' . $inventory['id'] . '/' . $initialPeriod) ?>">
              <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<?= $this->include('compliance/inventory/_detail_month') ?>

<!-- ================= MODAL ZOOM FOTO ================= -->
<?= $this->include('compliance/inventory/_modal_zoom') ?>

<?= $this->endSection() ?>