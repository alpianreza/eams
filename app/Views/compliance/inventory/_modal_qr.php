<div class="modal fade" id="modalQr" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content compliance-modal-content">
      <div class="modal-header compliance-modal-header">
        <h6 class="modal-title mb-0 d-inline-flex align-items-center gap-2">
          <i class="bi bi-qr-code"></i>
          QR Compliance Inventory
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body compliance-modal-body text-center">
        <div class="mb-3">
          <img id="qrImage" src="" class="img-fluid rounded border inventory-qr-img" alt="QR Code">
        </div>

        <div class="d-grid gap-2">
          <button type="button" class="btn btn-success btn-sm" id="btnDownloadQr">
            <i class="bi bi-download me-1"></i>
            Download QR
          </button>

          <button type="button" id="btnRegenQrModal" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-repeat me-1"></i>
            Regenerate QR
          </button>
        </div>

        <small class="text-muted d-block mt-3">
          QR akan membuka halaman detail compliance inventory.
        </small>
      </div>
    </div>
  </div>
</div>
