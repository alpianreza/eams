<style>
  body {
    font-family: sans-serif;
    font-size: 11px;
  }

  /* ================= HEADER ================= */

  .header-wrap {
    width: 100%;
    border-bottom: 2px solid #0b5e3b;
    padding-bottom: 5px;
    margin-bottom: 8px;
  }

  table {
    border-collapse: collapse;
    width: 100%;
    table-layout: fixed;
  }

  th,
  td {
    border: 1px solid #000;
    padding: 5px;
    font-size: 10px;
  }

  thead {
    display: table-header-group;
  }

  thead th {
    background: #f0f0f0;
    font-weight: bold;
  }

  .no-col {
    width: 35px;
    text-align: center;
  }

  .item-col {
    width: 60%;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
  }

  .week-col {
    width: 10%;
    text-align: center;
  }

  .photo {
    width: 65%;
    margin-top: 5px;
  }
</style>

<?php
$bulanNama = date('F', strtotime($year . '-' . $month . '-01'));
?>

<!-- ================= HEADER ================= -->

<div class="header-wrap">

  <div style="text-align:center; margin-bottom:8px;">
    <strong style="font-size:14px;">
      PT. YOUNGHYUN STAR
    </strong>
  </div>

  <div style="text-align:center; margin-bottom:8px;">
    <strong style="font-size:14px;">
      CHECKLIST PENGECEKAN <?= strtoupper((string)$itemName) ?>
    </strong>
  </div>

  <div style="width:100%; font-size:9px; margin-bottom:10px;">

    <div style="width:40%; float:left;">
      <strong>Bulan:</strong><br>
      <?= $bulanNama ?> <?= $year ?>
    </div>

    <div style="width:33%; float:left;">
      <strong>Area:</strong><br>
      <?= esc($specificArea ?? '-') ?>
    </div>

    <div style="width:25%; float:left;">
      <strong>No Inventaris:</strong><br>
      <?= esc($inventory['asset_code'] ?? '-') ?>
    </div>

    <div style="clear:both;"></div>
  </div>

</div>

<!-- ================= GRID ================= -->

<table>
  <thead>
    <tr>
      <th rowspan="2" class="no-col">NO</th>
      <th rowspan="2" class="item-col">ITEM PENGECEKAN</th>
      <th colspan="4" style="text-align:center;">
        MINGGU
      </th>
    </tr>
    <tr>
      <th class="week-col">1</th>
      <th class="week-col">2</th>
      <th class="week-col">3</th>
      <th class="week-col">4</th>
    </tr>
  </thead>

  <tbody>

    <?php $no = 1; ?>
    <?php foreach ($masters as $q): ?>
      <tr>

        <td class="no-col"><?= $no++ ?></td>
        <td class="item-col"><?= esc($q['question']) ?></td>

        <?php foreach ([1, 2, 3, 4] as $week): ?>

          <?php
          $status = $weeklyGrid[$q['id']][$week] ?? null;

          $symbol = match ($status) {
            'ok'     => '✓',
            'not_ok' => '✗',
            'na'     => '–',
            default  => ''
          };
          ?>

          <td class="week-col"><?= $symbol ?></td>

        <?php endforeach; ?>

      </tr>
    <?php endforeach; ?>

  </tbody>
</table>

<p style="margin-top:5px; font-size:8px;">
  Keterangan: ✓ = sesuai, ✗ = tidak sesuai, – = tidak berlaku
</p>

<!-- ================= TEMUAN ================= -->

<?php if (!empty($findings)): ?>

  <div style="page-break-before: always;"></div>

  <h4 style="margin-bottom:10px;">
    DETAIL TEMUAN BULAN <?= strtoupper($bulanNama) ?> <?= $year ?>
  </h4>

  <table style="width:100%;">
    <tr>
      <td style="width:50%; vertical-align:top;">

        <?php
        $total = count($findings);
        $half  = ceil($total / 2);
        ?>

        <?php foreach ($findings as $i => $log): ?>

          <?php if ($i == $half): ?>
      </td>
      <td style="width:50%; vertical-align:top;">
      <?php endif; ?>

      <?php
          $questionName = '';
          foreach ($masters as $q) {
            if ($q['id'] == $log['checklist_template_id']) {
              $questionName = $q['question'];
              break;
            }
          }
      ?>

      <div style="
          padding:6px 0;
          margin-bottom:6px;
          font-size:9px;
          border-bottom:1px solid #ddd;
      ">

        <strong><?= $i + 1 ?>.</strong>
        <span style="font-style:italic;">
          <?= esc($log['display_period']) ?>
        </span><br>

        <span style="margin-left:10px;">
          <?= esc($questionName) ?>
        </span><br>

        <?php if (!empty($log['remark'])): ?>
          <span style="color:#555; margin-left:10px;">
            <?= esc($log['remark']) ?>
          </span>
        <?php endif; ?>

      </div>

    <?php endforeach; ?>

      </td>
    </tr>
  </table>

<?php endif; ?>