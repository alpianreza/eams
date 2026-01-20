<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Tambah Karyawan</h4>

<form method="post" action="<?= base_url('employees/store') ?>" enctype="multipart/form-data">


    <div class="mb-3">
        <label class="form-label">ID Karyawan</label>
        <input type="text" name="employee_id" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Divisi</label>
        <input type="text" name="division" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Jabatan</label>
        <input type="text" name="position" class="form-control" required>
    </div>

    <div class="mb-3">
    <label class="form-label">Foto Karyawan</label>
    <input type="file" name="photo" class="form-control" accept="image/*">
    <small class="text-muted">JPG / PNG, max 2MB</small>
    </div>

    <button class="btn btn-success">Simpan</button>
    <a href="<?= base_url('employees') ?>" class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>
