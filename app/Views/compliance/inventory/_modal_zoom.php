<div class="modal fade" id="modalZoomPhoto" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-zoom-photo">
    <div class="modal-content border-0">

      <!-- HEADER + TOMBOL CLOSE -->
      <div class="modal-header border-0 pb-0">
        <button
          type="button"
          class="btn-close ms-auto"
          data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>

      <!-- BODY -->
      <div class="modal-body text-center pt-0">
        <img
          src="<?= base_url('uploads/inventory/' . $inventory['photo']) ?>"
          class="img-fluid rounded zoom-photo-img"
          alt="Foto Inventory">
      </div>

    </div>
  </div>
</div>