<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<a href="<?= base_url('compliance/inventory/detail/' . $inventory['id']) ?>"
  class="btn btn-sm btn-secondary mb-3">
  ← Kembali
</a>

<div class="container-fluid checklist-page">
  <div class="container-xl">

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