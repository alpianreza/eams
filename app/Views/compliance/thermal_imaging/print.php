<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <style>
    @page {
      margin: 14mm 12mm;
    }

    body {
      margin: 0;
      color: #000;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 14px;
    }

    .thermal-report-sheet {
      width: 100%;
    }

    .thermal-top-line {
      border-top: 2px solid #111;
      margin-bottom: 12px;
    }

    h1 {
      font-size: 16px;
      margin: 0 0 2px 12px;
      font-weight: 700;
    }

    h2 {
      font-size: 16px;
      margin: 0 0 10px 12px;
      font-weight: 400;
    }

    .thermal-info-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 22px;
    }

    .thermal-info-table td {
      border-bottom: 1px solid #111;
      padding: 2px 10px;
    }

    .thermal-info-label {
      width: 210px;
    }

    .thermal-info-separator {
      width: 18px;
      text-align: center;
    }

    .thermal-section-title {
      margin: 0 0 12px 10px;
      font-size: 16px;
    }

    .thermal-area-box {
      border: 1px solid #111;
      padding: 2px 6px;
      margin-bottom: 9px;
    }

    .thermal-output-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .thermal-output-table th,
    .thermal-output-table td {
      border: 1px solid #111;
      vertical-align: top;
      padding: 3px 6px;
      color: #000;
    }

    .thermal-output-table th {
      text-align: center;
      font-weight: 700;
    }

    .thermal-output-table .col-no {
      width: 42px;
    }

    .thermal-output-table .col-image {
      width: 150px;
    }

    .thermal-output-table .col-location {
      width: 145px;
    }

    .thermal-output-table .col-findings,
    .thermal-output-table .col-recommendation {
      width: 140px;
    }

    .thermal-image-cell {
      text-align: center;
    }

    .thermal-image-cell img {
      width: 100%;
      max-height: 190px;
      object-fit: cover;
    }

    .thermal-location-cell {
      line-height: 1.35;
    }
  </style>
</head>
<body>
  <?= view('compliance/thermal_imaging/_report_sheet', [
    'report' => $report,
    'items' => $items,
    'isPdf' => true,
  ]) ?>
</body>
</html>
