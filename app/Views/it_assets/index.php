<?= $this->extend('layouts/main') ?>

<?php
$role       = session()->get('role');
$permission = session()->get('permission');
$isWritable = ($permission === 'write' || $role === 'admin');

$filterItems = [
    '' => 'Semua IT',
    'Komputer' => 'Komputer',
    'Laptop' => 'Laptop',
    'Peripheral' => 'Peripheral',
    'Network' => 'Network Device',
];
?>

<?= $this->section('content') ?>
<div class="it-shell">
    <section class="card border-0 shadow-sm no-lift it-hero-card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="it-kicker mb-1">IT Asset</p>
                <h5 class="fw-bold mb-1">Inventaris IT</h5>
                <p class="text-muted mb-0">Kelola data asset IT, status, lokasi, dan pemakaian karyawan.</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <?php if ($isWritable): ?>
                    <a href="<?= base_url('it-assets/create') ?>" class="btn btn-sm btn-primary it-quick-btn">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Asset
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('dashboard-it') ?>" class="btn btn-sm btn-outline-primary it-quick-btn">
                    <i class="bi bi-pie-chart me-1"></i> Dashboard IT
                </a>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm no-lift mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($filterItems as $value => $label): ?>
                    <?php
                    $isActive = ($value === '' && empty($type)) || ($value !== '' && $type === $value);
                    $href = $value === '' ? base_url('it-assets') : base_url('it-assets?type=' . urlencode($value));
                    ?>
                    <a href="<?= esc($href) ?>" class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-primary' ?> rounded-pill px-3">
                        <?= esc($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm no-lift">
        <div class="card-body">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-lg-8">
                    <form method="get" class="row g-2 align-items-end">
                        <?php if ($type): ?>
                            <input type="hidden" name="type" value="<?= esc($type) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="perPage" value="<?= esc($perPage) ?>">

                        <div class="col-sm-9 col-md-10">
                            <label for="assetSearch" class="form-label form-label-sm">Cari Asset</label>
                            <input
                                id="assetSearch"
                                type="text"
                                name="q"
                                class="form-control"
                                placeholder="Cari no inventaris, nama asset, brand, atau lokasi"
                                value="<?= esc($keyword) ?>">
                        </div>
                        <div class="col-sm-3 col-md-2 d-grid">
                            <button class="btn btn-primary">Cari</button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <form method="get" class="row g-2 align-items-end">
                        <?php if ($type): ?>
                            <input type="hidden" name="type" value="<?= esc($type) ?>">
                        <?php endif; ?>
                        <?php if ($keyword): ?>
                            <input type="hidden" name="q" value="<?= esc($keyword) ?>">
                        <?php endif; ?>

                        <div class="col-7 col-md-8">
                            <label for="perPageSelect" class="form-label form-label-sm">Baris per Halaman</label>
                            <select id="perPageSelect" name="perPage" class="form-select" onchange="this.form.submit()">
                                <option value="20" <?= $perPage == 20 ? 'selected' : '' ?>>20</option>
                                <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                            </select>
                        </div>
                        <div class="col-5 col-md-4 d-grid">
                            <?php if ($keyword || $type): ?>
                                <a href="<?= base_url('it-assets') ?>" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 it-table">
                    <thead>
                        <tr>
                            <th width="56" class="text-center">No</th>
                            <th width="88" class="text-center">Foto</th>
                            <th>No Inventaris</th>
                            <th>Nama Asset</th>
                            <th class="d-none d-lg-table-cell">Brand</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Lokasi</th>
                            <th width="190" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $page = $pager->getCurrentPage();
                        $displayPerPage = $pager->getPerPage();
                        $no = 1 + ($displayPerPage * ($page - 1));
                        ?>

                        <?php if (empty($assets)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    Data asset tidak ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assets as $a): ?>
                                <?php
                                $statusRaw = strtolower(trim((string) ($a['status'] ?? '-')));
                                $statusClass = match ($statusRaw) {
                                    'baik', 'normal' => 'success',
                                    'rusak' => 'danger',
                                    'dipakai' => 'primary',
                                    default => 'secondary',
                                };
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($a['photo'])): ?>
                                            <img src="<?= base_url('uploads/assets/' . $a['photo']) ?>" class="it-thumb" alt="Foto <?= esc($a['asset_name'] ?? 'asset') ?>">
                                        <?php else: ?>
                                            <span class="it-thumb-placeholder"><i class="bi bi-image"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold"><?= esc($a['inventory_no']) ?></td>
                                    <td><?= esc($a['asset_name']) ?></td>
                                    <td class="d-none d-lg-table-cell"><?= esc($a['brand']) ?></td>
                                    <td><span class="badge text-bg-<?= esc($statusClass) ?>"><?= esc(ucfirst($a['status'] ?? '-')) ?></span></td>
                                    <td class="d-none d-md-table-cell"><?= esc($a['location']) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap justify-content-center gap-1">
                                            <a href="<?= base_url('it-assets/detail/' . $a['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                            <?php if ($isWritable): ?>
                                                <a href="<?= base_url('it-assets/edit/' . $a['id']) ?>" class="btn btn-sm btn-outline-warning">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-end">
                <ul class="pagination pagination-sm mb-0">
                    <?= $pager->links('default', 'eams') ?>
                </ul>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/it-suite.css?v=' . filemtime(FCPATH . 'assets/css/it-suite.css')) ?>">
<?= $this->endSection() ?>
