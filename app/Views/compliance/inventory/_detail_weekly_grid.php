<div class="table-responsive inventory-grid-table-wrap">
  <table class="table table-bordered text-center mb-0 inventory-grid-table">
    <thead class="table-light">
      <tr>
        <th class="text-start">Pengecekan</th>
        <th>W1</th>
        <th>W2</th>
        <th>W3</th>
        <th>W4</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($questions as $q): ?>
        <tr>
          <td class="text-start"><?= esc($q['question']) ?></td>

          <?php for ($w = 1; $w <= 4; $w++): ?>
            <?php $status = $weeklyGrid[$q['id']][$w] ?? null; ?>
            <td>
              <?php if ($status === 'ok'): ?>
                <i class="bi bi-check-circle-fill text-success" title="Sesuai"></i>
              <?php elseif ($status === 'not_ok'): ?>
                <i class="bi bi-x-circle-fill text-danger" title="Tidak sesuai"></i>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
