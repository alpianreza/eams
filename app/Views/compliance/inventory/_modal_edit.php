<?php $picUsers = (new \App\Models\UserModel())->where('status', 'active')->orderBy('name', 'ASC')->findAll(); ?>
<div class="modal fade" id="modalEditInventory" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content compliance-modal-content"><form id="formEditInventory" method="post"><?= csrf_field() ?><input type="hidden" name="pic" id="edit_pic">
<div class="modal-header compliance-modal-header"><div><h5 class="modal-title fw-semibold mb-0 d-inline-flex align-items-center gap-2"><i class="bi bi-pencil-square"></i> Edit Compliance Inventory</h5><div class="small text-muted mt-1">Perbarui informasi aset tanpa mengubah kategori, area, dan item.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
<div class="modal-body compliance-modal-body"><input type="hidden" name="id" id="edit_id"><div class="row g-3 mb-1">
<div class="col-md-6"><label for="edit_category_text" class="form-label">Kategori</label><input type="text" class="form-control" id="edit_category_text" disabled><input type="hidden" name="category_id" id="edit_category_id"></div><div class="col-md-6"><label for="edit_area_text" class="form-label">Area</label><input type="text" class="form-control" id="edit_area_text" disabled><input type="hidden" name="area_id" id="edit_area_id"></div>
<div class="col-md-6"><label for="edit_item_name" class="form-label">Nama Item</label><input type="text" class="form-control" id="edit_item_name" disabled><input type="hidden" name="item_type_id" id="edit_item_type_id"></div><div class="col-md-6"><label for="edit_code" class="form-label">No Inventaris</label><input type="text" name="asset_code" id="edit_code" class="form-control"></div>
<div class="col-md-6"><label for="edit_type" class="form-label">Tipe / Spesifikasi</label><input type="text" name="type_description" id="edit_type" class="form-control"></div><div class="col-md-6"><label for="edit_specific_area" class="form-label">Specific Area</label><input type="text" name="specific_area" id="edit_specific_area" class="form-control"></div>
<div class="col-md-6"><label for="edit_pic_primary" class="form-label">PIC Utama</label><select id="edit_pic_primary" class="form-select" required><option value="">-- pilih user aktif --</option><?php foreach ($picUsers as $picUser): ?><option value="<?= esc($picUser['name']) ?>"><?= esc($picUser['name']) ?> · <?= esc($picUser['username']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label for="edit_pic_secondary" class="form-label">PIC Kedua <span class="text-muted fw-normal">(opsional)</span></label><select id="edit_pic_secondary" class="form-select"><option value="">-- tanpa PIC kedua --</option><?php foreach ($picUsers as $picUser): ?><option value="<?= esc($picUser['name']) ?>"><?= esc($picUser['name']) ?> · <?= esc($picUser['username']) ?></option><?php endforeach; ?></select><small class="text-muted">Maksimal 2 PIC.</small></div>
<div class="col-md-3"><label for="edit_expired" class="form-label">Expired</label><input type="date" name="expired_date" id="edit_expired" class="form-control"></div><div class="col-md-3"><label for="edit_status" class="form-label">Status</label><select name="status" id="edit_status" class="form-select"><option value="Good">Good</option><option value="Need Repair">Need Repair</option><option value="Not Active">Not Active</option></select></div>
<div class="col-12"><label for="edit_remark" class="form-label">Remark</label><textarea name="remark" id="edit_remark" class="form-control" rows="3"></textarea></div></div></div>
<div class="modal-footer compliance-modal-footer d-flex justify-content-between"><small class="text-muted">PIC hanya dapat dipilih dari user aktif.</small><div class="d-flex gap-2"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button></div></div></form></div></div></div>
<script>
(function () {
  const modal = document.getElementById('modalEditInventory');
  const form = document.getElementById('formEditInventory');
  const primary = document.getElementById('edit_pic_primary');
  const secondary = document.getElementById('edit_pic_secondary');
  const hidden = document.getElementById('edit_pic');
  if (!modal || !form || !primary || !secondary || !hidden) return;
  const sync = function () {
    secondary.setCustomValidity(primary.value && secondary.value === primary.value ? 'PIC kedua harus berbeda.' : '');
    hidden.value = [primary.value, secondary.value].filter(Boolean).join('\n');
  };
  modal.addEventListener('show.bs.modal', function () {
    const values = hidden.value.split(/\s*(?:\r\n|\r|\n|,)\s*/).filter(Boolean).slice(0, 2);
    primary.value = values[0] || ''; secondary.value = values[1] || ''; sync();
  });
  primary.addEventListener('change', sync); secondary.addEventListener('change', sync); form.addEventListener('submit', sync);
})();
</script>
