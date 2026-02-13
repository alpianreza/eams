<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <div class="mb-3">
    <h4 class="mb-0">Tambah Item Checklist</h4>
    <small class="text-muted">Master item untuk checklist compliance</small>
  </div>

  <div class="card col-lg-6">
    <form method="post" action="<?= site_url('compliance/item/store') ?>">
      <?= csrf_field() ?>

      <div class="card-body">

        <!-- KATEGORI -->
        <div class="mb-3">
          <label class="form-label">Kategori</label>
          <select name="inventory_category_id" class="form-select" required>
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>">
                <?= esc($cat['name']) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>

        <!-- NAMA ITEM -->
        <div class="mb-3">
          <label class="form-label">Nama Item</label>
          <input type="text"
            name="name"
            class="form-control"
            required>
        </div>

        <!-- KODE (OPSIONAL) -->
        <div class="mb-3">
          <label class="form-label">Kode (Opsional)</label>
          <input type="text"
            name="code"
            class="form-control">
        </div>

      </div>

      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="<?= site_url('compliance/checklist/master') ?>"
          class="btn btn-secondary">
          Batal
        </a>
        <button class="btn btn-primary">
          Simpan Item
        </button>
      </div>

    </form>
  </div>

</div>

<?= $this->endSection() ?>