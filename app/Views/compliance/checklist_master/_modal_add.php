<div class="modal fade" id="modalAddChecklistMaster" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <form id="formAddChecklistMaster"
        action="<?= site_url('compliance/checklist/master/store') ?>"
        method="post">

        <?= csrf_field() ?>

        <div class="modal-header">
          <h5 class="modal-title">Tambah Pertanyaan Checklist</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <!-- Item Type -->
          <div class="mb-3">
            <label class="form-label">Item Type</label>
            <select name="item_type_id" class="form-select" required>
              <option value="">-- Pilih Item --</option>
              <?php foreach ($itemTypes as $it): ?>
                <option value="<?= $it['id'] ?>">
                  <?= esc($it['name']) ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>

          <!-- Frequency -->
          <div class="mb-3">
            <label class="form-label">Frequency</label>
            <select name="frequency" class="form-select" required>
              <option value="">-- Pilih --</option>
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
            </select>
          </div>

          <!-- Question -->
          <div class="mb-3">
            <label class="form-label">Pertanyaan</label>
            <textarea name="question"
              class="form-control"
              rows="3"
              required></textarea>
          </div>

          <!-- Require Photo -->
          <div class="mb-3">
            <label class="form-label">Wajib Foto?</label>
            <select name="require_photo" class="form-select">
              <option value="1">Ya</option>
              <option value="0">Tidak</option>
            </select>
          </div>

          <!-- Status -->
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="active" class="form-select">
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
            data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            Simpan
          </button>
        </div>

      </form>

    </div>
  </div>
</div>