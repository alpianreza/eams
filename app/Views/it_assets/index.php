<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Inventaris IT</h4>
<?php
$role       = session()->get('role');
$permission = session()->get('permission');

$isAdmin    = ($role === 'admin');
$isWritable = ($permission === 'write' || $role === 'admin');
$isReadOnly = !$isWritable;
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <?php if ($isWritable): ?>
        <a href="<?= base_url('it-assets/create') ?>"
            class="btn btn-success mb-3">
            + Tambah Asset
        </a>
    <?php endif; ?>


</div>

<div class="mb-3">
    <a href="<?= base_url('it-assets') ?>"
        class="btn btn-sm <?= empty($type) ? 'btn-primary' : 'btn-outline-primary' ?>">
        Semua IT
    </a>

    <a href="<?= base_url('it-assets?type=Komputer') ?>"
        class="btn btn-sm <?= $type === 'Komputer' ? 'btn-primary' : 'btn-outline-primary' ?>">
        Komputer
    </a>

    <a href="<?= base_url('it-assets?type=Laptop') ?>"
        class="btn btn-sm <?= $type === 'Laptop' ? 'btn-primary' : 'btn-outline-primary' ?>">
        Laptop
    </a>

    <a href="<?= base_url('it-assets?type=Peripheral') ?>"
        class="btn btn-sm <?= $type === 'Peripheral' ? 'btn-primary' : 'btn-outline-primary' ?>">
        Peripheral
    </a>

    <a href="<?= base_url('it-assets?type=Network') ?>"
        class="btn btn-sm <?= $type === 'Network' ? 'btn-primary' : 'btn-outline-primary' ?>">
        Network Device
    </a>
</div>
<div class="d-flex justify-content-between mb-2">
    <!-- SEARCH -->
    <form method="get" class="d-flex gap-2">
        <?php if ($type): ?>
            <input type="hidden" name="type" value="<?= esc($type) ?>">
        <?php endif; ?>
        <input type="hidden" name="perPage" value="<?= esc($perPage) ?>">

        <input type="text"
            name="q"
            class="form-control form-control-sm"
            placeholder="Cari inventaris / nama / brand..."
            value="<?= esc($keyword) ?>">

        <button class="btn btn-sm btn-primary">
            Cari
        </button>

        <?php if ($keyword): ?>
            <a href="<?= base_url('it-assets') ?>"
                class="btn btn-sm btn-outline-secondary">
                Reset
            </a>
        <?php endif; ?>
    </form>

    <!-- PER PAGE -->
    <form method="get">
        <?php if ($type): ?>
            <input type="hidden" name="type" value="<?= esc($type) ?>">
        <?php endif; ?>
        <?php if ($keyword): ?>
            <input type="hidden" name="q" value="<?= esc($keyword) ?>">
        <?php endif; ?>

        <label class="me-2">Tampilkan</label>
        <select name="perPage"
            class="form-select form-select-sm d-inline-block w-auto"
            onchange="this.form.submit()">
            <option value="20" <?= $perPage == 20 ? 'selected' : '' ?>>20</option>
            <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
        </select>
    </form>
</div>


<table class="table table-bordered table-striped mt-3">
    <thead>
        <tr>
            <th>No</th>
            <th>Foto</th>
            <th>No Inventaris</th>
            <th>Nama Asset</th>
            <th>Brand</th>
            <th>Status</th>
            <th>Lokasi</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $page    = $pager->getCurrentPage();
        $perPage = $pager->getPerPage();
        $no      = 1 + ($perPage * ($page - 1));
        ?>

        <?php foreach ($assets as $a): ?>
            <tr>
                <!-- NO -->
                <td><?= $no++ ?></td>

                <!-- FOTO -->
                <td class="text-center">
                    <?php if ($a['photo']): ?>
                        <img src="<?= base_url('uploads/assets/' . $a['photo']) ?>"
                            width="60"
                            class="img-thumbnail">
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>

                <!-- DATA -->
                <td><?= esc($a['inventory_no']) ?></td>
                <td><?= esc($a['asset_name']) ?></td>
                <td><?= esc($a['brand']) ?></td>
                <td><?= esc($a['status']) ?></td>
                <td><?= esc($a['location']) ?></td>

                <!-- AKSI -->
                <td>
                    <a href="<?= base_url('it-assets/detail/' . $a['id']) ?>"
                        class="btn btn-sm btn-primary">
                        Detail
                    </a>
                    <a href="<?= base_url('it-assets/edit/' . $a['id']) ?>"
                        class="btn btn-sm btn-warning">
                        Edit
                    </a>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>

</table>
<div class="mt-3">
    <?= $pager->links() ?>
</div>


<?= $this->endSection() ?>