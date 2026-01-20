<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Detail Asset IT</h4>
<?php
$role       = session()->get('role');
$permission = session()->get('permission');

$isAdmin    = ($role === 'admin');
$isWritable = ($permission === 'write' || $role === 'admin');
$isReadOnly = !$isWritable;
?>

<table class="table">
    <tr>
        <th>No Inventaris</th>
        <td><?= esc($asset['inventory_no']) ?></td>
    </tr>
    <tr>
        <th>Nama</th>
        <td><?= esc($asset['asset_name']) ?></td>
    </tr>
    <tr>
        <th>Brand</th>
        <td><?= esc($asset['brand']) ?></td>
    </tr>
    <tr>
        <th>Status</th>
        <td><?= esc($asset['status']) ?></td>
    </tr>
    <tr>
        <th>Lokasi</th>
        <td><?= esc($asset['location']) ?></td>
    </tr>

    <!-- 🔽 INI HASIL STEP NO. 5 -->
    <?php if ($currentEmployee): ?>
        <tr>
            <th>Pemakai</th>
            <td>
                <strong><?= esc($currentEmployee->name) ?></strong><br>
                ID: <?= esc($currentEmployee->employee_id) ?><br>
                <?= esc($currentEmployee->division) ?> - <?= esc($currentEmployee->position) ?><br>
                <small>Sejak: <?= esc($currentEmployee->assigned_at) ?></small>
            </td>
        </tr>
    <?php else: ?>
        <tr>
            <th>Pemakai</th>
            <td><em>Belum di-assign</em></td>
        </tr>
    <?php endif; ?>
</table>
<?php if ($isWritable): ?>
    <a href="<?= base_url('it-assets/assign/' . $asset['id']) ?>"
        class="btn btn-warning">
        Assign Asset
    </a>
<?php endif; ?>

<a href="<?= base_url('it-assets') ?>" class="btn btn-secondary mt-2">
    Kembali
</a>
<h5 class="mt-4">Riwayat Pemakaian</h5>
<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>ID Karyawan</th>
            <th>Divisi</th>
            <th>Jabatan</th>
            <th>Mulai</th>
            <th>Selesai</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($history): ?>
            <?php $i = 1;
            foreach ($history as $h): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= esc($h->name) ?></td>
                    <td><?= esc($h->employee_id) ?></td>
                    <td><?= esc($h->division) ?></td>
                    <td><?= esc($h->position) ?></td>
                    <td><?= esc($h->assigned_at) ?></td>
                    <td>
                        <?= $h->returned_at
                            ? esc($h->returned_at)
                            : '<span class="badge bg-success">Masih dipakai</span>' ?>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted">
                    Belum ada riwayat pemakaian
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>


<?= $this->endSection() ?>