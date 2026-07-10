<div class="table-responsive inventory-grid-table-wrap">
  <table class="table table-bordered align-middle mb-0 inventory-grid-table">
    <thead class="table-light">
      <tr>
        <th class="text-nowrap" style="width:34%">Pengecekan</th>
        <th class="text-center text-nowrap" style="width:14%">Status</th>
        <th class="text-nowrap">Catatan</th>
        <th class="text-center text-nowrap" style="width:14%">Foto</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($detailLogs)): ?>
        <tr>
          <td colspan="4" class="text-center text-muted py-3">Tidak ada checklist untuk bulan ini.</td>
        </tr>
      <?php endif; ?>

      <?php foreach ($detailLogs as $row): ?>
        <tr>
          <td class="text-wrap" style="min-width:100px;word-break:break-word"><?= esc($row['question']) ?></td>

          <td class="text-center">
            <?php if ($row['status'] === 'ok'): ?>
              <i class="bi bi-check-circle-fill text-success" title="Ceklis OK"></i>
            <?php elseif ($row['status'] === 'not_ok'): ?>
              <i class="bi bi-x-circle-fill text-danger" title="Ceklis Temuan"></i>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>

          <td class="text-wrap" style="max-width:150px;word-break:break-word"><?= esc($row['remark'] ?: '-') ?></td>

          <td class="text-center">
            <?php if ($row['photo']): ?>
              <img
                src="<?= base_url('uploads/checklist/' . $row['photo']) ?>"
                class="inventory-thumb-small"
                alt="Foto checklist"
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

<div class="modal fade" id="modalZoomChecklist" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content compliance-modal-content">
      <div class="modal-header compliance-modal-header">
        <h6 class="modal-title">Pratinjau Foto</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body text-center">
        <img id="zoomChecklistImage" src="" class="img-fluid inventory-zoom-checklist" alt="Pratinjau">
      </div>
    </div>
  </div>
</div>
