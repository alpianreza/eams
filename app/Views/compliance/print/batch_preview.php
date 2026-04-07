<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Print Batch - <?= esc($itemType['name'] ?? 'Checklist') ?></title>

  <style>
    @page {
      size: A4 landscape;
      margin: 6mm;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      color: #111827;
      background: #ffffff;
      font-size: 8px;
    }

    .print-sheet {
      width: 100%;
    }

    .batch-header-table,
    .batch-checklist-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .batch-header-table td,
    .batch-checklist-table th,
    .batch-checklist-table td {
      border: 1px solid #111111;
    }

    .batch-header-table {
      margin-bottom: 6px;
    }

    .batch-header-table td {
      padding: 4px 6px;
      vertical-align: top;
    }

    .batch-company-cell {
      width: 26%;
    }

    .batch-title-cell {
      width: 31%;
      text-align: center;
      vertical-align: middle;
    }

    .batch-sign-cell {
      width: 14.333%;
      min-height: 58px;
      text-align: center;
      vertical-align: top;
      position: relative;
    }

    .batch-date-cell {
      padding: 6px 8px;
      font-weight: 700;
      letter-spacing: .03em;
    }

    .batch-company-brand {
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }

    .batch-company-logo {
      width: 58px;
      max-height: 38px;
      object-fit: contain;
      flex-shrink: 0;
    }

    .batch-company-name {
      font-size: 12px;
      font-weight: 800;
      line-height: 1.1;
      color: #0b67c2;
      margin-bottom: 2px;
    }

    .batch-company-line {
      font-size: 8px;
      line-height: 1.3;
    }

    .batch-title-main {
      font-size: 11px;
      font-weight: 800;
      line-height: 1.15;
      text-transform: uppercase;
    }

    .batch-title-sub {
      margin-top: 2px;
      font-size: 8px;
      color: #5b7cba;
    }

    .batch-sign-label {
      font-size: 8px;
      margin-bottom: 22px;
    }

    .batch-sign-line {
      position: absolute;
      left: 12px;
      right: 12px;
      bottom: 10px;
      border-top: 1px solid #111111;
    }

    .batch-checklist-table th,
    .batch-checklist-table td {
      padding: 2px 2px;
      text-align: center;
      vertical-align: middle;
      word-break: break-word;
    }

    .batch-checklist-table thead th {
      font-weight: 700;
    }

    .batch-checklist-table thead {
      display: table-header-group;
    }

    .batch-checklist-table tbody tr {
      page-break-inside: avoid;
    }

    .batch-checklist-table .text-left {
      text-align: left;
    }

    .col-no {
      width: 4.5%;
    }

    .col-location {
      width: 14%;
    }

    .col-pic {
      width: 12%;
    }

    .col-static,
    .col-question {
      width: auto;
    }

    .answer-cell {
      height: 18px;
    }

    .empty-state {
      padding: 16px;
      border: 1px solid #111111;
      text-align: center;
      font-weight: 700;
    }
  </style>
</head>

<body>
  <div class="print-sheet">
    <?= view('compliance/print/templates/batch/_header', [
      'itemType' => $itemType,
      'layout' => $layout,
    ]) ?>

    <?= view('compliance/print/templates/batch/' . ($layout['template'] ?? 'generic'), [
      'itemType' => $itemType,
      'inventories' => $inventories,
      'layout' => $layout,
      'checklistMatrix' => $checklistMatrix ?? [],
    ]) ?>
  </div>
</body>

</html>
