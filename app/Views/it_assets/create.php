<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Tambah Asset IT</h4>

<form method="post"
      action="<?= base_url('it-assets/store') ?>"
      enctype="multipart/form-data">

    <div class="mb-3">
        <label>No Inventaris</label>
        <input type="text" name="inventory_no" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Kategori</label>
        <select name="category_id" class="form-select" required>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>">
                    <?= esc($c['sub_category']) ?>
                </option>
            <?php endforeach ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Nama Asset</label>
        <input type="text" name="asset_name" class="form-control" required>
    </div>

    <div class="mb-3">
    <label>Tanggal Beli (opsional)</label>
    <input type="date" name="purchase_date" class="form-control">
    </div>

    <div class="mb-3">
        <label>Brand</label>
        <input type="text" name="brand" class="form-control">
    </div>

    <div class="mb-3">
        <label>Serial Number</label>
        <input type="text" name="serial_number" class="form-control">
    </div>

    <div class="mb-3">
        <label>Foto Asset</label>
        <input type="file" name="photo" class="form-control" accept="image/*">
    </div>

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-select">
            <option value="aktif">Aktif</option>
            <option value="rusak">Rusak</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Lokasi</label>
        <input type="text" name="location" class="form-control">
    </div>

    <button class="btn btn-success">Simpan</button>
</form>

<?= $this->endSection() ?>
