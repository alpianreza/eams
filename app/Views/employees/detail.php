<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Detail Karyawan</h4>
<div class="mb-3">
    <?php if ($employee['photo']): ?>
        <img src="<?= base_url('uploads/employees/' . $employee['photo']) ?>"
            width="150"
            class="img-thumbnail">
    <?php else: ?>
        <img src="https://via.placeholder.com/150?text=No+Photo"
            class="img-thumbnail">
    <?php endif; ?>
</div>

<table class="table">
    <tr>
        <th>ID Karyawan</th>
        <td><?= esc($employee['employee_id']) ?></td>
    </tr>
    <tr>
        <th>Nama</th>
        <td><?= esc($employee['name']) ?></td>
    </tr>
    <tr>
        <th>Divisi</th>
        <td><?= esc($employee['division']) ?></td>
    </tr>
    <tr>
        <th>Jabatan</th>
        <td><?= esc($employee['position']) ?></td>
    </tr>
    <tr>
        <th>Status</th>
        <td><?= esc($employee['status']) ?></td>
    </tr>
</table>

<h5 class="mt-4">Asset yang Digunakan</h5>

<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th>No</th>
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
            <?php $i = 1;
            foreach ($assignedAssets as $a): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= esc($a->sub_category) ?></td>
                    <td><?= esc($a->inventory_no) ?></td>
                    <td><?= esc($a->asset_name) ?></td>
                    <td><?= esc($a->status) ?></td>
                    <td><?= esc($a->assigned_at) ?></td>
                    <td>
                        <form method="post"
                            action="<?= base_url('employees/unassign/' . $employee['id'] . '/' . $a->asset_id) ?>"
                            onsubmit="return confirm('Unassign asset ini?')">

                            <?= csrf_field() ?>

                            <button class="btn btn-sm btn-danger">
                                Unassign
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted">
                    Belum ada asset yang di-assign
                </td>
            </tr>
        <?php endif; ?>
    </tbody>

</table>


<a href="<?= base_url('employees') ?>" class="btn btn-secondary">Kembali</a>
<a href="<?= base_url('employees/edit/' . $employee['id']) ?>"
    class="btn btn-warning">Edit</a>

<a href="<?= base_url('employees/deactivate/' . $employee['id']) ?>"
    class="btn btn-danger"
    onclick="return confirm('Nonaktifkan karyawan ini?')">
    Nonaktifkan
</a>


<?= $this->endSection() ?>