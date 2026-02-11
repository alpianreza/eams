<?php

/** @var string $itemName */
/** @var string $specificArea */
/** @var string $pic */
/** @var string|null $expired */
/** @var array $inventory */
?>

<?php
$bulanNama = date('F', strtotime($year . '-' . $month . '-01'));
?>

<div class="text-center mb-3">
  <h5>
    CHECKLIST PENGECEKAN <?= strtoupper(esc($itemName)) ?>
  </h5>
  <div>
    Bulan: <?= $bulanNama ?> <?= $year ?>
  </div>
</div>

<div class="row mb-3">

  <div class="col-md-3">
    <strong>Lokasi:</strong><br>
    <?= esc($specificArea ?? '-') ?>
  </div>

  <div class="col-md-3">
    <strong>PIC:</strong><br>
    <?= esc($pic ?? '-') ?>
  </div>

  <div class="col-md-3">
    <strong>No Inventaris:</strong><br>
    <?= esc($inventory['asset_code']) ?>
  </div>

  <div class="col-md-3">
    <?php if (!empty($isFireExtinguisher) && $isFireExtinguisher): ?>
      <strong>Masa Berlaku:</strong><br>
      <?= !empty($expired) ? date('d M Y', strtotime($expired)) : '-' ?>
    <?php endif; ?>
  </div>

</div>


<div class="table-responsive">
  <table class="table table-bordered text-center">

    <thead>
      <tr>
        <th>Item Pengecekan</th>
        <th>Minggu 1</th>
        <th>Minggu 2</th>
        <th>Minggu 3</th>
        <th>Minggu 4</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($masters as $q): ?>
        <tr>
          <td class="text-start"><?= esc($q['question']) ?></td>

          <?php for ($w = 1; $w <= 4; $w++): ?>

            <?php
            $status = $weeklyGrid[$q['id']][$w] ?? null;
            ?>

            <td>
              <?php
              if ($status === 'ok') echo '✓';
              elseif ($status === 'not_ok') echo '✗';
              elseif ($status === 'na') echo '–';
              ?>
            </td>

          <?php endfor; ?>

        </tr>
      <?php endforeach; ?>
    </tbody>


    <!-- FOOTER CEK OLEH -->
    <tfoot>
      <tr>
        <td><strong>Pengecekan oleh</strong></td>

        <?php for ($w = 1; $w <= 4; $w++): ?>

          <?php
          $data = $checkerByWeek[$w] ?? null;

          $initial = '';
          $tooltip = '';

          if ($data) {

            $nameParts = explode(' ', trim($data['name']));
            $firstName = $nameParts[0] ?? '';

            $displayName = ucfirst(strtolower($firstName));

            $tooltip = 'Dicek oleh: ' . $data['name'];

            if ($role !== 'auditor') {
              $tooltip .= ' | Tanggal: ' .
                date('d M Y', strtotime($data['date']));
            }
          }
          ?>

          <td style="font-size:11px; color:#555;">
            <?php if (!empty($displayName)): ?>
              <span title="<?= esc($tooltip) ?>" style="cursor:help;">
                <?= esc($displayName) ?>
              </span>
            <?php endif; ?>
          </td>

        <?php endfor; ?>

      </tr>
    </tfoot>

  </table>
</div>


<!-- DETAIL TEMUAN BULAN INI -->
<?php if (!empty($findings)): ?>

  <hr>
  <h6 class="mt-4">
    DETAIL TEMUAN BULAN <?= $bulanNama ?> <?= $year ?>
  </h6>

  <div class="row">

    <?php foreach ($findings as $log): ?>

      <div class="col-md-4 mb-3">
        <div class="card h-100 shadow-sm">

          <?php if (!empty($log['photo'])): ?>
            <img src="<?= base_url('uploads/checklist/' . $log['photo']) ?>"
              class="card-img-top img-preview"
              style="height:200px;object-fit:cover;cursor:pointer;"
              data-src="<?= base_url('uploads/checklist/' . $log['photo']) ?>">
          <?php endif; ?>

          <div class="card-body">

            <small class="text-muted">
              <?= esc($log['display_period']) ?>
            </small>


            <br>

            <?php
            foreach ($masters as $q) {
              if ($q['id'] == $log['checklist_template_id']) {
                echo '<strong>' . esc($q['question']) . '</strong>';
                break;
              }
            }
            ?>

            <?php if (!empty($log['remark'])): ?>
              <p class="mt-2 mb-0">
                <?= esc($log['remark']) ?>
              </p>
            <?php endif; ?>

          </div>

        </div>
      </div>

    <?php endforeach; ?>

  </div>

<?php endif; ?>