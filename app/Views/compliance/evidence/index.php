<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="card">
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
        <select id="filterFollowUp" class="form-select">
          <option value="">Semua Status</option>
          <option value="open">Open</option>
          <option value="monitoring">Monitoring</option>
          <option value="closed">Closed</option>
        </select>
      </div>

    </div>


    <div id="evidenceAjax"></div>

  </div>
</div>

<!-- Modal Evidence Detail -->
<div class="modal fade" id="evidenceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Detail Evidence</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="evidenceDetailBody">
        <div class="text-center p-4">Loading...</div>
      </div>

    </div>
  </div>
</div>

<?= $this->endSection() ?>