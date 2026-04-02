<?= $this->extend('layouts/main') ?>

<?php
$isActive = strtolower((string)($employee['status'] ?? 'inactive')) === 'active';
$canDelete = (int)($activeAssignments ?? 0) === 0 && (int)($assignmentHistory ?? 0) === 0;
?>

<?= $this->section('content') ?>
<div class="employee-page">
    <section class="card border-0 shadow-sm employee-hero-card employee-hero-card--soft mb-4">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="employee-detail-avatar">
                    <?php if (!empty($employee['photo'])): ?>
                        <img src="<?= base_url('uploads/employees/' . $employee['photo']) ?>" alt="<?= esc($employee['name']) ?>">
                    <?php else: ?>
                        <span><?= esc(strtoupper(substr((string)($employee['name'] ?? 'U'), 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="employee-kicker mb-2">Detail Pemegang IT</p>
                    <h4 class="fw-bold mb-1"><?= esc($employee['name'] ?? '-') ?></h4>
                    <p class="text-muted mb-0"><?= esc($employee['employee_id'] ?? '-') ?> • <?= esc($employee['division'] ?? '-') ?></p>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('employees/edit/' . $employee['id']) ?>" class="btn btn-primary">Edit Data</a>

                <form method="post" action="<?= base_url('employees/' . ($isActive ? 'deactivate' : 'activate') . '/' . $employee['id']) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-secondary">
                        <?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>
                    </button>
                </form>

                <form method="post" action="<?= base_url('employees/delete/' . $employee['id']) ?>" onsubmit="return confirm('Hapus pemegang IT ini? Data tanpa histori assignment akan dihapus permanen.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger" <?= $canDelete ? '' : 'disabled' ?>>
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm employee-form-card h-100">
                <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Informasi Utama</h5>
                    <div class="row g-3 employee-info-grid">
                        <div class="col-md-6">
                            <div class="employee-info-item">
                                <span class="employee-info-label">ID Karyawan</span>
                                <strong><?= esc($employee['employee_id'] ?? '-') ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="employee-info-item">
                                <span class="employee-info-label">Status</span>
                                <strong><?= $isActive ? 'Aktif' : 'Inaktif' ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="employee-info-item">
                                <span class="employee-info-label">Divisi</span>
                                <strong><?= esc($employee['division'] ?? '-') ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="employee-info-item">
                                <span class="employee-info-label">Jabatan</span>
                                <strong><?= esc($employee['position'] ?? '-') ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm employee-form-card h-100">
                <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3">Ringkasan Assignment</h5>
                    <div class="employee-summary-stack">
                        <div class="employee-summary-chip">
                            <span>Asset Aktif</span>
                            <strong><?= (int)($activeAssignments ?? 0) ?></strong>
                        </div>
                        <div class="employee-summary-chip">
                            <span>Riwayat Assignment</span>
                            <strong><?= (int)($assignmentHistory ?? 0) ?></strong>
                        </div>
                    </div>

                    <?php if (!$canDelete): ?>
                        <div class="alert alert-warning mt-3 mb-0 border-0">
                            Data ini belum bisa dihapus permanen karena masih memiliki asset aktif atau riwayat assignment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm employee-table-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-semibold mb-1">Asset yang Sedang Digunakan</h5>
                    <p class="text-muted mb-0">Daftar perangkat yang masih aktif di-assign ke pemegang IT ini.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 employee-table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>No Inventaris</th>
                            <th>Nama Asset</th>
                            <th>Status</th>
                            <th>Sejak</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assignedAssets)): ?>
                            <?php foreach ($assignedAssets as $asset): ?>
                                <tr>
                                    <td><?= esc($asset['sub_category'] ?? '-') ?></td>
                                    <td><?= esc($asset['inventory_no'] ?? '-') ?></td>
                                    <td><?= esc($asset['asset_name'] ?? '-') ?></td>
                                    <td><?= esc($asset['status'] ?? '-') ?></td>
                                    <td><?= !empty($asset['assigned_at']) ? esc(date('d M Y H:i', strtotime($asset['assigned_at']))) : '-' ?></td>
                                    <td>
                                        <form method="post" action="<?= base_url('employees/unassign/' . $employee['id'] . '/' . $asset['asset_id']) ?>" onsubmit="return confirm('Unassign asset ini dari pemegang IT?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Unassign</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada asset yang sedang di-assign.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="<?= base_url('employees') ?>" class="btn btn-outline-secondary">Kembali ke daftar</a>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/employees.css?v=' . filemtime(FCPATH . 'assets/css/employees.css')) ?>">
<?= $this->endSection() ?>
