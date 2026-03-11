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
    width: 180px;
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
</style>

<?php
$logoPath = FCPATH . 'assets/images/company/logo.png';
$bulanNama = date('F', strtotime($year . '-' . $month . '-01'));
?>

<table style="width:100%; border:none; margin-bottom:6px;">
  <tr>

    <!-- LOGO -->
    <td style="width:90px; border:none; padding-left:150px;">
      <?php if (file_exists($logoPath)): ?>
        <img src="<?= $logoPath ?>" style="width:60px;">
      <?php endif; ?>
    </td>

    <!-- TEXT -->
    <td style="border:none; text-align:center; padding-right:200px;">

      <div style="font-size:14px; font-weight:bold;">
        PT. YOUNGHYUN STAR
      </div>

      <div style="font-size:13px; font-weight:bold;">
        CHECKLIST PENGECEKAN <?= strtoupper((string)$itemName) ?>
      </div>

    </td>

  </tr>
</table>

<div style="width:100%; font-size:9px; margin-bottom:8px;">

  <table style="width:100%; border-collapse:collapse; border:none;">

    <tr>

      <td style="width:25%; padding:2px 0; border:none;">
        <strong>Tahun:</strong> <?= $year ?>
      </td>

      <td style="width:30%; padding:2px 0; border:none; text-align:center; ">
        <strong>Area:</strong> <?= esc($specificArea ?? '-') ?>
      </td>

      <td style="width:20%; padding:2px 0; border:none;">
        <?php if (!empty($isFireExtinguisher) && $isFireExtinguisher): ?>

          <strong>Masa Berlaku:</strong>
          <?= !empty($expired) ? date('d M Y', strtotime($expired)) : '-' ?>

        <?php endif; ?>
      </td>

      <td style="width:20%; padding:2px 0; text-align:right; border:none;">
        <strong>No Inventaris:</strong> <?= esc($inventory['asset_code']) ?>
      </td>

    </tr>

  </table>

</div>

<table>

  <thead>

    <tr>
      <th rowspan="2" class="no-col">NO</th>
      <th rowspan="2" class="item-col">ITEM PENGECEKAN</th>
      <th colspan="12" style="text-align:center;">
        BULAN
      </th>
    </tr>

    <tr>

      <?php
      $months = [
        1 => "Jan",
        2 => "Feb",
        3 => "Mar",
        4 => "Apr",
        5 => "Mei",
        6 => "Jun",
        7 => "Jul",
        8 => "Agu",
        9 => "Sep",
        10 => "Okt",
        11 => "Nov",
        12 => "Des"
      ];
      ?>

      <?php foreach ($months as $m): ?>
        <th class="date-col"><?= $m ?></th>
      <?php endforeach; ?>

    </tr>

  </thead>

  <tbody>

    <?php $no = 1; ?>
    <?php foreach ($masters as $q): ?>
      <tr>

        <td class="no-col"><?= $no++ ?></td>
        <td class="item-col"><?= esc($q['question']) ?></td>

        <?php foreach ($months as $monthNum => $m): ?>

          <?php
          $status = $monthlyGrid[$q['id']][$monthNum]['status'] ?? null;

          $symbol = match ($status) {
            'ok' => '✓',
            'not_ok' => '✗',
            'na' => '–',
            default => ''
          };
          ?>

          <td class="date-col"><?= $symbol ?></td>

        <?php endforeach; ?>

      </tr>
    <?php endforeach; ?>

  </tbody>

  <tfoot style="font-size:5px; text-align:center;">
    <tr>
      <td colspan="2"><strong>Pengecekan oleh</strong></td>

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

        <td style="font-size:6px; color:#555;">
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

<p style="margin-top:6px;">
  Keterangan: ✓ = sesuai, ✗ = tidak sesuai, – = tidak berlaku
</p>