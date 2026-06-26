<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="thermal-page">
  <section class="card no-lift mb-3 thermal-hero">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="text-uppercase text-muted small fw-bold mb-1">Compliance</p>
        <h5 class="mb-1 fw-bold">Thermal Imaging Inspection Report</h5>
        <p class="text-muted mb-0">Form inspeksi thermal dengan lokasi.</p>
      </div>
      <a href="/compliance/thermal-imaging/create" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        Buat Report
      </a>
    </div>
  </section>

  <section class="card no-lift">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="56" class="text-center">No</th>
              <th>Tanggal</th>
              <th>Inspector</th>
              <th>Facility</th>
              <th width="180" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($reports)): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Belum ada thermal imaging report.</td>
              </tr>
            <?php endif; ?>

            <?php foreach ($reports as $index => $report): ?>
              <tr>
                <td class="text-center fw-semibold"><?= $index + 1 ?></td>
                <td><?= esc(date('d M Y', strtotime($report['inspection_date']))) ?></td>
                <td><?= esc($report['inspector_name']) ?></td>
                <td><?= esc($report['facility']) ?></td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <a href="/compliance/thermal-imaging/<?= (int) $report['id'] ?>" class="btn btn-outline-primary">
                      Output
                    </a>
                    <a href="/compliance/thermal-imaging/<?= (int) $report['id'] ?>/pdf" target="_blank" class="btn btn-outline-danger">
                      PDF
                    </a>
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

<?= $this->endSection() ?>
