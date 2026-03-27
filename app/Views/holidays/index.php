<?= $this->extend('layouts/main') ?>

<?php
$title = 'Hari Libur';
$currentYear = (int) date('Y');
$selectedYear = (int) $year;
$minYear = 2026;
$maxYear = max($currentYear + 1, $selectedYear);
$years = range($minYear, $maxYear);
rsort($years);

$dayMap = [
  'Sunday' => 'Minggu',
  'Monday' => 'Senin',
  'Tuesday' => 'Selasa',
  'Wednesday' => 'Rabu',
  'Thursday' => 'Kamis',
  'Friday' => 'Jumat',
  'Saturday' => 'Sabtu',
];
?>

<?= $this->section('content') ?>
<div class="holiday-page">
  <section class="card border-0 shadow-sm holiday-hero-card no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="holiday-kicker mb-1">Kalender Compliance</p>
        <h5 class="fw-bold mb-1">Kelola Hari Libur Nasional</h5>
        <p class="text-muted mb-0">Hari libur dipakai untuk perhitungan checklist harian.</p>
      </div>

      <form method="get" class="holiday-year-form ms-auto">
        <label for="yearFilter" class="form-label form-label-sm mb-1">Tahun</label>
        <div class="d-flex gap-2">
          <select id="yearFilter" name="year" class="form-select form-select-sm">
            <?php foreach ($years as $y): ?>
              <option value="<?= (int) $y ?>" <?= $selectedYear === (int) $y ? 'selected' : '' ?>>
                <?= (int) $y ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary btn-sm px-3" type="submit">Terapkan</button>
        </div>
      </form>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift mb-3">
    <div class="card-body">
      <h6 class="fw-semibold mb-3">Tambah Hari Libur</h6>

      <form method="post" action="<?= site_url('holidays/store') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>

        <div class="col-md-3">
          <label for="holidayDate" class="form-label mb-1">Tanggal</label>
          <input id="holidayDate" type="date" name="holiday_date" class="form-control" required>
        </div>

        <div class="col-md-7">
          <label for="holidayDescription" class="form-label mb-1">Keterangan</label>
          <input
            id="holidayDescription"
            type="text"
            name="description"
            class="form-control"
            placeholder="Contoh: Hari Raya Idul Fitri"
            required>
        </div>

        <div class="col-md-2">
          <button class="btn btn-success w-100" type="submit">
            <i class="bi bi-plus-lg me-1"></i>
            Tambah
          </button>
        </div>
      </form>
    </div>
  </section>

  <section class="card border-0 shadow-sm no-lift">
    <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
      <h6 class="fw-semibold mb-0">Daftar Hari Libur <?= esc((string) $selectedYear) ?></h6>
      <span class="badge bg-light text-dark border"><?= count($holidays) ?> data</span>
    </div>

    <div class="card-body pt-2">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 holiday-table">
          <thead>
            <tr>
              <th width="170">Tanggal</th>
              <th width="120">Hari</th>
              <th>Keterangan</th>
              <th width="150" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($holidays)): ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-4">
                  Belum ada data hari libur untuk tahun ini.
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach ($holidays as $h): ?>
              <?php
              $dateObj = strtotime($h['holiday_date']);
              $dayName = date('l', $dateObj);
              $dayLabel = $dayMap[$dayName] ?? $dayName;
              ?>

              <tr>
                <td>
                  <span class="fw-semibold"><?= esc(date('d M Y', $dateObj)) ?></span>
                </td>
                <td>
                  <span class="badge bg-light text-dark border"><?= esc($dayLabel) ?></span>
                </td>
                <td><?= esc($h['description']) ?></td>
                <td class="text-center">
                  <div class="d-inline-flex gap-1">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-warning"
                      data-bs-toggle="modal"
                      data-bs-target="#editModal<?= (int) $h['id'] ?>">
                      <i class="bi bi-pencil-square"></i>
                    </button>

                    <?php if (session('role') === 'admin'): ?>
                      <form method="post" action="<?= site_url('holidays/delete/' . (int) $h['id']) ?>" class="form-delete d-inline">
                        <?= csrf_field() ?>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-danger btn-delete"
                          data-name="<?= esc($h['description'], 'attr') ?>">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>

              <div class="modal fade holiday-modal" id="editModal<?= (int) $h['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content holiday-modal-content">
                    <form method="post" action="<?= site_url('holidays/update/' . (int) $h['id']) ?>">
                      <?= csrf_field() ?>

                      <div class="modal-header holiday-modal-header">
                        <h5 class="modal-title mb-0">Edit Hari Libur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                      </div>

                      <div class="modal-body holiday-modal-body">
                        <div class="mb-3">
                          <label class="form-label">Tanggal</label>
                          <input
                            type="date"
                            name="holiday_date"
                            class="form-control"
                            value="<?= esc($h['holiday_date']) ?>"
                            required>
                        </div>

                        <div class="mb-1">
                          <label class="form-label">Keterangan</label>
                          <input
                            type="text"
                            name="description"
                            class="form-control"
                            value="<?= esc($h['description']) ?>"
                            required>
                        </div>
                      </div>

                      <div class="modal-footer holiday-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/holidays.css?v=' . filemtime(FCPATH . 'assets/css/holidays.css')) ?>">
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('click', function(event) {
    const button = event.target.closest('.btn-delete');
    if (!button) {
      return;
    }

    const form = button.closest('.form-delete');
    if (!form) {
      return;
    }

    const holidayName = button.getAttribute('data-name') || 'hari libur ini';

    Swal.fire({
      title: 'Hapus hari libur?',
      html: `Data <b>${holidayName}</b> akan dihapus.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  });
</script>
<?= $this->endSection() ?>
