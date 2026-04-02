<?php
$employee = $employee ?? null;
$formErrors = $formErrors ?? (session('form_errors') ?? []);
$currentPhoto = old('old_photo', $employee['photo'] ?? '');
?>

<?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger border-0 shadow-sm employee-form-alert" role="alert">
        <strong>Periksa lagi data yang diisi.</strong>
        <ul class="mb-0 mt-2 ps-3">
            <?php foreach ($formErrors as $message): ?>
                <li><?= esc($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($employee): ?>
    <input type="hidden" name="old_photo" value="<?= esc($employee['photo'] ?? '') ?>">
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm employee-form-card">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">ID Karyawan</label>
                        <input
                            type="text"
                            name="employee_id"
                            class="form-control<?= isset($formErrors['employee_id']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('employee_id', $employee['employee_id'] ?? '')) ?>"
                            placeholder="Contoh: YHS-001"
                            required>
                        <?php if (isset($formErrors['employee_id'])): ?>
                            <div class="invalid-feedback"><?= esc($formErrors['employee_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control<?= isset($formErrors['name']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('name', $employee['name'] ?? '')) ?>"
                            placeholder="Masukkan nama pemegang IT"
                            required>
                        <?php if (isset($formErrors['name'])): ?>
                            <div class="invalid-feedback"><?= esc($formErrors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Divisi</label>
                        <input
                            type="text"
                            name="division"
                            class="form-control<?= isset($formErrors['division']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('division', $employee['division'] ?? '')) ?>"
                            placeholder="Contoh: IT Support"
                            required>
                        <?php if (isset($formErrors['division'])): ?>
                            <div class="invalid-feedback"><?= esc($formErrors['division']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Jabatan</label>
                        <input
                            type="text"
                            name="position"
                            class="form-control<?= isset($formErrors['position']) ? ' is-invalid' : '' ?>"
                            value="<?= esc(old('position', $employee['position'] ?? '')) ?>"
                            placeholder="Contoh: Staff IT"
                            required>
                        <?php if (isset($formErrors['position'])): ?>
                            <div class="invalid-feedback"><?= esc($formErrors['position']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm employee-form-card h-100">
            <div class="card-body p-4">
                <label class="form-label">Foto Pemegang IT</label>

                <div class="employee-photo-preview mb-3">
                    <?php if ($currentPhoto !== ''): ?>
                        <img src="<?= base_url('uploads/employees/' . $currentPhoto) ?>" alt="Foto Pemegang IT">
                    <?php else: ?>
                        <div class="employee-photo-placeholder">
                            <span>Belum ada foto</span>
                        </div>
                    <?php endif; ?>
                </div>

                <input
                    type="file"
                    name="photo"
                    class="form-control<?= isset($formErrors['photo']) ? ' is-invalid' : '' ?>"
                    accept="image/jpeg,image/png,image/webp">
                <?php if (isset($formErrors['photo'])): ?>
                    <div class="invalid-feedback d-block"><?= esc($formErrors['photo']) ?></div>
                <?php endif; ?>

                <div class="form-text mt-2">
                    Format yang didukung: JPG, PNG, atau WEBP. Ukuran maksimal 2 MB.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check2-circle me-1"></i>
        Simpan
    </button>
    <a href="<?= $employee ? base_url('employees/detail/' . $employee['id']) : base_url('employees') ?>" class="btn btn-outline-secondary px-4">
        Batal
    </a>
</div>
