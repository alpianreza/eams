<?= $this->extend('layouts/main') ?>

<?php
$title = 'Detail Compliance Inventory';
$backUrl = base_url('compliance/inventory');

$frequencyValue = strtolower((string)($inventory['checklist_frequency'] ?? 'monthly'));
$frequencyLabel = match ($frequencyValue) {
  'daily' => 'Harian',
  'weekly' => 'Mingguan',
  default => 'Bulanan',
};

$statusValue = (string)($inventory['status'] ?? '');
$statusClass = 'bg-light text-dark';
$statusLabel = '-';

if ($statusValue === 'Good') {
  $statusClass = 'bg-success';
  $statusLabel = 'Baik';
} elseif ($statusValue === 'Need Repair') {
  $statusClass = 'bg-warning text-dark';
  $statusLabel = 'Perlu Perbaikan';
} elseif ($statusValue === 'Not Active') {
  $statusClass = 'bg-secondary';
  $statusLabel = 'Tidak Aktif';
}
?>

<?= $this->section('content') ?>

<div class="compliance-inventory-detail">
  <section class="card border-0 shadow-sm detail-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="inventory-kicker mb-1">Detail Compliance Inventory</p>
        <h5 class="mb-1 fw-bold"><?= esc($inventory['item_display_name']) ?></h5>
        <p class="text-muted mb-2">Kode inventaris: <strong><?= esc($inventory['asset_code']) ?></strong></p>

        <div class="d-flex flex-wrap gap-2">
          <span class="badge bg-info text-dark">Frekuensi: <?= esc($frequencyLabel) ?></span>
          <span class="badge <?= $statusClass ?>">Status: <?= esc($statusLabel) ?></span>
          <span class="badge bg-light text-dark border">Area: <?= esc($inventory['area_name']) ?></span>
        </div>
      </div>

      <div class="detail-hero-actions ms-auto d-flex flex-wrap gap-2">
          <?php if (hasRole(['admin', 'compliance', 'staff'])): ?>
            <?php if ((int) ($inventory['item_type_id'] ?? 0) === 13): ?>
              <a href="/compliance/checklist/cctv-grid?ym=<?= esc($ym) ?>&focus_id=<?= (int) $inventory['id'] ?>" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-grid-3x3-gap"></i>
                Grid CCTV
              </a>
            <?php endif; ?>

            <?php if ((int) ($inventory['item_type_id'] ?? 0) === 4 && hasRole(['admin', 'compliance'])): ?>
              <a href="/compliance/checklist/emergency-light-grid?ym=<?= esc($ym) ?>&focus_id=<?= (int) $inventory['id'] ?>" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-grid-3x3-gap"></i>
                Grid Emergency Light
              </a>
            <?php endif; ?>

            <?php if ((int) ($inventory['item_type_id'] ?? 0) === 10 && hasRole(['admin', 'compliance'])): ?>
              <a href="/compliance/checklist/first-aid-box-grid?ym=<?= esc($ym) ?>&focus_id=<?= (int) $inventory['id'] ?>" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-grid-3x3-gap"></i>
                Grid First Aid Box
              </a>
            <?php endif; ?>

            <a href="/compliance/checklist/<?= (int) $inventory['id'] ?>?ym=<?= esc($ym) ?>" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1">
              <i class="bi bi-clipboard-check"></i>
              Buka Ceklis
            </a>
        <?php endif; ?>

        <?php if (hasRole(['admin', 'compliance'])): ?>
          <?php
          $ym = date('Y-m');
          [$year, $month] = explode('-', $ym);
          ?>
          <a
            class="btn btn-danger btn-sm d-inline-flex align-items-center gap-1"
            target="_blank"
            href="<?= site_url('export/pdf/recap/' . $inventory['id'] . '/' . $year . '/' . $month) ?>">
            <i class="bi bi-file-earmark-pdf"></i>
            Export PDF
          </a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <div class="row g-3 mb-3">
    <div class="col-lg-4">
      <div class="card checklist-card h-100 no-lift">
        <div class="card-body text-center">
          <?php if ($inventory['photo']): ?>
            <img
              id="inventoryPhoto"
              src="<?= base_url('uploads/inventory/' . $inventory['photo']) ?>"
              class="img-fluid rounded mb-3 inventory-photo-preview"
              alt="Foto inventory"
              data-bs-toggle="modal"
              data-bs-target="#modalZoomPhoto">
          <?php else: ?>
            <div class="text-muted mb-3 py-4 border rounded-3 bg-light-subtle">
              <i class="bi bi-image fs-1"></i><br>
              Belum ada foto
            </div>
          <?php endif; ?>

          <?php if (hasRole(['admin', 'compliance'])): ?>
            <form
              action="<?= base_url('compliance/inventory/update-photo/' . $inventory['id']) ?>"
              method="post"
              enctype="multipart/form-data"
              class="text-start">

              <?= csrf_field() ?>

              <label for="inventoryPhotoInput" class="form-label small text-muted mb-1">Perbarui Foto</label>
              <input
                id="inventoryPhotoInput"
                type="file"
                name="photo"
                class="form-control form-control-sm mb-2"
                accept="image/*"
                capture="environment"
                required>

              <button class="btn btn-sm btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-1">
                <i class="bi bi-camera"></i>
                Ganti Foto
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card checklist-card h-100 no-lift">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">Informasi Inventory</h6>

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
                  <td><?= esc($inventory['area_name']) ?> - <?= esc($inventory['specific_area']) ?></td>
                </tr>
                <tr>
                  <th>PIC</th>
                  <td><?= esc($inventory['pic'] ?: '-') ?></td>
                </tr>
                <tr>
                  <th>Status</th>
                  <td><span class="badge <?= $statusClass ?>"><?= esc($statusLabel) ?></span></td>
                </tr>

                <?php if (!empty($inventory['expired_date'])): ?>
                  <tr>
                    <th>Tanggal Kedaluwarsa</th>
                    <td><?= esc($inventory['expired_date']) ?></td>
                  </tr>
                <?php endif; ?>

                <tr>
                  <th>Catatan</th>
                  <td><?= esc($inventory['remark'] ?: '-') ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (hasRole(['auditor', 'admin', 'compliance', 'staff'])): ?>
    <?= $this->include('compliance/inventory/_detail_month') ?>
  <?php endif; ?>
</div>

<?= $this->include('compliance/inventory/_modal_zoom') ?>

<script src="<?= base_url('js/inventory-detail.js') ?>"></script>
<script>
  function openChecklistZoom(imageUrl) {
    const image = document.getElementById('zoomChecklistImage');
    const modalElement = document.getElementById('modalZoomChecklist');

    if (!image || !modalElement) {
      return;
    }

    image.src = imageUrl;
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
  }

  document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('modalZoomChecklist');
    const image = document.getElementById('zoomChecklistImage');

    if (!modalElement || !image) {
      return;
    }

    modalElement.addEventListener('hidden.bs.modal', function () {
      image.src = '';
    });
  });
</script>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/inventory-detail.css?v=' . filemtime(FCPATH . 'assets/css/inventory-detail.css')) ?>">
<?= $this->endSection() ?>
