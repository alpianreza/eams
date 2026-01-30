<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0"><?= esc($item['name']) ?></h4>
      <small class="text-muted">Pertanyaan checklist</small>
    </div>

    <div class="d-flex gap-2">
      <a href="<?= site_url('compliance/checklist/master/category/' . $item['inventory_category_id']) ?>"
        class="btn btn-sm btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Item
      </a>

      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
        + Tambah Pertanyaan
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-body p-0">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Pertanyaan</th>
            <th>Frekuensi</th>
            <th>Foto</th>
            <th>Status</th>
            <th width="120">Aksi</th>
          </tr>
        </thead>
        <tbody>

          <?php if (empty($questions)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                Belum ada pertanyaan checklist
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach ($questions as $q): ?>
            <tr>
              <td><?= esc($q['question']) ?></td>

              <td>
                <span class="badge bg-secondary">
                  <?= esc(ucfirst($q['frequency'])) ?>
                </span>
              </td>

              <td>
                <?= $q['require_photo'] ? 'Wajib' : 'Tidak' ?>
              </td>

              <td>
                <?= $q['active'] ? 'Aktif' : 'Nonaktif' ?>
              </td>

              <td>
                <button
                  class="btn btn-warning btn-xs"
                  data-action="edit"
                  data-update-url="<?= site_url('compliance/checklist/master/update/' . $q['id']) ?>"
                  data-question="<?= esc($q['question']) ?>"
                  data-frequency="<?= $q['frequency'] ?>"
                  data-require_photo="<?= $q['require_photo'] ?>"
                  data-active="<?= $q['active'] ?>">
                  Edit
                </button>
              </td>
            </tr>
          <?php endforeach; ?>

        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- =======================
     MODAL ADD
======================== -->
<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog">
    <form method="post"
      action="<?= site_url('compliance/checklist/master/store') ?>"
      class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Tambah Pertanyaan</h5>
      </div>

      <div class="modal-body">
        <input type="hidden" name="item_type_id" value="<?= $item['id'] ?>">

        <div class="mb-3">
          <label class="form-label">Pertanyaan</label>
          <textarea name="question" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Frekuensi</label>
          <select name="frequency" class="form-select" required>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="require_photo" value="1">
          <label class="form-check-label">Wajib Foto</label>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary">Simpan</button>
      </div>

    </form>
  </div>
</div>

<!-- =======================
     MODAL EDIT
======================== -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" id="formEdit" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit Pertanyaan</h5>
      </div>

      <div class="modal-body">
        <!-- penting: hidden item_type_id -->
        <input type="hidden" name="item_type_id" value="<?= $item['id'] ?>">

        <div class="mb-3">
          <label class="form-label">Pertanyaan</label>
          <textarea name="question" id="edit_question" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Frekuensi</label>
          <select name="frequency" id="edit_frequency" class="form-select" required>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="edit_require_photo" name="require_photo" value="1">
          <label class="form-check-label">Wajib Foto</label>
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="edit_active" name="active" value="1">
          <label class="form-check-label">Aktif</label>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary">Update</button>
      </div>

    </form>
  </div>
</div>

<?= $this->section('scripts') ?>
const BASE_URL = "<?= base_url() ?>";
</script>

<?= $this->endSection() ?>
<?= $this->endSection() ?>