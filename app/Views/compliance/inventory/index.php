<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- ================= HEADER ================= -->
<div class="inventory-header mb-3">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <h5 class="mb-0">Compliance Inventory</h5>
      <small class="text-muted">Compliance & Facility Assets</small>
    </div>

    <?php if ($isWritable ?? false): ?>
      <button class="btn btn-primary btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#modalAddInventory">
        <i class="bi bi-plus-lg"></i>
      </button>
    <?php endif; ?>
  </div>

  <!-- MOBILE SEARCH -->
  <div class="inventory-search mt-2">
    <input type="text"
      id="mobileSearch"
      class="form-control form-control-sm"
      placeholder="Cari inventory...">
  </div>

  <!-- FILTER -->
  <div class="d-flex gap-2 mt-2 flex-wrap">
    <select id="filterCategory" class="form-select form-select-sm">
      <option value="">Semua Kategori</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= esc($cat['name']) ?>"
          <?= $category === $cat['name'] ? 'selected' : '' ?>>
          <?= esc($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select id="filterArea" class="form-select form-select-sm">
      <option value="">Semua Area</option>
      <?php foreach ($areas as $a): ?>
        <option value="<?= esc($a['name']) ?>"
          <?= $area === $a['name'] ? 'selected' : '' ?>>
          <?= esc($a['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- ================= DESKTOP INVENTORY ================= -->
<div class="inventory-desktop">

  <table id="inventoryTable" class="table table-striped align-middle">
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
        <th width="120" class="text-center">Aksi</th>
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

          <!-- hidden (untuk JS) -->
          <td class="d-none col-category"><?= esc($inv['category_name']) ?></td>
          <td class="d-none col-area"><?= esc($inv['area_name']) ?></td>

          <td class="col-item">
            <a href="<?= base_url('compliance/inventory/detail/' . $inv['id']) ?>"
              class="fw-semibold text-dark text-decoration-none">
              <?= esc($inv['item_display_name']) ?>
            </a>
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

          <td class="text-center">
            <div class="d-flex justify-content-center gap-1">

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
                data-remark="<?= esc($inv['remark']) ?>"
                title="Edit">
                <i class="bi bi-pencil-square"></i>
              </button>

              <!-- DELETE -->
              <form action="<?= base_url('compliance/inventory/delete/' . $inv['id']) ?>"
                method="post" class="d-inline form-delete">
                <?= csrf_field() ?>
                <button type="button"
                  class="btn btn-sm btn-outline-danger btn-delete"
                  title="Delete">
                  <i class="bi bi-trash"></i>
                </button>
              </form>

              <!-- QR -->
              <?php if (!empty($inv['qr_image'])): ?>
                <button type="button"
                  class="btn btn-sm btn-outline-secondary btn-qr"
                  data-qr="<?= base_url('uploads/qr/' . $inv['qr_image']) ?>"
                  title="QR Code">
                  <i class="bi bi-qr-code"></i>
                </button>
              <?php endif; ?>

            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ================= MOBILE INVENTORY ================= -->
<div class="inventory-mobile">

  <?php foreach ($inventories as $inv): ?>
    <div class="card mb-3 shadow-sm inventory-card"
      onclick="window.location.href='<?= base_url('compliance/inventory/detail/' . $inv['id']) ?>'">

      <div class="card-body">

        <div class="fw-semibold mb-1">
          <?= esc($inv['item_display_name']) ?>
        </div>

        <div class="text-muted small mb-2">
          <?= esc($inv['asset_code']) ?>
        </div>

        <div class="small"><b>Tipe:</b> <?= esc($inv['type_description'] ?? '-') ?></div>
        <div class="small"><b>Area:</b> <?= esc($inv['specific_area']) ?></div>
        <div class="small"><b>PIC:</b> <?= esc($inv['pic'] ?? '-') ?></div>

        <div class="mt-2 d-flex justify-content-between align-items-center">
          <div>
            <?php if ($inv['status'] === 'Good'): ?>
              <span class="badge bg-success">Good</span>
            <?php elseif ($inv['status'] === 'Need Repair'): ?>
              <span class="badge bg-warning text-dark">Need Repair</span>
            <?php else: ?>
              <span class="badge bg-secondary">Not Active</span>
            <?php endif; ?>
          </div>

          <i class="bi bi-chevron-right text-muted"></i>
        </div>

      </div>
    </div>
  <?php endforeach; ?>

</div>



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
  const BASE_URL = "<?= base_url() ?>";
</script>


<?= $this->section('scripts') ?>
<script src="<?= base_url('js/inventory.js') ?>"></script>
<?= $this->endSection() ?>



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

<script>
  document.addEventListener("DOMContentLoaded", function() {

    const filterCategory = document.getElementById("filterCategory");
    const filterArea = document.getElementById("filterArea");

    if (!filterCategory && !filterArea) return;

    function applyFilter() {
      const params = new URLSearchParams();

      if (filterCategory && filterCategory.value) {
        params.set("category", filterCategory.value);
      }

      if (filterArea && filterArea.value) {
        params.set("area", filterArea.value);
      }

      window.location.href =
        "<?= base_url('compliance/inventory') ?>" +
        (params.toString() ? "?" + params.toString() : "");
    }

    if (filterCategory) {
      filterCategory.addEventListener("change", applyFilter);
    }

    if (filterArea) {
      filterArea.addEventListener("change", applyFilter);
    }

  });
</script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('[title]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  });
</script>

<!-- MOBILE SEARCH SCRIPT -->

<script>
  document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("mobileSearch");
    if (!searchInput) return;

    searchInput.addEventListener("input", function() {
      const keyword = this.value.toLowerCase();

      document.querySelectorAll(".inventory-card").forEach(card => {
        const text = card.innerText.toLowerCase();
        card.style.display = text.includes(keyword) ? "" : "none";
      });
    });
  });
</script>


<?= $this->endSection() ?>