<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="checklist-page">

  <div id="checklistAjax">
    <?= $this->include('compliance/checklist/_calendar') ?>
    <?= $this->include('compliance/checklist/_form') ?>
  </div>

</div>

<script>
  window.CHECKLIST_USER = "<?= esc(session('name')) ?>";
  window.CHECKLIST_FLASH = {
    success: <?= session()->getFlashdata('success')
                ? '"' . esc(session('success')) . '"'
                : 'null' ?>,
    error: <?= session()->getFlashdata('error')
              ? '"' . esc(session('error')) . '"'
              : 'null' ?>
  };
</script>

<?= $this->endSection() ?>