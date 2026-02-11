<?php

/** @var string $itemName */
/** @var string $specificArea */
/** @var string $pic */
/** @var string|null $expired */
/** @var array $inventory */
?>

<div class="text-center mb-3">
  <h5>
    CHECKLIST PENGECEKAN <?= strtoupper(esc($itemName)) ?>
  </h5>
  Tahun: <?= $year ?>
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


<table class="table table-bordered text-center">
  <thead>
    <tr>
      <th>Pengecekan</th>
      <?php
      $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
      foreach ($months as $m):
      ?>
        <th><?= $m ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>

  <tbody>
    <?php foreach ($masters as $q): ?>
      <tr>
        <td class="text-start">
          <?= esc($q['question']) ?>
        </td>

        <?php for ($m = 1; $m <= 12; $m++): ?>

          <?php
          $data = $monthlyGrid[$q['id']][$m] ?? null;
          $symbol = '';

          if ($data) {
            if ($data['status'] === 'ok') {
              $symbol = '✓';
            } elseif ($data['status'] === 'not_ok') {
              $symbol = '✗';
            } elseif ($data['status'] === 'na') {
              $symbol = '–';
            }
          }
          ?>

          <td style="font-weight:600;">
            <?= $symbol ?>
          </td>

        <?php endfor; ?>

      </tr>
    <?php endforeach; ?>
  </tbody>

  <tfoot>
    <tr>
      <td class="text-muted">
        <strong>Dicek oleh</strong>
      </td>

      <?php for ($m = 1; $m <= 12; $m++): ?>

        <?php
        $data = $checkerByMonth[$m] ?? null;
        $firstName = '';
        $tooltip = '';

        if ($data) {

          $parts = explode(' ', trim($data['name']));
          $firstName = $parts[0] ?? '';

          $tooltip = 'Dicek oleh: ' . $data['name'];

          if ($role !== 'auditor') {
            $tooltip .= ' | Tanggal: ' . date('d M Y', strtotime($data['date']));
          }
        }
        ?>

        <td style="font-size:11px; color:#555;">
          <?php if ($firstName): ?>
            <span title="<?= esc($tooltip) ?>" style="cursor:help;">
              <?= esc($firstName) ?>
            </span>
          <?php endif; ?>
        </td>

      <?php endfor; ?>

    </tr>
  </tfoot>



</table>

<?php if (!empty($findingsByMonth)): ?>

  <hr>
  <h5 class="mt-4">DETAIL TEMUAN TAHUN <?= $year ?></h5>

  <?php
  $months = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
  ];
  ?>

  <?php for ($m = 1; $m <= 12; $m++): ?>

    <div class="mt-4">
      <h6>
        <?= $months[$m] ?>
        (<?= isset($findingsByMonth[$m]) ? count($findingsByMonth[$m]) : 0 ?> Temuan)
      </h6>

      <?php if (!empty($findingsByMonth[$m])): ?>

        <div class="row">

          <?php foreach ($findingsByMonth[$m] as $log): ?>

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

      <?php else: ?>

        <p class="text-muted">Tidak ada temuan</p>

      <?php endif; ?>

    </div>

  <?php endfor; ?>

<?php endif; ?>