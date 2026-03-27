<div class="modal fade" id="modalZoomPhoto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-zoom-photo">
    <div class="modal-content compliance-modal-content border-0">
      <div class="modal-header compliance-modal-header border-0 pb-0">
        <h6 class="modal-title">Preview Foto Inventory</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body compliance-modal-body text-center pt-2">
        <img
          src="<?= base_url('uploads/inventory/' . $inventory['photo']) ?>"
          class="img-fluid rounded zoom-photo-img"
          alt="Foto Inventory">
      </div>
    </div>
  </div>
</div>
