<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<a href="<?= base_url('employees/create') ?>" class="btn btn-primary mb-3">
    + Tambah Karyawan
</a>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID Karyawan</th>
            <th>Nama</th>
            <th>Divisi</th>
            <th>Jabatan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($employees as $e): ?>
            <tr>
                <td><?= esc($e['employee_id']) ?></td>
                <td><?= esc($e['name']) ?></td>
                <td><?= esc($e['division']) ?></td>
                <td><?= esc($e['position']) ?></td>
                <td>
                    <a href="<?= base_url('employees/detail/' . $e['id']) ?>"
                        class="btn btn-sm btn-info">
                        Detail
                    </a>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>

<?= $this->endSection() ?>