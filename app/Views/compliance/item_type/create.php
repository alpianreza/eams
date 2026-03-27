<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="checklist-master-page">
  <section class="card checklist-master-hero no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-master-kicker mb-1">Checklist Master</p>
        <h5 class="mb-1 fw-bold">Tambah Item Checklist</h5>
        <p class="text-muted mb-0">Buat item baru untuk dikelola pada checklist master.</p>
      </div>

      <a href="<?= site_url('compliance/checklist/master') ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        Kembali
      </a>
    </div>
  </section>

  <section class="card checklist-master-form-card no-lift">
    <form method="post" action="<?= site_url('compliance/item/store') ?>">
      <?= csrf_field() ?>

      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Kategori</label>
            <select name="inventory_category_id" class="form-select" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= esc($cat['name']) ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Nama Item</label>
            <input type="text" name="name" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Kode (Opsional)</label>
            <input type="text" name="code" class="form-control" placeholder="Contoh: FE / CCTV / EXD">
          </div>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="<?= site_url('compliance/checklist/master') ?>" class="btn btn-outline-secondary">Batal</a>
        <button class="btn btn-primary d-inline-flex align-items-center gap-1" type="submit">
          <i class="bi bi-save"></i>
          Simpan Item
        </button>
      </div>
    </form>
  </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/checklist-master.css?v=' . filemtime(FCPATH . 'assets/css/checklist-master.css')) ?>">
<?= $this->endSection() ?>
