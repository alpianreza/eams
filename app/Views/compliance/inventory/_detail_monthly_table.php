<div class="card checklist-card mt-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th width="40%">Pengecekan</th>
            <th width="15%" class="text-center">Status</th>
            <th>Catatan</th>
            <th width="15%" class="text-center">Foto</th>
          </tr>
        </thead>
        <tbody>

          <?php if (empty($detailLogs)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-3">
                Tidak ada checklist
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach ($detailLogs as $row): ?>
            <tr>
              <td><?= esc($row['question']) ?></td>

              <td class="text-center fw-bold">
                <?php if ($row['status'] === 'ok'): ?>
                  <span class="text-success">✓</span>
                <?php elseif ($row['status'] === 'not_ok'): ?>
                  <span class="text-danger">✗</span>
                <?php else: ?>
                  <span class="text-muted">–</span>
                <?php endif; ?>
              </td>

              <td><?= esc($row['remark'] ?: '-') ?></td>
              <td class="text-center">
                <?php if ($row['photo']): ?>
                  <img
                    src="<?= base_url('uploads/checklist/' . $row['photo']) ?>"
                    class="inventory-thumb-small"
                    onclick="openChecklistZoom('<?= base_url('uploads/checklist/' . $row['photo']) ?>')">
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>

            </tr>
          <?php endforeach; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>
<div class="modal fade" id="modalZoomChecklist" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h6 class="modal-title">Preview Foto</h6>
        <button type="button"
          class="btn-close"
          data-bs-dismiss="modal"
          aria-label="Close">
        </button>
      </div>

      <div class="modal-body text-center">
        <img id="zoomChecklistImage"
          src=""
          class="img-fluid inventory-zoom-checklist">
      </div>

    </div>
  </div>
</div>
