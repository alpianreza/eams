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
  <div class="inventory-search mt-2 d-md-none">
    <input type="text"
      id="mobileSearch"
      class="form-control form-control-sm"
      placeholder="Cari inventory...">
  </div>

  <!-- FILTER -->
  <!-- FILTER DESKTOP -->
  <div class="inventory-filters d-none d-md-flex align-items-center gap-2 mt-2">

    <select id="filterCategory" class="form-select form-select-sm filter-select">
      <option value="">Semua Kategori</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>"
          <?= ($category == $cat['id']) ? 'selected' : '' ?>>
          <?= esc($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select id="filterArea" class="form-select form-select-sm filter-select">
      <option value="">Semua Area</option>
      <?php foreach ($areas as $a): ?>
        <option value="<?= $a['id'] ?>"
          <?= ($area == $a['id']) ? 'selected' : '' ?>>
          <?= esc($a['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button
      id="btnResetFilter"
      class="btn btn-outline-danger btn-sm d-none">
      Reset
    </button>


  </div>
</div>


<!-- AJAX CONTAINER -->
<div id="inventoryAjax">

  <?= $this->include('compliance/inventory/_table') ?>

  <!-- PAGINATION -->
  <?php
  $start = (($pager->getCurrentPage() - 1) * $pager->getPerPage()) + 1;
  $end   = min(
    $pager->getCurrentPage() * $pager->getPerPage(),
    $pager->getTotal()
  );
  ?>
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">

    <div class="text-muted small">
      Showing <?= $start ?> to <?= $end ?> of <?= $pager->getTotal() ?> entries
    </div>

    <div class="pagination-wrapper">
      <?= $pager->links() ?>
    </div>

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