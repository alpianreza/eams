<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<a href="<?= base_url('compliance/inventory/detail/' . $inventory['id']) ?>"
  class="btn btn-sm btn-secondary mb-3">
  ← Kembali
</a>

<div id="checklist-container">

  <?= $this->include('compliance/checklist/_calendar') ?>

  <?= $this->include('compliance/checklist/_form') ?>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/checklist.js') ?>"></script>
<?= $this->endSection() ?>