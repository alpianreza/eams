<div class="modal fade" id="modalEditInventory" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow rounded-4">

      <form id="formEditInventory" method="post">
        <?= csrf_field() ?>

        <div class="modal-header">
          <h5 class="modal-title">Edit Inventory</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <input type="hidden" name="category_id" id="edit_category_id">
          <input type="hidden" name="area_id" id="edit_area_id">

          <div class="row g-3">

            <!-- KATEGORI -->
            <select name="category_id" id="edit_category_id" class="form-select">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>">
                  <?= esc($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <select name="area_id" id="edit_area_id" class="form-select">
              <?php foreach ($areas as $area): ?>
                <option value="<?= $area['id'] ?>">
                  <?= esc($area['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <div class="col-12">
              <hr>
            </div>

            <!-- NAMA ITEM -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nama Item</label>
              <select id="edit_item_type_id" name="item_type_id" class="form-select" required>
                <option value="">-- pilih item --</option>
              </select>
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
                <option value="">-</option>
                <option value="Good">Good</option>
                <option value="Need Repair">Need Repair</option>
                <option value="Not Active">Not Active</option>
              </select>
            </div>

            <div class="col-md-6"></div>

            <!-- REMARK -->
            <div class="col-12">
              <label class="form-label fw-semibold">Remark</label>
              <textarea name="remark"
                id="edit_remark"
                class="form-control"
                rows="3"></textarea>
            </div>

          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <small class="text-muted">* Perubahan akan langsung disimpan</small>
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

<script>
  document.addEventListener('DOMContentLoaded', function() {

    const editCategory = document.getElementById('edit_category_id');
    const editItem = document.getElementById('edit_item_type_id');
    const editArea = document.getElementById('edit_area_id');

    function loadItemTypes(categoryId, selectedItemId, callback) {
      editItem.innerHTML = '<option value="">Loading...</option>';

      fetch(`<?= base_url('compliance/inventory/item-types') ?>/${categoryId}`)
        .then(res => res.json())
        .then(data => {

          editItem.innerHTML = '<option value="">-- pilih item --</option>';

          data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            editItem.appendChild(opt);
          });

          if (selectedItemId) {
            editItem.value = selectedItemId;
          }

          if (callback) callback();
        })
        .catch(err => {
          console.error(err);
          editItem.innerHTML = '<option>Error load item</option>';
        });
    }

    // 🔥 EVENT DELEGATION (INI KUNCI)
    document.addEventListener('click', function(e) {

      const btn = e.target.closest('.btn-edit');
      if (!btn) return;

      const categoryId = btn.dataset.categoryId;
      const itemTypeId = btn.dataset.itemTypeId;
      const areaId = btn.dataset.areaId;

      // set value dasar
      editCategory.value = categoryId;
      editArea.value = areaId;

      // load item → baru buka modal
      loadItemTypes(categoryId, itemTypeId, function() {

        document.getElementById('edit_id').value = btn.dataset.id;
        document.getElementById('edit_code').value = btn.dataset.code || '';
        document.getElementById('edit_type').value = btn.dataset.type || '';
        document.getElementById('edit_pic').value = btn.dataset.pic || '';
        document.getElementById('edit_status').value = btn.dataset.status || '';
        document.getElementById('edit_remark').value = btn.dataset.remark || '';

        new bootstrap.Modal(
          document.getElementById('modalEditInventory')
        ).show();
      });

    });

    // kalau kategori diganti manual
    editCategory.addEventListener('change', function() {
      if (this.value) {
        loadItemTypes(this.value, null);
      }
    });

  });
</script>