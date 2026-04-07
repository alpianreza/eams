<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  $relative = static function (string $uri): string {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  };
  $formAction = $mode === 'create'
    ? $relative('compliance/questionnaires/store')
    : $relative('compliance/questionnaires/update/' . $questionnaire['id']);
  $cancelPath = !empty($questionnaire['id'])
    ? $relative('compliance/questionnaires/' . $questionnaire['id'])
    : $relative('compliance/questionnaires');
?>

<div class="questionnaire-page">
  <div class="card questionnaire-form-card no-lift">
    <div class="card-body">
      <div class="mb-4">
        <p class="questionnaire-kicker mb-1">Template Kuesioner</p>
        <h4 class="fw-bold mb-1"><?= $mode === 'create' ? 'Tambah Kuesioner Baru' : 'Edit Kuesioner' ?></h4>
        <p class="text-muted mb-0">Atur judul, deskripsi, dan status kuesioner. Pertanyaan bisa dikelola setelah template tersimpan.</p>
      </div>

      <form method="post" action="<?= esc($formAction) ?>" class="row g-3">
        <div class="col-12 col-lg-7">
          <label class="form-label">Judul</label>
          <input type="text" name="title" class="form-control" value="<?= esc(old('title', $questionnaire['title'] ?? '')) ?>" required>
        </div>

        <div class="col-12 col-lg-5">
          <label class="form-label">Slug</label>
          <input type="text" name="slug" class="form-control" value="<?= esc(old('slug', $questionnaire['slug'] ?? '')) ?>" placeholder="otomatis jika kosong">
        </div>

        <div class="col-12 col-lg-8">
          <label class="form-label">Subjudul</label>
          <input type="text" name="subtitle" class="form-control" value="<?= esc(old('subtitle', $questionnaire['subtitle'] ?? '')) ?>">
        </div>

        <div class="col-12 col-lg-2">
          <label class="form-label">Urutan</label>
          <input type="number" name="sort_order" class="form-control" value="<?= esc((string) old('sort_order', $questionnaire['sort_order'] ?? 0)) ?>">
        </div>

        <div class="col-12 col-lg-2 d-flex align-items-end">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="activeSwitch" name="active" value="1" <?= (int) old('active', $questionnaire['active'] ?? 1) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="activeSwitch">Aktif</label>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Deskripsi / Instruksi</label>
          <textarea name="description" rows="4" class="form-control" placeholder="Instruksi yang akan tampil pada form dan PDF"><?= esc(old('description', $questionnaire['description'] ?? '')) ?></textarea>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
          <a href="<?= esc($cancelPath) ?>" class="btn btn-outline-secondary">Batal</a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= esc($relative('assets/css/questionnaire.css') . '?v=' . filemtime(FCPATH . 'assets/css/questionnaire.css')) ?>">
<?= $this->endSection() ?>
