<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="employee-page">
    <section class="card border-0 shadow-sm employee-hero-card mb-4">
        <div class="card-body p-4 p-lg-5">
            <p class="employee-kicker mb-2">Pemegang IT</p>
            <h4 class="fw-bold mb-2">Tambah Pemegang IT</h4>
            <p class="text-muted mb-0">Simpan data pengguna perangkat supaya assignment asset dan monitoring device tetap rapi.</p>
        </div>
    </section>

    <form method="post" action="<?= base_url('employees/store') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?= $this->include('employees/_form') ?>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/employees.css?v=' . filemtime(FCPATH . 'assets/css/employees.css')) ?>">
<?= $this->endSection() ?>
