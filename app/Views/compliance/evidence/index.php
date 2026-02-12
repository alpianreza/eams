<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="card-header">
  <h3 class="card-title">Evidence Center</h3>
</div>

<div class="card-body">
  <div class="row mb-3">

    <div class="col-md-3">
      <select id="filterYear" class="form-control">
        <option value="">Semua Tahun</option>
        <?php for ($y = 2026; $y <= date('Y'); $y++): ?>
          <option value="<?= $y ?>"><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="col-md-3">
      <select id="filterItem" class="form-control">
        <option value="">Semua Item</option>
        <?php foreach ($itemTypes as $item): ?>
          <option value="<?= $item['id'] ?>">
            <?= esc($item['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <select id="filterArea" class="form-control">
        <option value="">Semua Area</option>
        <?php foreach ($areas as $area): ?>
          <option value="<?= $area['id'] ?>">
            <?= esc($area['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

  </div>

  <div id="evidenceAjax"></div>
</div>
</div>

<?= $this->endSection() ?>