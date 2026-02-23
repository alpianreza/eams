<div class="modal fade" id="modalQr" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">QR Inventory</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">

        <!-- QR IMAGE -->
        <div class="mb-3">
          <img
            id="qrImage"
            src=""
            class="img-fluid rounded border"
            style="max-height:260px"
            alt="QR Code">
        </div>

        <!-- ACTION BUTTONS -->
        <div class="d-grid gap-2">

          <button
            type="button"
            class="btn btn-success btn-sm"
            id="btnDownloadQr">
            <i class="bi bi-download me-1"></i>
            Download QR
          </button>

          <button
            type="button"
            id="btnRegenQrModal"
            class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-repeat me-1"></i>
            Regenerate QR
          </button>

        </div>

        <!-- INFO -->
        <small class="text-muted d-block mt-2">
          QR akan membuka halaman detail inventory
        </small>

      </div>
    </div>
  </div>
</div>