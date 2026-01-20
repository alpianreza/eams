<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Compliance Inventory</h5>

  <?php if ($isWritable ?? false): ?>
    <button class="btn btn-primary btn-sm"
      data-bs-toggle="modal"
      data-bs-target="#modalAddInventory">
      + Tambah Inventory
    </button>
  <?php endif; ?>
</div>

<!-- FILTER KATEGORI -->
<div class="mb-3 d-flex gap-2 flex-wrap">
  <a a href="<?= base_url('compliance/inventory') ?>" class="btn btn-outline-primary btn-sm <?= empty($categories) ?> filter-btn active" data-filter="">
    Semua
  </a>
  <?php foreach ($categories as $cat): ?>
    <a href="<?= base_url('compliance/inventory?category=' . urlencode($cat['name'])) ?>"
      class="btn btn-sm <?= $category === $cat['name'] ? 'btn-primary' : 'btn-outline-primary' ?>">
      <?= esc($cat['name']) ?>
    </a>
  <?php endforeach; ?>
</div>
<!-- FILTER KATEGORI -->
<div class="mb-3 d-flex gap-2 flex-wrap">
  <a href="<?= base_url('compliance/inventory') ?>"
    class="btn btn-sm <?= empty($area) ? 'btn-secondary' : 'btn-outline-secondary' ?>">
    Semua
  </a>
  <?php foreach ($areas as $a): ?>
    <a href="<?= base_url('compliance/inventory?area=' . urlencode($a['name'])) ?>"
      class="btn btn-sm <?= $area === $a['name'] ? 'btn-secondary' : 'btn-outline-secondary' ?>">
      <?= esc($a['name']) ?>
    </a>
  <?php endforeach; ?>
</div>

<table id="inventoryTable" class="table table-striped">
  <thead class="table-light">
    <tr>
      <th>No</th>
      <th class="d-none">Kategori</th>
      <th class="d-none">Area</th>
      <th>Nama Item</th>
      <th>No Inventaris</th>
      <th>Tipe</th>
      <th>Area</th>
      <th>PIC</th>
      <th>Status</th>
      <th>Remark</th>
      <th width="120">Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $no = 1;
    foreach ($inventories as $inv): ?>
      <tr class="
  <?= $inv['status'] === 'Need Repair' ? 'table-warning' : '' ?>
  <?= $inv['status'] === 'Not Active' ? 'table-secondary' : '' ?>
">

        <td><?= $no++ ?></td>

        <!-- kategori (hidden, buat filter) -->
        <td class="d-none"><?= esc($inv['category_name']) ?></td>
        <td class="d-none"><?= esc($inv['area_name']) ?></td>
        <td>
          <a href="<?= base_url('compliance/inventory/detail/' . $inv['id']) ?>"
            class="text-decoration-none fw-semibold text-dark">
            <?= esc($inv['item_display_name']) ?>
        </td>
        <td><?= esc($inv['asset_code']) ?></td>
        <td><?= esc($inv['type_description'] ?? '-') ?></td>
        <td><?= esc($inv['specific_area']) ?></td>
        <td><?= esc($inv['pic'] ?? '-') ?></td>

        <td>
          <?php if ($inv['status'] === 'Good'): ?>
            <span class="badge bg-success">Good</span>
          <?php elseif ($inv['status'] === 'Need Repair'): ?>
            <span class="badge bg-warning text-dark">Need Repair</span>
          <?php elseif ($inv['status'] === 'Not Active'): ?>
            <span class="badge bg-secondary">Not Active</span>
          <?php else: ?>
            <span class="badge bg-light text-dark">-</span>
          <?php endif; ?>
        </td>


        <td><?= esc($inv['remark'] ?? '-') ?></td>

        <td class="d-flex gap-1">

          <!-- EDIT -->
          <button type="button"
            class="btn btn-sm btn-outline-warning btn-edit"
            data-id="<?= $inv['id'] ?>"
            data-category-id="<?= $inv['category_id'] ?>"
            data-item-type-id="<?= $inv['item_type_id'] ?>"
            data-area-id="<?= $inv['area_id'] ?>"
            data-code="<?= esc($inv['asset_code']) ?>"
            data-type="<?= esc($inv['type_description']) ?>"
            data-pic="<?= esc($inv['pic']) ?>"
            data-status="<?= esc($inv['status']) ?>"
            data-remark="<?= esc($inv['remark']) ?>">
            Edit
          </button>

          <!-- DELETE -->
          <form action="<?= base_url('compliance/inventory/delete/' . $inv['id']) ?>"
            method="post"
            class="form-delete d-inline">
            <?= csrf_field() ?>
            <button type="button"
              class="btn btn-sm btn-outline-danger btn-delete">
              Delete
            </button>
          </form>

          <!-- QR -->
          <?php if (!empty($inv['qr_image'])): ?>
            <button type="button"
              class="btn btn-sm btn-outline-secondary btn-qr"
              data-qr="<?= base_url('uploads/qr/' . $inv['qr_image']) ?>">
              QR
            </button>
          <?php endif; ?>

        </td>

      </tr>

    <?php endforeach; ?>
  </tbody>
</table>

<?php
$start = (($pager->getCurrentPage() - 1) * $pager->getPerPage()) + 1;
$end   = min(
  $pager->getCurrentPage() * $pager->getPerPage(),
  $pager->getTotal()
);
?>

<div class="d-flex justify-content-between align-items-center mt-3">
  <div class="text-muted small">
    Showing <?= $start ?> to <?= $end ?> of <?= $pager->getTotal() ?> entries
  </div>

  <div>
    <?= $pager->links() ?>
  </div>
</div>


<?= $this->include('compliance/inventory/_modal_add') ?>

<?= $this->include('compliance/inventory/_modal_qr') ?>

<?= $this->include('compliance/inventory/_modal_edit') ?>


<script>
  document.addEventListener('DOMContentLoaded', function() {

    document.querySelectorAll('.btn-qr').forEach(function(btn) {
      btn.addEventListener('click', function() {

        const qrUrl = this.getAttribute('data-qr');
        const img = document.getElementById('qrImage');
        img.src = qrUrl;

        const modal = new bootstrap.Modal(
          document.getElementById('modalQr')
        );
        modal.show();

      });
    });

  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {

    // OPEN MODAL + ISI DATA
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', function() {

        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_category_id').value = this.dataset.categoryId;
        document.getElementById('edit_area_id').value = this.dataset.areaId;

        document.getElementById('edit_item').value = this.dataset.item || '';
        document.getElementById('edit_code').value = this.dataset.code || '';
        document.getElementById('edit_type').value = this.dataset.type || '';
        document.getElementById('edit_pic').value = this.dataset.pic || '';
        document.getElementById('edit_status').value = this.dataset.status || '';
        document.getElementById('edit_remark').value = this.dataset.remark || '';

        new bootstrap.Modal(
          document.getElementById('modalEditInventory')
        ).show();
      });
    });


    // SUBMIT VIA AJAX
    document.getElementById('formEditInventory').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const id = formData.get('id');

      fetch('<?= base_url('compliance/inventory/update') ?>/' + id, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(res => res.json())
        .then(res => {
          if (res.status === 'success') {
            location.reload(); // sementara reload biar aman
          } else {
            alert(res.message || 'Gagal update data');
          }
        });
    });

  });
</script>

<script>
  document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    const itemSelect = document.getElementById('item_type_id');

    itemSelect.innerHTML = '<option value="">Loading...</option>';

    if (!categoryId) {
      itemSelect.innerHTML = '<option value="">-- pilih item --</option>';
      return;
    }

    fetch('<?= base_url('compliance/inventory/item-types') ?>/' + categoryId)
      .then(res => res.json())
      .then(data => {
        itemSelect.innerHTML = '<option value="">-- pilih item --</option>';
        data.forEach(item => {
          itemSelect.innerHTML += `
          <option value="${item.id}">
            ${item.name}
          </option>`;
        });
      });
  });
</script>


<?= $this->endSection() ?>