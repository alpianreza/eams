<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
  .thermal-report-actions {
    max-width: 920px;
    margin: 0 auto 12px;
  }

  .thermal-report-sheet {
    width: 100%;
    max-width: 920px;
    margin: 0 auto;
    padding: 18px 16px 28px;
    background: #fff;
    color: #000;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
  }

  .thermal-top-line {
    border-top: 2px solid #111;
    margin-bottom: 12px;
  }

  .thermal-report-sheet h1 {
    font-size: 16px;
    margin: 0 0 2px 12px;
    font-weight: 700;
  }

  .thermal-report-sheet h2 {
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
    width: 54px;
  }

  .thermal-output-table .col-image {
    width: 190px;
  }

  .thermal-output-table .col-location {
    width: 160px;
  }

  .thermal-output-table .col-findings,
  .thermal-output-table .col-recommendation {
    width: 180px;
  }

  .thermal-no {
    text-align: left;
  }

  .thermal-image-cell {
    text-align: center;
  }

  .thermal-image-cell img {
    width: 100%;
    max-height: 230px;
    object-fit: cover;
  }

  .thermal-location-cell {
    line-height: 1.35;
  }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="thermal-report-actions d-flex justify-content-end gap-2">
  <a href="/compliance/thermal-imaging/create" class="btn btn-outline-primary btn-sm">
    <i class="bi bi-plus-circle"></i>
    Buat Lagi
  </a>
  <a href="/compliance/thermal-imaging/<?= (int) $report['id'] ?>/pdf" target="_blank" class="btn btn-danger btn-sm">
    <i class="bi bi-file-earmark-pdf"></i>
    Export PDF
  </a>
</div>

<?= view('compliance/thermal_imaging/_report_sheet', [
  'report' => $report,
  'items' => $items,
  'isPdf' => false,
]) ?>

<?= $this->endSection() ?>
