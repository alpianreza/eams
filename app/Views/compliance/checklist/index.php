<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="checklist-page">
  <div id="checklistAjax">
    <?= $this->include('compliance/checklist/_calendar') ?>
    <?= $this->include('compliance/checklist/_form') ?>
  </div>
</div>

<?= $this->endSection() ?>


<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/checklist.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/calendar.css') ?>">
<?= $this->endSection() ?>


<?= $this->section('scripts') ?>

<script src="<?= base_url('js/checklist.js') ?>"></script>

<script>
  window.CHECKLIST_USER = "<?= esc(session('name')) ?>";
  window.CHECKLIST_FLASH = {
    success: <?= session()->getFlashdata('success')
                ? json_encode(session('success'))
                : 'null' ?>,
    error: <?= session()->getFlashdata('error')
              ? json_encode(session('error'))
              : 'null' ?>
  };
</script>
<?= $this->endSection() ?>