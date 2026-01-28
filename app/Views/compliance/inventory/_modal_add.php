<div class="modal fade" id="modalAddInventory" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">

      <form
        action="<?= base_url('compliance/inventory/store') ?>"
        method="post"
        enctype="multipart/form-data"
        onsubmit="return false;"
        id="formAddInventory">


        <?= csrf_field() ?>

        <div class="modal-header border-0">
          <h5 class="modal-title fw-semibold">Tambah Inventory Compliance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- KATEGORI -->
            <div class="col-md-6">
              <label class="form-label">Kategori Compliance</label>
              <select name="category_id" id="category_id" class="form-select" required>
                <option value="">-- pilih kategori --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>">
                    <?= esc($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>

            </div>

            <!-- NAMA ITEM -->
            <div class="col-md-6">
              <label class="form-label">Nama Item / Inventory</label>
              <select id="item_type_id" name="item_type_id" class="form-select">
                <option value="">-- pilih item --</option>
              </select>
            </div>

            <!-- NO INVENTARIS -->
            <div class="col-md-6">
              <label class="form-label">No Inventaris</label>
              <input type="text"
                name="asset_code"
                class="form-control"
                placeholder="Contoh: FS-APAR-001">
            </div>

            <!-- TIPE / SPESIFIKASI -->
            <div class="col-md-6">
              <label class="form-label">Tipe / Spesifikasi</label>
              <input type="text"
                name="type_description"
                class="form-control"
                placeholder="Contoh: 3,5 Kg / CO2 / Thermatic (boleh kosong)">
            </div>

            <!-- AREA -->
            <div class="col-md-6">
              <label class="form-label">Area</label>
              <select name="area_id" class="form-select" required>
                <option value="">-- pilih --</option>
                <?php foreach ($areas as $area): ?>
                  <option value="<?= $area['id'] ?>">
                    <?= esc($area['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- SPECIFIC AREA -->
            <div class="col-md-6">
              <label class="form-label">Specific Area</label>
              <input type="text"
                name="specific_area"
                class="form-control"
                placeholder="Contoh: Office Lt. 1 / Line A">
            </div>

            <!-- PIC -->
            <div class="col-md-6">
              <label class="form-label">PIC</label>
              <input type="text"
                name="pic"
                class="form-control"
                placeholder="Nama penanggung jawab">
            </div>

            <!-- STATUS -->
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="">-- pilih --</option>
                <option value="Good">Good</option>
                <option value="Need Repair">Need Repair</option>
                <option value="Not Active">Not Active</option>
              </select>
            </div>

            <!-- QTY -->
            <div class="col-md-6">
              <label class="form-label">Qty</label>
              <input type="number"
                name="qty"
                class="form-control"
                min="1"
                value="1">
            </div>

            <!-- EXPIRED DATE -->
            <div class="col-md-6">
              <label class="form-label">Expired Date</label>
              <input type="date"
                name="expired_date"
                class="form-control">
            </div>

            <!-- REMARK -->
            <div class="col-md-12">
              <label class="form-label">Remark / Comment</label>
              <textarea name="remark"
                class="form-control"
                rows="2"
                placeholder="Catatan tambahan (boleh kosong)"></textarea>
            </div>

            <!-- FOTO -->
            <div class="col-12">
              <label class="form-label">Foto Inventory</label>
              <input type="file"
                name="photo"
                class="form-control"
                accept="image/*">
            </div>

            <div class="col-12 text-center">
              <img id="previewPhoto"
                class="img-fluid rounded d-none mt-2"
                style="max-height:180px">
            </div>
          </div>
        </div>

        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">
            Batal
          </button>
          <button type="submit" class="btn btn-primary">
            Simpan
          </button>
        </div>

      </form>
    </div>
  </div>
</div>