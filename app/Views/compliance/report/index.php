<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="card shadow-sm border-0 rounded-3">
  <div class="card-body py-3">

    <div class="row g-3 align-items-end">

      <div class="col-md-3">
        <label class="form-label fw-semibold mb-1">Kategori</label>
        <select id="categorySelect" class="form-select form-select-sm">
          <option value="">Pilih Kategori</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>">
              <?= esc($cat['name']) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label fw-semibold mb-1">Nama Item</label>
        <select id="itemTypeSelect" class="form-select form-select-sm" disabled>
          <option value="">Pilih Item</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label fw-semibold mb-1">No Inventory</label>
        <select id="inventorySelect" class="form-select form-select-sm" disabled>
          <option value="">Pilih No</option>
        </select>
      </div>

      <div class="col-md-1">
        <label class="form-label fw-semibold mb-1">Tahun</label>
        <select id="yearSelect" class="form-select form-select-sm">
          <?php for ($y = 2026; $y <= date('Y'); $y++): ?>
            <option value="<?= $y ?>"><?= $y ?></option>
          <?php endfor ?>
        </select>
      </div>

      <!-- BULAN -->
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">Bulan</label>
        <select id="monthSelect" class="form-select">

          <?php
          $bulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
          ];
          ?>

          <?php foreach ($bulan as $num => $nama): ?>
            <option value="<?= $num ?>">
              <?= $nama ?>
            </option>
          <?php endforeach; ?>

        </select>
      </div>

      <div class="col-md-1">
        <button id="loadReport"
          class="btn btn-primary btn-sm w-100 d-flex align-items-center justify-content-center">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </div>

    </div>

    <div id="reportContainer" class="mt-4"></div>

  </div>
</div>

<!-- IMAGE PREVIEW MODAL -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark border-0">
      <div class="modal-body text-center p-2">
        <img id="previewImage" src="" class="img-fluid rounded">
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('click', function(e) {

    const img = e.target.closest('.img-preview');
    if (!img) return;

    const src = img.getAttribute('data-src');

    document.getElementById('previewImage').src = src;

    const modal = new bootstrap.Modal(
      document.getElementById('imagePreviewModal')
    );

    modal.show();
  });
</script>


<script>
  const categorySelect = document.getElementById('categorySelect');
  const itemTypeSelect = document.getElementById('itemTypeSelect');
  const inventorySelect = document.getElementById('inventorySelect');

  categorySelect.addEventListener('change', function() {

    fetch(`/compliance/report/item-by-category?category_id=${this.value}`)
      .then(res => res.json())
      .then(data => {
        let options = '<option value="">-- Nama Item --</option>';
        data.forEach(row => {
          options += `<option value="${row.id}">${row.name}</option>`;
        });
        itemTypeSelect.innerHTML = options;
        itemTypeSelect.disabled = false;
        inventorySelect.disabled = true;
      });

  });

  itemTypeSelect.addEventListener('change', function() {
    fetch(`/compliance/report/inventory-by-type?item_type_id=${this.value}`)
      .then(res => res.json())
      .then(data => {
        let options = '<option value="">-- Kode --</option>';
        data.forEach(row => {
          options += `<option value="${row.id}">${row.asset_code}</option>`;
        });
        inventorySelect.innerHTML = options;
        inventorySelect.disabled = false;
      });

  });

  document.getElementById('loadReport').addEventListener('click', function() {

    const inventory = inventorySelect.value;
    const year = document.getElementById('yearSelect').value;
    const month = document.getElementById('monthSelect').value;

    if (!inventory) return;

    loadReport(inventory, year, month);

  });

  function loadReport(inventory, year, month) {

    fetch(`/compliance/report/load?inventory_id=${inventory}&year=${year}&month=${month}`)
      .then(res => res.text())
      .then(html => {
        document.getElementById('reportContainer').innerHTML = html;
      });

  }

  document.addEventListener('click', function(e) {

    if (e.target.classList.contains('navInventory')) {

      const inventory = e.target.dataset.id;
      const year = document.getElementById('yearSelect').value;
      const month = document.getElementById('monthSelect').value;

      loadReport(inventory, year, month);
    }

  });
</script>




<?= $this->endSection() ?>