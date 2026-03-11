<style>
  body {
    font-family: sans-serif;
    font-size: 9px;
  }

  /* ================= HEADER ================= */

  .header-wrap {
    width: 100%;
    border-bottom: 2px solid #0b5e3b;
    padding-bottom: 5px;
    margin-bottom: 8px;
  }

  .header-top {
    text-align: center;
    font-weight: bold;
    font-size: 13px;
  }

  .header-sub {
    width: 100%;
    margin-top: 4px;
    font-size: 9px;
  }

  .header-sub div {
    display: inline-block;
    width: 33%;
  }

  .line {
    border-top: 1px solid #000;
    margin-top: 2px;
  }

  /* ================= TABLE ================= */

  table {
    border-collapse: collapse;
    width: 100%;
    table-layout: fixed;
    /* WAJIB */
  }

  th,
  td {
    border: 1px solid #000;
    padding: 2px;
    font-size: 8px;
  }

  thead th {
    background: #f0f0f0;
    font-weight: bold;
  }

  /* kolom */
  .no-col {
    width: 25px;
    text-align: center;
  }

  .item-col {
    width: 220px;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
  }

  .date-col {
    width: 18px;
    text-align: center;
  }

  /* libur */
  .offday {
    background: #f4c2c2;
    /* merah muda */
  }

  .offday-header {
    color: #c00000;
    /* angka merah */
    font-weight: bold;
  }

  /* footer */
  .photo {
    width: 65%;
    margin-top: 5px;
  }

  tfoot td {
    border-top: 2px solid #000;
    font-weight: 600;
  }
</style>

<?php
$bulanNama = date('F', strtotime($year . '-' . $month . '-01'));
?>

<!-- ================= HEADER ================= -->

<?php
$logoPath = FCPATH . 'assets/images/company/logo.png';
?>


<table style="width:100%; border:none; margin-bottom:6px;">
  <tr>

    <!-- LOGO -->
    <td style="width:90px; border:none; padding-left:300px;">
      <?php if (file_exists($logoPath)): ?>
        <img src="<?= $logoPath ?>" style="width:60px;">
      <?php endif; ?>
    </td>

    <!-- TEXT -->
    <td style="border:none; text-align:center; padding-right:330px;">

      <div style="font-size:14px; font-weight:bold;">
        PT. YOUNGHYUN STAR
      </div>

      <div style="font-size:13px; font-weight:bold;">
        CHECKLIST PENGECEKAN <?= strtoupper((string)$itemName) ?>
      </div>

    </td>

  </tr>
</table>


<div style="width:100%; font-size:9px; margin-bottom:10px;">
  <div style="width:40%; float:left;">
    <strong>Bulan:</strong>
    <?= $bulanNama ?> <?= $year ?>
  </div>

  <div style="width:25%; float:left;">
    <strong>Area:</strong>
    <?= esc($specificArea ?? '-') ?>
  </div>

  <div style="width:20%; float:right;">
    <strong>No Inventaris:</strong>
    <?= esc($inventory['asset_code']) ?>
  </div>

  <div style="clear:both;"></div>
</div>


<!-- ================= GRID ================= -->

<table>
  <thead>

    <tr>
      <th rowspan="2" class="no-col">NO</th>
      <th rowspan="2" class="item-col">ITEM PENGECEKAN</th>
      <th colspan="<?= count($dailyDays) ?>" style="text-align:center;">
        TANGGAL
      </th>
    </tr>

    <tr>
      <?php foreach ($dailyDays as $date): ?>
        <?php
        $isSunday  = date('w', strtotime($date)) == 0;
        $isHoliday = in_array($date, $holidayDates ?? []);
        $isOffDay  = $isSunday || $isHoliday;
        ?>
        <th class="date-col <?= $isOffDay ? 'offday-header' : '' ?>">
          <?= date('j', strtotime($date)) ?>
        </th>
      <?php endforeach; ?>
    </tr>

  </thead>

  <tbody>

    <?php $no = 1; ?>
    <?php foreach ($masters as $q): ?>
      <tr>

        <td class="no-col"><?= $no++ ?></td>
        <td class="item-col"><?= esc($q['question']) ?></td>

        <?php foreach ($dailyDays as $date): ?>

          <?php
          $isSunday  = date('w', strtotime($date)) == 0;
          $isHoliday = in_array($date, $holidayDates ?? []);
          $isOffDay  = $isSunday || $isHoliday;

          $status = $dailyGrid[$q['id']][$date] ?? null;

          $symbol = match ($status) {
            'ok' => '✓',
            'not_ok' => '✗',
            'na' => '–',
            default => ''
          };
          ?>

          <td class="date-col <?= $isOffDay ? 'offday' : '' ?>">
            <?= !$isOffDay ? $symbol : '' ?>
          </td>

        <?php endforeach; ?>

      </tr>
    <?php endforeach; ?>

  </tbody>
  <tfoot style="font-size:8px; text-align:center;">
    <tr>

      <td colspan="2"><strong>Pengecekan oleh</strong></td>

      <?php foreach ($dailyDays as $date): ?>

        <?php
        $data = $checkerByDate[$date] ?? null;

        $initial = '';
        $tooltip = '';

        if ($data) {

          $nameParts = explode(' ', trim($data['name']));
          $firstName = $nameParts[0] ?? '';

          $initial = strtoupper(substr($firstName, 0, 2));

          $tooltip = 'Dicek oleh: ' . $data['name'];

          if ($role !== 'auditor') {
            $tooltip .= ' | Tanggal: ' . date('d M Y', strtotime($data['date']));
          }
        }
        ?>

        <td style="font-size:11px;color:#555;">

          <?php if ($initial): ?>
            <span title="<?= esc($tooltip) ?>" style="cursor:help;">
              <?= $initial ?>
            </span>
          <?php endif; ?>

        </td>

      <?php endforeach; ?>

    </tr>
  </tfoot>
</table>

<p style="margin-top:5px; font-size:8px;">
  Keterangan: ✓ = sesuai, ✗ = tidak sesuai, – = tidak berlaku
</p>

<!-- ================= TEMUAN ================= -->
<?php if (!empty($findings)): ?>

  <div style="page-break-before: always;"></div>

  <h4 style="margin-bottom:10px;font-size:12px;">
    DETAIL TEMUAN BULAN <?= strtoupper($bulanNama) ?> <?= $year ?>
  </h4>

  <?php $rows = array_chunk($findings, 4); ?>

  <table style="width:100%; border-collapse:collapse;">

    <?php foreach ($rows as $index => $pair): ?>

      <tr>

        <?php foreach ($pair as $i => $log): ?>

          <?php
          $questionName = '';
          foreach ($masters as $q) {
            if ($q['id'] == $log['checklist_template_id']) {
              $questionName = $q['question'];
              break;
            }
          }
          ?>

          <td style="width:50%; vertical-align:top; padding:4px 8px;">

            <div style="
font-size:9px;
border-bottom:1px solid #ddd;
padding-bottom:4px;
margin-bottom:6px;
">

              <strong><?= ($index * 2) + $i + 1 ?>.</strong>
              <span style="font-style:italic;">
                <?= esc($log['display_period']) ?>
              </span>
              <br>

              <span style="margin-left:10px;">
                <?= esc($questionName) ?>
              </span>
              <br>

              <?php if (!empty($log['remark'])): ?>
                <span style="margin-left:10px;color:#555;">
                  <?= esc($log['remark']) ?>
                </span>
              <?php endif; ?>

            </div>

          </td>

        <?php endforeach; ?>

        <?php if (count($pair) == 1): ?>
          <td style="width:50%"></td>
        <?php endif; ?>

      </tr>

    <?php endforeach; ?>

  </table>

<?php endif; ?>