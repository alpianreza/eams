<?= $this->extend('layouts/main') ?>

<?php
$totalEmployees = count($employees ?? []);
$activeEmployees = count(array_filter($employees ?? [], static fn($row) => ($row['status'] ?? '') === 'active'));
$inactiveEmployees = max(0, $totalEmployees - $activeEmployees);
$activeAssets = array_sum(array_map(static fn($row) => (int)($row['active_assets'] ?? 0), $employees ?? []));
?>

<?= $this->section('content') ?>
<div class="employee-page">
    <section class="card border-0 shadow-sm employee-hero-card mb-4">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
            <div>
                <p class="employee-kicker mb-2">IT Workspace</p>
                <h4 class="fw-bold mb-2">Pemegang IT</h4>
                <p class="text-muted mb-0">Kelola data pemegang asset IT, status pengguna, dan assignment perangkat dalam satu halaman.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('employees/create') ?>" class="btn btn-primary px-4">
                    <i class="bi bi-person-plus me-1"></i>
                    Tambah Pemegang
                </a>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm employee-stat-card">
                <div class="card-body">
                    <span class="employee-stat-label">Total Data</span>
                    <h3 class="mb-1"><?= (int) $totalEmployees ?></h3>
                    <p class="text-muted mb-0">Pemegang IT terdaftar</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm employee-stat-card">
                <div class="card-body">
                    <span class="employee-stat-label">Status Aktif</span>
                    <h3 class="mb-1"><?= (int) $activeEmployees ?></h3>
                    <p class="text-muted mb-0">Pengguna aktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm employee-stat-card">
                <div class="card-body">
                    <span class="employee-stat-label">Status Inaktif</span>
                    <h3 class="mb-1"><?= (int) $inactiveEmployees ?></h3>
                    <p class="text-muted mb-0">Data nonaktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm employee-stat-card">
                <div class="card-body">
                    <span class="employee-stat-label">Asset Aktif</span>
                    <h3 class="mb-1"><?= (int) $activeAssets ?></h3>
                    <p class="text-muted mb-0">Sedang dipakai</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm employee-table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 employee-table">
                    <thead>
                        <tr>
                            <th>Pemegang</th>
                            <th class="d-none d-md-table-cell">Divisi</th>
                            <th class="d-none d-lg-table-cell">Jabatan</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Asset Aktif</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">Belum ada data pemegang IT.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <?php
                                $status = strtolower((string)($employee['status'] ?? 'inactive'));
                                $isActive = $status === 'active';
                                $detailUrl = base_url('employees/detail/' . $employee['id']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="employee-person">
                                            <div class="employee-person__avatar">
                                                <?php if (!empty($employee['photo'])): ?>
                                                    <img src="<?= base_url('uploads/employees/' . $employee['photo']) ?>" alt="<?= esc($employee['name']) ?>">
                                                <?php else: ?>
                                                    <span><?= esc(strtoupper(substr((string)($employee['name'] ?? 'U'), 0, 1))) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="<?= $detailUrl ?>" class="employee-person__name">
                                                    <?= esc($employee['name'] ?? '-') ?>
                                                </a>
                                                <div class="small text-muted"><?= esc($employee['employee_id'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell"><?= esc($employee['division'] ?? '-') ?></td>
                                    <td class="d-none d-lg-table-cell"><?= esc($employee['position'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge rounded-pill text-bg-<?= $isActive ? 'success' : 'secondary' ?>">
                                            <?= $isActive ? 'Aktif' : 'Inaktif' ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="employee-count-pill"><?= (int)($employee['active_assets'] ?? 0) ?> asset</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="<?= $detailUrl ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                            <a href="<?= base_url('employees/edit/' . $employee['id']) ?>" class="btn btn-sm btn-outline-warning">Edit</a>

                                            <form method="post" action="<?= base_url('employees/' . ($isActive ? 'deactivate' : 'activate') . '/' . $employee['id']) ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>
                                            </form>

                                            <form method="post" action="<?= base_url('employees/delete/' . $employee['id']) ?>" class="d-inline" onsubmit="return confirm('Hapus pemegang IT ini? Data tanpa riwayat assignment akan dihapus permanen.')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/employees.css?v=' . filemtime(FCPATH . 'assets/css/employees.css')) ?>">
<?= $this->endSection() ?>
