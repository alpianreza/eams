<div class="card checklist-card mt-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered text-center mb-0">
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
                <td>
                  <?php
                  $status = $weeklyGrid[$q['id']][$w] ?? null;

                  if ($status === 'ok') echo '<span class="text-success">✓</span>';
                  elseif ($status === 'not_ok') echo '<span class="text-danger">✗</span>';
                  else echo '<span class="text-muted">–</span>';
                  ?>
                </td>
              <?php endfor; ?>

            </tr>
          <?php endforeach; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>