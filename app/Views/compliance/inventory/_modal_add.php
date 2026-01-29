<div class="modal fade" id="modalAddInventory" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <form
        action="<?= base_url('compliance/inventory/store') ?>"
        method="post"
        enctype="multipart/form-data"
        id="formAddInventory"
        onsubmit="return false;">

        <?= csrf_field() ?>

        <!-- HEADER -->
        <div class="modal-header">
          <h5 class="modal-title fw-bold">
            <i class="fas fa-plus-circle me-1"></i>
            Tambah Inventory Compliance
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body">
          <div class="container-fluid">
            <div class="row">

              <!-- KATEGORI -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Kategori Compliance</label>
                  <select name="category_id" id="category_id" class="form-control" required>
                    <option value="">-- pilih kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?= $cat['id'] ?>">
                        <?= esc($cat['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- NAMA ITEM -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Nama Item / Inventory</label>
                  <select id="item_type_id" name="item_type_id" class="form-control">
                    <option value="">-- pilih item --</option>
                  </select>
                </div>
              </div>

              <!-- NO INVENTARIS -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>No Inventaris</label>
                  <input type="text" name="asset_code" class="form-control"
                    placeholder="Contoh: FS-APAR-001">
                </div>
              </div>

              <!-- TIPE -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Tipe / Spesifikasi</label>
                  <input type="text" name="type_description" class="form-control"
                    placeholder="3,5 Kg / CO2 / Thermatic">
                </div>
              </div>

              <!-- AREA -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Area</label>
                  <select name="area_id" class="form-control" required>
                    <option value="">-- pilih --</option>
                    <?php foreach ($areas as $area): ?>
                      <option value="<?= $area['id'] ?>">
                        <?= esc($area['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- SPECIFIC AREA -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Specific Area</label>
                  <input type="text" name="specific_area" class="form-control"
                    placeholder="Office Lt. 1 / Line A">
                </div>
              </div>

              <!-- PIC -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>PIC</label>
                  <input type="text" name="pic" class="form-control"
                    placeholder="Nama penanggung jawab">
                </div>
              </div>

              <!-- STATUS -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Status</label>
                  <select name="status" class="form-control">
                    <option value="">-- pilih --</option>
                    <option value="Good">Good</option>
                    <option value="Need Repair">Need Repair</option>
                    <option value="Not Active">Not Active</option>
                  </select>
                </div>
              </div>

              <!-- QTY -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Qty</label>
                  <input type="number" name="qty" class="form-control" min="1" value="1">
                </div>
              </div>

              <!-- EXPIRED -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Expired Date</label>
                  <input type="date" name="expired_date" class="form-control">
                </div>
              </div>

              <!-- REMARK -->
              <div class="col-md-12">
                <div class="form-group">
                  <label>Remark</label>
                  <textarea name="remark" rows="2" class="form-control"
                    placeholder="Catatan tambahan"></textarea>
                </div>
              </div>

              <!-- FOTO -->
              <div class="col-md-12">
                <div class="form-group">
                  <label>Foto Inventory</label>
                  <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
              </div>

              <!-- PREVIEW -->
              <div class="col-md-12 text-center">
                <img id="previewPhoto"
                  class="img-fluid rounded d-none mt-2"
                  style="max-height:180px">
              </div>

            </div>
          </div>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Batal
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan
          </button>
        </div>

      </form>
    </div>
  </div>
</div>