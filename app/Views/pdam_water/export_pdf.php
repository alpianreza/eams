<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= esc($title ?? 'Laporan Pengecekan Air PDAM') ?></title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 11px;
      color: #111827;
    }
    .title {
      text-align: center;
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 4px;
    }
    .subtitle {
      text-align: center;
      margin-bottom: 14px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      border: 1px solid #111827;
      padding: 6px 5px;
      vertical-align: middle;
    }
    th {
      background: #f3f4f6;
      text-align: center;
      font-weight: bold;
    }
    td.center {
      text-align: center;
    }
    td.right {
      text-align: right;
    }
    tr.offday td {
      background: #fee2e2;
    }
  </style>
</head>
<body>
  <div class="title"><?= esc($title ?? 'Laporan Pengecekan Air PDAM') ?></div>
  <div class="subtitle">Periode: <?= esc($periodLabel ?? '-') ?></div>

  <table>
    <thead>
      <tr>
        <th style="width: 40px;">No</th>
        <th style="width: 90px;">Hari</th>
        <th style="width: 110px;">Tanggal</th>
        <th style="width: 70px;">Jam</th>
        <th style="width: 95px;">Meteran Air</th>
        <th>Keterangan</th>
        <th style="width: 70px;">Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $row): ?>
        <tr class="<?= !empty($row['is_offday']) ? 'offday' : '' ?>">
          <td class="center"><?= esc((string) ($row['no'] ?? '')) ?></td>
          <td><?= esc((string) ($row['day_name'] ?? '')) ?></td>
          <td><?= esc((string) ($row['date_label'] ?? '')) ?></td>
          <td class="center"><?= esc((string) ($row['time'] ?? '')) ?></td>
          <td class="right"><?= isset($row['meter']) && $row['meter'] !== '' && $row['meter'] !== null ? esc(number_format((float) $row['meter'], 2, ',', '.')) : '' ?></td>
          <td><?= esc((string) ($row['note'] ?? '')) ?></td>
          <td class="center"><?= esc((string) ($row['status'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
