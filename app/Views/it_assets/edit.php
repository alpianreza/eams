<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Edit Asset IT</h4>

<form method="post"
  action="<?= base_url('it-assets/update/' . $asset['id']) ?>"
  enctype="multipart/form-data">

  <input type="hidden" name="old_photo" value="<?= esc($asset['photo']) ?>">

  <div class="mb-3">
    <label>No Inventaris</label>
    <input type="text" name="inventory_no"
      class="form-control"
      value="<?= esc($asset['inventory_no']) ?>" required>
  </div>

  <div class="mb-3">
    <label>Kategori</label>
    <select name="category_id" class="form-select" required>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>"
          <?= $asset['category_id'] == $c['id'] ? 'selected' : '' ?>>
          <?= esc($c['sub_category']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="mb-3">
    <label>Nama Asset</label>
    <input type="text" name="asset_name"
      class="form-control"
      value="<?= esc($asset['asset_name']) ?>" required>
  </div>

  <div class="mb-3">
    <label>Brand</label>
    <input type="text" name="brand"
      class="form-control"
      value="<?= esc($asset['brand']) ?>">
  </div>

  <div class="mb-3">
    <label>Serial Number</label>
    <input type="text" name="serial_number"
      class="form-control"
      value="<?= esc($asset['serial_number']) ?>">
  </div>

  <div class="mb-3">
    <label>Tanggal Beli</label>
    <input type="date" name="purchase_date"
      class="form-control"
      value="<?= esc($asset['purchase_date']) ?>">
  </div>

  <div class="mb-3">
    <label>Foto Asset</label><br>
    <?php if ($asset['photo']): ?>
      <img src="<?= base_url('uploads/assets/' . $asset['photo']) ?>"
        width="120"
        class="img-thumbnail mb-2"><br>
    <?php endif; ?>
    <input type="file" name="photo" class="form-control" accept="image/*">
  </div>

  <div class="mb-3">
    <label>Status</label>
    <select name="status" class="form-select">
      <option value="aktif" <?= $asset['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
      <option value="rusak" <?= $asset['status'] == 'rusak' ? 'selected' : '' ?>>Rusak</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Lokasi</label>
    <input type="text" name="location"
      class="form-control"
      value="<?= esc($asset['location']) ?>">
  </div>

  <button class="btn btn-success">Update</button>
  <a href="<?= base_url('it-assets/detail/' . $asset['id']) ?>"
    class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>