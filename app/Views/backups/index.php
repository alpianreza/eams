<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  $relative = static function (string $uri): string {
    $path = parse_url(base_url($uri), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : base_url($uri);
  };
?>

<div class="row g-3">
  <div class="col-12">
    <div class="card no-lift border-0 shadow-sm">
      <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div class="pe-xl-4">
          <p class="text-uppercase text-muted fw-semibold small mb-1">Admin</p>
          <h4 class="fw-bold mb-2">Backup Sistem</h4>
          <p class="text-muted mb-0">Kelola backup manual, restore penuh, upload backup, dan backup otomatis harian dari satu halaman yang lebih rapi.</p>
          <div class="small text-muted mt-3 d-flex flex-wrap gap-2 align-items-center">
            <span>Lokasi backup aktif:</span>
            <code><?= esc($backupDirectoryPath) ?></code>
            <?php if ($usingExternalDrive): ?>
              <span class="badge text-bg-success">Drive D aktif</span>
            <?php endif; ?>
            <span class="badge text-bg-secondary">Retensi <?= esc((string) $retentionDays) ?> hari</span>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <form method="post" action="<?= esc($relative('backups/database')) ?>">
            <button type="submit" class="btn btn-outline-success">
              <i class="bi bi-database-add me-1"></i> Backup Database
            </button>
          </form>
          <form method="post" action="<?= esc($relative('backups/files')) ?>">
            <button type="submit" class="btn btn-outline-info">
              <i class="bi bi-folder-symlink me-1"></i> Backup File
            </button>
          </form>
          <form method="post" action="<?= esc($relative('backups/full')) ?>">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-shield-check me-1"></i> Backup Penuh
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-4">
    <div class="card no-lift h-100 border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
          <div>
            <h5 class="fw-bold mb-1">Backup Otomatis</h5>
            <p class="text-muted small mb-0">Backup penuh harian disimpan ke folder aktif dan backup lebih lama dari <?= esc((string) $retentionDays) ?> hari akan dibersihkan otomatis.</p>
          </div>
          <?php if (!empty($autoBackupStatus['active'])): ?>
            <span class="badge text-bg-success">Aktif</span>
          <?php else: ?>
            <span class="badge text-bg-secondary">Belum aktif</span>
          <?php endif; ?>
        </div>

        <div class="border rounded-4 p-3 bg-body-tertiary small mb-3">
          <div class="fw-semibold mb-2"><?= esc($autoBackupStatus['message'] ?? 'Status belum tersedia.') ?></div>
          <div class="text-muted">Jadwal default: setiap hari pukul <?= esc($defaultScheduleTime) ?></div>
          <div class="text-muted mt-1">Next run: <?= esc($autoBackupStatus['next_run'] ?? '-') ?></div>
          <div class="text-muted mt-1">Run as: <?= esc($autoBackupStatus['run_as'] ?? '-') ?></div>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <?php if (!empty($autoBackupStatus['active'])): ?>
            <form method="post" action="<?= esc($relative('backups/auto-disable')) ?>" class="js-disable-auto-backup-form">
              <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-pause-circle me-1"></i> Matikan Otomatis
              </button>
            </form>
          <?php else: ?>
            <form method="post" action="<?= esc($relative('backups/auto-enable')) ?>" class="js-enable-auto-backup-form">
              <button type="submit" class="btn btn-success">
                <i class="bi bi-play-circle me-1"></i> Aktifkan Otomatis
              </button>
            </form>
          <?php endif; ?>
        </div>

        <hr class="my-4">

        <h5 class="fw-bold mb-2">Upload Backup</h5>
        <p class="text-muted small mb-3">Upload file backup `.sql` atau `.zip` supaya bisa langsung dikelola dari halaman ini.</p>

        <form method="post" action="<?= esc($relative('backups/upload')) ?>" enctype="multipart/form-data" class="d-grid gap-3">
          <div>
            <label for="backup-file" class="form-label small fw-semibold">Pilih file backup</label>
            <input type="file" class="form-control" id="backup-file" name="backup_file" accept=".sql,.zip" required>
          </div>
          <button type="submit" class="btn btn-outline-primary">
            <i class="bi bi-upload me-1"></i> Upload Backup
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-8">
    <div class="card no-lift border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <div>
            <h5 class="fw-bold mb-1">Riwayat Backup</h5>
            <div class="text-muted small">Restore penuh akan memulihkan database dan file upload sekaligus. Tombol restore lain tetap tersedia kalau ingin parsial.</div>
          </div>
          <span class="badge text-bg-secondary"><?= count($backups) ?> file</span>
        </div>

        <?php if (empty($backups)): ?>
          <div class="text-center py-5 text-muted">
            Belum ada file backup yang tersedia.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th>Nama File</th>
                  <th>Jenis</th>
                  <th>Ukuran</th>
                  <th>Waktu</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($backups as $backup): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= esc($backup['name']) ?></div>
                    </td>
                    <td>
                      <span class="badge <?= esc($backup['type']['class']) ?>"><?= esc($backup['type']['label']) ?></span>
                    </td>
                    <td><?= esc($backup['size']) ?></td>
                    <td><?= esc($backup['modified_at']) ?></td>
                    <td class="text-end">
                      <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                        <a href="<?= esc($backup['download_url']) ?>" class="btn btn-outline-primary btn-sm">
                          <i class="bi bi-download me-1"></i> Download
                        </a>
                        <?php if (!empty($backup['restore_full_url'])): ?>
                          <form method="post" action="<?= esc($backup['restore_full_url']) ?>" class="js-restore-full-form">
                            <button type="submit" class="btn btn-primary btn-sm">
                              <i class="bi bi-arrow-repeat me-1"></i> Restore Penuh
                            </button>
                          </form>
                        <?php endif; ?>
                        <?php if (!empty($backup['restore_database_url'])): ?>
                          <form method="post" action="<?= esc($backup['restore_database_url']) ?>" class="js-restore-database-form">
                            <button type="submit" class="btn btn-outline-success btn-sm">
                              <i class="bi bi-database-check me-1"></i> Restore DB
                            </button>
                          </form>
                        <?php endif; ?>
                        <?php if (!empty($backup['restore_files_url'])): ?>
                          <form method="post" action="<?= esc($backup['restore_files_url']) ?>" class="js-restore-files-form">
                            <button type="submit" class="btn btn-outline-info btn-sm">
                              <i class="bi bi-folder-check me-1"></i> Restore File
                            </button>
                          </form>
                        <?php endif; ?>
                        <form method="post" action="<?= esc($backup['delete_url']) ?>" class="js-backup-delete-form">
                          <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i> Hapus
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.querySelectorAll('.js-enable-auto-backup-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();

      Swal.fire({
        title: 'Aktifkan backup otomatis harian?',
        text: 'Task Windows akan dibuat supaya backup penuh jalan setiap hari pukul 01:00 dan backup lama di atas 30 hari dibersihkan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, aktifkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d'
      }).then(function(result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  document.querySelectorAll('.js-disable-auto-backup-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();

      Swal.fire({
        title: 'Matikan backup otomatis?',
        text: 'Task backup harian akan dihapus dari Windows Task Scheduler.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, matikan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d'
      }).then(function(result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  document.querySelectorAll('.js-restore-full-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();

      Swal.fire({
        title: 'Restore penuh dari file ini?',
        text: 'Database aktif dan file upload akan ditimpa sekaligus oleh backup yang dipilih.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, restore penuh',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d'
      }).then(function(result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  document.querySelectorAll('.js-restore-database-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();

      Swal.fire({
        title: 'Restore database dari file ini?',
        text: 'Isi database saat ini akan ditimpa oleh backup yang dipilih.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, restore database',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d'
      }).then(function(result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  document.querySelectorAll('.js-restore-files-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();

      Swal.fire({
        title: 'Restore file upload dari backup ini?',
        text: 'File upload aktif akan ditimpa jika ada nama file yang sama.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, restore file',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0dcaf0',
        cancelButtonColor: '#6c757d'
      }).then(function(result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  document.querySelectorAll('.js-backup-delete-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();

      Swal.fire({
        title: 'Hapus file backup ini?',
        text: 'File backup yang dihapus tidak bisa dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d'
      }).then(function(result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
<?= $this->endSection() ?>
