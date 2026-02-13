<div class="modal fade" id="modalEditChecklistMaster" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <form id="formEditChecklistMaster" method="post">
        <?= csrf_field() ?>

        <input type="hidden" name="id" id="edit_id">

        <div class="modal-header">
          <h5 class="modal-title">Edit Pertanyaan Checklist</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <!-- Question -->
          <div class="mb-3">
            <label class="form-label">Pertanyaan</label>
            <textarea name="question"
              id="edit_question"
              class="form-control"
              rows="3"
              required></textarea>
          </div>
          <!-- Status -->
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="active"
              id="edit_active"
              class="form-select">
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>

          <div class="alert alert-warning py-2">
            <small>
              Item Type & Frequency tidak bisa diubah
              untuk menjaga konsistensi checklist.
            </small>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal">Batal</button>
          <button type="submit"
            class="btn btn-primary">Update</button>
        </div>

      </form>

    </div>
  </div>
</div>