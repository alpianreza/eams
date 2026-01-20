<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>


<div class="card shadow-sm border-0">
  <div class="card-body">

    <h5 class="mb-3">Tambah Inventory Compliance</h5>

    <form method="post"
      action="<?= base_url('compliance/inventory/store') ?>"
      enctype="multipart/form-data">


      <div class="mb-3">
        <label class="form-label">Kategori</label>
        <select name="category" class="form-select" required>
          <option value="">-- pilih --</option>
          <option value="Fire Safety">Fire Safety</option>
          <option value="CTPAT">CTPAT</option>
          <option value="Health & Safety">Health & Safety</option>
          <option value="Maintenance">Maintenance</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Jenis Asset</label>
        <input type="text"
          name="asset_type"
          class="form-control"
          placeholder="APAR, CCTV, Hydrant"
          required>
      </div>

      <div class="mb-3">
        <label class="form-label">Kode Asset</label>
        <input type="text"
          name="asset_code"
          class="form-control"
          placeholder="APAR-001"
          required>
      </div>

      <div class="mb-3">
        <label class="form-label">Lokasi</label>
        <input type="text"
          name="location"
          class="form-control"
          required>
      </div>

      <div class="mb-3">
        <label class="form-label">Foto Inventory</label>
        <input type="file"
          name="photo"
          accept="image/*"
          class="form-control">
      </div>
      

      <button class="btn btn-primary">
        Simpan Inventory
      </button>

    </form>

  </div>
</div>

<?= $this->endSection() ?>