<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="employee-page">
    <section class="card border-0 shadow-sm employee-hero-card employee-hero-card--soft mb-4">
        <div class="card-body p-4 p-lg-5">
            <p class="employee-kicker mb-2">Pemegang IT</p>
            <h4 class="fw-bold mb-2">Edit Pemegang IT</h4>
            <p class="text-muted mb-0">Perbarui informasi identitas, divisi, jabatan, dan foto agar data assignment tetap sinkron.</p>
        </div>
    </section>

    <form method="post" action="<?= base_url('employees/update/' . $employee['id']) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?= $this->include('employees/_form') ?>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/employees.css?v=' . filemtime(FCPATH . 'assets/css/employees.css')) ?>">
<?= $this->endSection() ?>
