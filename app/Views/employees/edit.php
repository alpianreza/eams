<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Edit Karyawan</h4>

<form method="post"
      action="<?= base_url('employees/update/'.$employee['id']) ?>"
      enctype="multipart/form-data">

    <input type="hidden" name="old_photo" value="<?= esc($employee['photo']) ?>">

    <div class="mb-3">
        <label>ID Karyawan</label>
        <input type="text" name="employee_id"
               class="form-control"
               value="<?= esc($employee['employee_id']) ?>" required>
    </div>

    <div class="mb-3">
        <label>Nama</label>
        <input type="text" name="name"
               class="form-control"
               value="<?= esc($employee['name']) ?>" required>
    </div>

    <div class="mb-3">
        <label>Divisi</label>
        <input type="text" name="division"
               class="form-control"
               value="<?= esc($employee['division']) ?>" required>
    </div>

    <div class="mb-3">
        <label>Jabatan</label>
        <input type="text" name="position"
               class="form-control"
               value="<?= esc($employee['position']) ?>" required>
    </div>

    <div class="mb-3">
        <label>Ganti Foto (opsional)</label><br>
        <?php if ($employee['photo']): ?>
            <img src="<?= base_url('uploads/employees/'.$employee['photo']) ?>"
                 width="100" class="img-thumbnail mb-2"><br>
        <?php endif; ?>
        <input type="file" name="photo" class="form-control" accept="image/*">
    </div>

    <button class="btn btn-success">Simpan</button>
    <a href="<?= base_url('employees/detail/'.$employee['id']) ?>"
       class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>
