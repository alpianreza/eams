<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="inventory-page compliance-inventory-page">
  <section class="card border-0 shadow-sm inventory-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="inventory-kicker mb-1">Compliance Inventory</p>
        <h5 class="mb-1 fw-bold">Daftar Aset Compliance & Fasilitas</h5>
        <p class="text-muted mb-0">Kelola aset, area, PIC, dan status kondisi dalam satu halaman.</p>
      </div>

      <?php if (hasRole(['admin', 'compliance'])): ?>
        <div class="inventory-hero-actions ms-auto">
          <button
            class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 inventory-hero-btn"
            data-bs-toggle="modal"
            data-bs-target="#modalAddInventory">
            <i class="bi bi-plus-lg"></i>
            Tambah Aset
          </button>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="card border-0 shadow-sm inventory-filter-card no-lift mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3 col-xl-2">
          <label for="filterCategory" class="form-label form-label-sm mb-1">Kategori</label>
          <select id="filterCategory" class="form-select form-select-sm w-100">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                <?= esc($cat['name']) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
          <label for="filterArea" class="form-label form-label-sm mb-1">Area</label>
          <select id="filterArea" class="form-select form-select-sm w-100">
            <option value="">Semua Area</option>
            <?php foreach ($areas as $a): ?>
              <option value="<?= $a['id'] ?>" <?= $area == $a['id'] ? 'selected' : '' ?>>
                <?= esc($a['name']) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>

        <div class="col-12 col-md-4 col-xl-4">
          <label for="searchInput" class="form-label form-label-sm mb-1">Pencarian</label>
          <input
            type="text"
            id="searchInput"
            class="form-control form-control-sm"
            placeholder="Cari item, kode inventaris, atau PIC..."
            value="<?= esc($keyword) ?>">
        </div>

        <div class="col-6 col-md-2 col-xl-2">
          <label for="filterPerPage" class="form-label form-label-sm mb-1">Data / halaman</label>
          <select id="filterPerPage" class="form-select form-select-sm w-100">
            <option value="10" <?= (string)$perPage === '10' ? 'selected' : '' ?>>10</option>
            <option value="20" <?= (string)$perPage === '20' ? 'selected' : '' ?>>20</option>
            <option value="50" <?= (string)$perPage === '50' ? 'selected' : '' ?>>50</option>
            <option value="100" <?= (string)$perPage === '100' ? 'selected' : '' ?>>100</option>
          </select>
        </div>

        <div class="col-6 col-md-12 col-xl-2 d-flex justify-content-md-end justify-content-xl-start">
          <button
            id="btnResetFilter"
            class="btn btn-outline-danger btn-sm w-100 d-none">
            Reset Filter
          </button>
        </div>
      </div>
    </div>
  </section>

  <div id="inventoryAjax" class="inventory-ajax-shell position-relative">
    <div id="inventorySkeleton" class="d-none">
      <table class="table align-middle mb-0">
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

    <div class="inventory-pagination-wrap mt-3">
      <?= $this->include('compliance/inventory/_pagination') ?>
    </div>
  </div>

  <?= $this->include('compliance/inventory/_modal_add') ?>
  <?= $this->include('compliance/inventory/_modal_qr') ?>
  <?= $this->include('compliance/inventory/_modal_edit') ?>
</div>

<script>
  const BASE_URL = "<?= rtrim(base_url(), '/') ?>";
</script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/inventory.js?v=' . filemtime(FCPATH . 'js/inventory.js')) ?>"></script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const toRelative = (rawUrl) => {
      const parsed = new URL(rawUrl, window.location.origin);
      return parsed.pathname + parsed.search;
    };

    const categorySelect = document.getElementById("category_id");
    const itemSelect = document.getElementById("item_type_id");

    if (categorySelect && itemSelect) {
      categorySelect.addEventListener("change", function () {
        const categoryId = this.value;
        itemSelect.innerHTML = '<option value="">Memuat data...</option>';

        if (!categoryId) {
          itemSelect.innerHTML = '<option value="">-- pilih item --</option>';
          return;
        }

        fetch(toRelative(`${BASE_URL}/compliance/inventory/item-types/${categoryId}`))
          .then((res) => res.json())
          .then((data) => {
            itemSelect.innerHTML = '<option value="">-- pilih item --</option>';
            data.forEach((item) => {
              itemSelect.innerHTML += `<option value="${item.id}">${item.name}</option>`;
            });
          })
          .catch(() => {
            itemSelect.innerHTML = '<option value="">-- gagal memuat item --</option>';
          });
      });
    }

    document.querySelectorAll("[title]").forEach((el) => {
      new bootstrap.Tooltip(el);
    });
  });
</script>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/inventory-detail.css?v=' . filemtime(FCPATH . 'assets/css/inventory-detail.css')) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/inventory-mobile.css?v=' . filemtime(FCPATH . 'assets/css/inventory-mobile.css')) ?>">
<?= $this->endSection() ?>
