<div class="modal fade" id="modalEditInventory" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow rounded-4">

      <!-- ACTION DISET VIA JS -->
      <form id="formEditInventory" method="post">
        <?= csrf_field() ?>

        <div class="modal-header">
          <h5 class="modal-title fw-semibold">Edit Inventory</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <!-- ID -->
          <input type="hidden" name="id" id="edit_id">

          <!-- KATEGORI & AREA (LOCKED) -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Kategori</label>
              <input type="text" class="form-control" id="edit_category_text" disabled>
              <input type="hidden" name="category_id" id="edit_category_id">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Area</label>
              <input type="text" class="form-control" id="edit_area_text" disabled>
              <input type="hidden" name="area_id" id="edit_area_id">
            </div>
          </div>

          <hr>

          <div class="row g-3">

            <!-- NAMA ITEM (LOCKED) -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nama Item</label>
              <input type="text" class="form-control" id="edit_item_name" disabled>
              <input type="hidden" name="item_type_id" id="edit_item_type_id">
            </div>

            <!-- NO INVENTARIS -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">No Inventaris</label>
              <input type="text" name="asset_code" id="edit_code" class="form-control">
            </div>

            <!-- TIPE -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tipe / Spesifikasi</label>
              <input type="text" name="type_description" id="edit_type" class="form-control">
            </div>

            <!-- PIC -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">PIC</label>
              <input type="text" name="pic" id="edit_pic" class="form-control">
            </div>

            <!-- STATUS -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" id="edit_status" class="form-select">
                <option value="Good">Good</option>
                <option value="Need Repair">Need Repair</option>
                <option value="Not Active">Not Active</option>
              </select>
            </div>

            <!-- REMARK -->
            <div class="col-12">
              <label class="form-label fw-semibold">Remark</label>
              <textarea name="remark" id="edit_remark"
                class="form-control" rows="3"></textarea>
            </div>

          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <small class="text-muted">
            Kategori, Area, dan Item tidak dapat diubah
          </small>
          <div>
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
              Batal
            </button>
            <button type="submit" class="btn btn-primary px-4">
              Update
            </button>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>