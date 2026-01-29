<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

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

  <!-- FILTER BAR -->
  <div class="d-flex gap-2 flex-wrap mt-2">
    <select id="filterCategory" class="form-select form-select-sm w-auto">
      <option value="">Semua Kategori</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
          <?= esc($cat['name']) ?>
        </option>
      <?php endforeach ?>
    </select>

    <select id="filterArea" class="form-select form-select-sm w-auto">
      <option value="">Semua Area</option>
      <?php foreach ($areas as $a): ?>
        <option value="<?= $a['id'] ?>" <?= $area == $a['id'] ? 'selected' : '' ?>>
          <?= esc($a['name']) ?>
        </option>
      <?php endforeach ?>
    </select>

    <!-- DESKTOP SEARCH -->
    <input type="text"
      id="searchInput"
      class="form-control form-control-sm"
      style="max-width:240px"
      placeholder="Cari inventory..."
      value="<?= esc($keyword) ?>">

    <button id="btnResetFilter" class="btn btn-outline-danger btn-sm d-none">
      Reset
    </button>
  </div>

</div>

<div id="inventoryAjax">

  <!-- SKELETON -->
  <div id="inventorySkeleton" class="d-none">
    <table class="table align-middle">
      <tbody>
        <?php for ($i = 0; $i < 6; $i++): ?>
          <tr>
            <td colspan="11">
              <div class="skeleton-row"></div>
            </td>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>

  <?= $this->include('compliance/inventory/_table') ?>
  <?= $this->include('compliance/inventory/_pagination') ?>

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


<?= $this->endSection() ?>