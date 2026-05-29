<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
  .thermal-form-page .thermal-row-card {
    border: 1px solid rgba(15, 23, 42, .12);
    border-radius: 16px;
    background: #fff;
  }

  .thermal-form-page .thermal-row-number {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eef2ff;
    color: #3730a3;
    font-weight: 700;
  }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$locationOptions = '';
foreach ($locations as $location) {
  $label = trim((string) ($location['section'] ?? '')) !== ''
    ? $location['section'] . ' - ' . $location['name']
    : $location['name'];
  $locationOptions .= '<option value="' . esc((string) $location['id'], 'attr') . '">' . esc($label) . '</option>';
}
?>

<div class="thermal-form-page">
  <form action="/compliance/thermal-imaging/store" method="post" enctype="multipart/form-data" id="thermalReportForm">
    <?= csrf_field() ?>

    <section class="card no-lift mb-3">
      <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
          <p class="text-uppercase text-muted small fw-bold mb-1">Thermal Imaging</p>
          <h5 class="mb-1 fw-bold">Buat Inspection Report</h5>
          <p class="text-muted mb-0">User pilih lokasi dan isi Celsius; admin/compliance bisa tambah lokasi baru.</p>
        </div>
        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
          <i class="bi bi-save"></i>
          Simpan Report
        </button>
      </div>
    </section>

    <section class="card no-lift mb-3">
      <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold">General Information</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label">Inspection Date</label>
            <input type="date" name="inspection_date" class="form-control" value="<?= esc(old('inspection_date') ?: date('Y-m-d')) ?>" required>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Inspector Name</label>
            <input type="text" name="inspector_name" class="form-control" value="<?= esc(old('inspector_name') ?: $defaultInspector) ?>" required>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Facility</label>
            <input type="text" name="facility" class="form-control" value="<?= esc(old('facility') ?: $defaultFacility) ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Inspection Area</label>
            <input type="text" name="area_name" class="form-control" value="<?= esc(old('area_name') ?: $defaultArea) ?>" required>
          </div>
        </div>
      </div>
    </section>

    <section class="card no-lift">
      <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h6 class="mb-0 fw-bold">Inspection Report</h6>
          <small class="text-muted">Tambahkan baris sesuai jumlah thermal image yang dicek.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <?php if ($canManageLocations): ?>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#locationModal">
              <i class="bi bi-geo-alt"></i>
              Tambah Lokasi
            </button>
          <?php endif; ?>
          <button type="button" class="btn btn-outline-primary btn-sm" id="addThermalRow">
            <i class="bi bi-plus-lg"></i>
            Tambah Baris
          </button>
        </div>
      </div>
      <div class="card-body">
        <?php if (empty($locations)): ?>
          <div class="alert alert-warning">
            Belum ada master lokasi. Admin/compliance perlu menambah lokasi dulu sebelum user mengisi report.
          </div>
        <?php endif; ?>

        <div id="thermalRows" class="d-grid gap-3">
          <div class="thermal-row-card p-3" data-row>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="thermal-row-number" data-row-number>1</span>
              <button type="button" class="btn btn-outline-danger btn-sm d-none" data-remove-row>
                <i class="bi bi-trash"></i>
              </button>
            </div>
            <div class="row g-3">
              <div class="col-12 col-lg-4">
                <label class="form-label">Thermal Image</label>
                <input type="file" name="thermal_images[]" class="form-control" accept="image/*">
              </div>
              <div class="col-12 col-lg-4">
                <label class="form-label">Location</label>
                <select name="location_id[]" class="form-select" required>
                  <option value="">Pilih Lokasi</option>
                  <?= $locationOptions ?>
                </select>
              </div>
              <div class="col-12 col-lg-4">
                <label class="form-label">Celsius (°C)</label>
                <input type="number" name="celsius[]" class="form-control" min="0" step="0.1" placeholder="31.5" required>
              </div>
              <div class="col-12 col-lg-6">
                <label class="form-label">Findings</label>
                <textarea name="findings[]" class="form-control" rows="2" placeholder="Isi jika ada temuan"></textarea>
              </div>
              <div class="col-12 col-lg-6">
                <label class="form-label">Recommendation</label>
                <textarea name="recommendation[]" class="form-control" rows="2" placeholder="Isi rekomendasi jika diperlukan"></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </form>
</div>

<?php if ($canManageLocations): ?>
  <div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="locationForm">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Tambah Lokasi Thermal</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Section / Area</label>
            <input type="text" name="section" class="form-control" placeholder="Contoh: Main Building">
          </div>
          <div>
            <label class="form-label">Nama Lokasi</label>
            <input type="text" name="name" class="form-control" placeholder="Contoh: PP Warehouse & Cutting" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Lokasi</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<template id="thermalRowTemplate">
  <div class="thermal-row-card p-3" data-row>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <span class="thermal-row-number" data-row-number>1</span>
      <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>
        <i class="bi bi-trash"></i>
      </button>
    </div>
    <div class="row g-3">
      <div class="col-12 col-lg-4">
        <label class="form-label">Thermal Image</label>
        <input type="file" name="thermal_images[]" class="form-control" accept="image/*">
      </div>
      <div class="col-12 col-lg-4">
        <label class="form-label">Location</label>
        <select name="location_id[]" class="form-select" required>
          <option value="">Pilih Lokasi</option>
          <?= $locationOptions ?>
        </select>
      </div>
      <div class="col-12 col-lg-4">
        <label class="form-label">Celsius (°C)</label>
        <input type="number" name="celsius[]" class="form-control" min="0" step="0.1" placeholder="31.5" required>
      </div>
      <div class="col-12 col-lg-6">
        <label class="form-label">Findings</label>
        <textarea name="findings[]" class="form-control" rows="2" placeholder="Isi jika ada temuan"></textarea>
      </div>
      <div class="col-12 col-lg-6">
        <label class="form-label">Recommendation</label>
        <textarea name="recommendation[]" class="form-control" rows="2" placeholder="Isi rekomendasi jika diperlukan"></textarea>
      </div>
    </div>
  </div>
</template>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const rows = document.getElementById('thermalRows');
    const template = document.getElementById('thermalRowTemplate');
    const addButton = document.getElementById('addThermalRow');

    const renumber = () => {
      rows.querySelectorAll('[data-row]').forEach((row, index) => {
        row.querySelector('[data-row-number]').textContent = index + 1;
        const removeButton = row.querySelector('[data-remove-row]');
        if (removeButton) {
          removeButton.classList.toggle('d-none', rows.querySelectorAll('[data-row]').length === 1);
        }
      });
    };

    addButton?.addEventListener('click', () => {
      rows.appendChild(template.content.cloneNode(true));
      renumber();
    });

    rows?.addEventListener('click', (event) => {
      const button = event.target.closest('[data-remove-row]');
      if (!button) return;
      button.closest('[data-row]')?.remove();
      renumber();
    });

    const locationForm = document.getElementById('locationForm');
    locationForm?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(locationForm);

      try {
        const response = await fetch('/compliance/thermal-imaging/locations/store', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const result = await response.json();
        if (result.csrf?.name && result.csrf?.hash) {
          document.querySelectorAll(`input[name="${result.csrf.name}"]`).forEach((input) => {
            input.value = result.csrf.hash;
          });
        }

        if (!response.ok || result.status !== 'success') {
          throw new Error(result.message || 'Lokasi gagal disimpan.');
        }

        const location = result.location;
        const label = location.section ? `${location.section} - ${location.name}` : location.name;
        document.querySelectorAll('select[name="location_id[]"]').forEach((select) => {
          if (!select.querySelector(`option[value="${location.id}"]`)) {
            select.insertAdjacentHTML('beforeend', `<option value="${location.id}"></option>`);
            select.lastElementChild.textContent = label;
          }
          select.value = location.id;
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('locationModal')).hide();
        locationForm.reset();
        if (window.safeToast) safeToast('Lokasi berhasil ditambahkan.', 'success');
      } catch (error) {
        if (window.safeToast) {
          safeToast(error.message, 'error');
        } else {
          alert(error.message);
        }
      }
    });

    renumber();
  });
</script>
<?= $this->endSection() ?>
