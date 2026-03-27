<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$totalQuestions = count($questions);
$activeQuestions = count(array_filter($questions, static fn($q) => (int)($q['active'] ?? 0) === 1));
$photoRequiredQuestions = count(array_filter($questions, static fn($q) => (int)($q['require_photo'] ?? 0) === 1));
?>

<div class="checklist-master-page">
  <section class="card checklist-master-hero no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="checklist-master-kicker mb-1">Checklist Master</p>
        <h5 class="mb-1 fw-bold"><?= esc($item['name']) ?></h5>
        <p class="text-muted mb-0">Kelola pertanyaan checklist untuk item ini.</p>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= site_url('compliance/checklist/master/category/' . $item['inventory_category_id']) ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
          <i class="bi bi-arrow-left"></i>
          Kembali
        </a>

        <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalAdd">
          <i class="bi bi-plus-circle"></i>
          Tambah Pertanyaan
        </button>
      </div>
    </div>
  </section>

  <section class="row g-2 mb-3 checklist-master-stats">
    <div class="col-6 col-lg-3">
      <div class="card checklist-master-stat-card no-lift">
        <div class="card-body">
          <div class="checklist-master-stat-label">Total Pertanyaan</div>
          <div class="checklist-master-stat-value"><?= esc((string)$totalQuestions) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card checklist-master-stat-card no-lift">
        <div class="card-body">
          <div class="checklist-master-stat-label">Status Aktif</div>
          <div class="checklist-master-stat-value"><?= esc((string)$activeQuestions) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card checklist-master-stat-card no-lift">
        <div class="card-body">
          <div class="checklist-master-stat-label">Wajib Foto</div>
          <div class="checklist-master-stat-value"><?= esc((string)$photoRequiredQuestions) ?></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card checklist-master-stat-card no-lift">
        <div class="card-body">
          <div class="checklist-master-stat-label">Frekuensi</div>
          <div class="mt-1">
            <select id="itemFrequency" class="form-select form-select-sm" data-url="<?= site_url('compliance/checklist/master/item-frequency/' . $item['id']) ?>">
              <option value="daily" <?= $item['checklist_frequency'] === 'daily' ? 'selected' : '' ?>>Harian</option>
              <option value="weekly" <?= $item['checklist_frequency'] === 'weekly' ? 'selected' : '' ?>>Mingguan</option>
              <option value="monthly" <?= $item['checklist_frequency'] === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="card checklist-master-table-card no-lift">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0 checklist-master-table">
          <thead>
            <tr>
              <th width="60" class="text-center">No</th>
              <th>Pertanyaan</th>
              <th width="130" class="text-center">Wajib Foto</th>
              <th width="120" class="text-center">Status</th>
              <th width="140" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($questions)): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-5">Belum ada pertanyaan checklist.</td>
              </tr>
            <?php endif; ?>

            <?php foreach ($questions as $index => $q): ?>
              <tr>
                <td class="text-center fw-semibold"><?= $index + 1 ?></td>
                <td>
                  <div class="fw-semibold"><?= esc($q['question']) ?></div>
                  <small class="text-muted">Item: <?= esc($item['name']) ?></small>
                </td>
                <td class="text-center">
                  <?php if ((int)($q['require_photo'] ?? 0) === 1): ?>
                    <span class="badge text-bg-info">Ya</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary">Tidak</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ((int)($q['active'] ?? 0) === 1): ?>
                    <span class="badge text-bg-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary">Nonaktif</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="d-flex align-items-center justify-content-center gap-1">
                    <button
                      class="btn btn-outline-warning btn-sm"
                      data-action="edit"
                      data-update-url="<?= site_url('compliance/checklist/master/update/' . $q['id']) ?>"
                      data-question="<?= esc($q['question']) ?>"
                      data-require-photo="<?= (int)($q['require_photo'] ?? 0) ?>"
                      data-active="<?= (int)($q['active'] ?? 0) ?>"
                      type="button">
                      <i class="bi bi-pencil-square"></i>
                    </button>

                    <button
                      class="btn btn-outline-danger btn-sm"
                      data-action="delete"
                      data-url="<?= site_url('compliance/checklist/master/delete/' . $q['id']) ?>"
                      type="button">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form method="post" action="<?= site_url('compliance/checklist/master/store') ?>" class="modal-content checklist-master-modal" id="formAdd">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Tambah Pertanyaan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="item_type_id" value="<?= $item['id'] ?>">

        <div class="mb-3">
          <label class="form-label">Pertanyaan</label>
          <textarea name="question" class="form-control" rows="3" required></textarea>
        </div>

        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Wajib Foto</label>
            <select name="require_photo" class="form-select">
              <option value="0">Tidak</option>
              <option value="1">Ya</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="active" class="form-select">
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form method="post" id="formEdit" class="modal-content checklist-master-modal">
      <?= csrf_field() ?>
      <input type="hidden" name="item_type_id" value="<?= $item['id'] ?>">

      <div class="modal-header">
        <h5 class="modal-title">Edit Pertanyaan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pertanyaan</label>
          <textarea name="question" id="edit_question" class="form-control" rows="3" required></textarea>
        </div>

        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Wajib Foto</label>
            <select name="require_photo" id="edit_require_photo" class="form-select">
              <option value="0">Tidak</option>
              <option value="1">Ya</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="active" id="edit_active" class="form-select">
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/checklist-master.css?v=' . filemtime(FCPATH . 'assets/css/checklist-master.css')) ?>">
<?= $this->endSection() ?>
