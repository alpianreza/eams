<?= $this->extend('layouts/main') ?>

<?php
$title = 'Detail Inventory';
$backUrl = base_url('compliance/inventory');
?>

<?= $this->section('content') ?>
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
        <?php if (hasRole(['admin', 'compliance'])): ?>
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
        <?php endif; ?>

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

          <?php if (hasRole(['admin', 'compliance', 'staff'])): ?>
            <a href="<?= base_url('compliance/checklist/' . $inventory['id']) ?>" class="btn btn-success">
              <i class="bi bi-clipboard-check"></i> Checklist
            </a>
          <?php endif; ?>

          <?php if (hasRole(['admin', 'compliance'])): ?>

            <?php
            $ym = date('Y-m');
            [$year, $month] = explode('-', $ym);
            ?>

            <a
              class="btn btn-danger"
              target="_blank"
              href="<?= site_url('export/pdf/recap/' . $inventory['id'] . '/' . $year . '/' . $month) ?>">
              <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>

          <?php endif; ?>


        </div>

      </div>
    </div>
  </div>
</div>

<?php if (hasRole(['auditor', 'admin', 'compliance', 'staff'])): ?>
  <?= $this->include('compliance/inventory/_detail_month') ?>
<?php endif; ?>


<!-- ================= MODAL ZOOM FOTO ================= -->
<?= $this->include('compliance/inventory/_modal_zoom') ?>

<script src="<?= base_url('js/inventory-detail.js') ?>"></script>


<script>
  function openChecklistZoom(imageUrl) {

    const img = document.getElementById('zoomChecklistImage');
    img.src = imageUrl;

    const modalElement = document.getElementById('modalZoomChecklist');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  }

  document.getElementById('modalZoomChecklist')
    .addEventListener('hidden.bs.modal', function() {
      document.getElementById('zoomChecklistImage').src = '';
    });
</script>

<?= $this->endSection() ?>