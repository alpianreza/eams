<div class="card checklist-card checklist-form-card no-lift">
  <div class="card-body checklist-content">

    <div class="mb-3">
      <h5 class="fw-bold mb-1">
        <?= esc($inventory['item_display_name']) ?>
        <span class="text-muted fw-normal">- <?= esc($inventory['asset_code']) ?></span>
      </h5>

      <div class="d-flex flex-wrap gap-2 align-items-center small text-muted">
        <span class="badge bg-info text-dark">Frekuensi: <?= strtoupper($frequency) ?></span>

        <?php if (! empty($period_label)): ?>
          <span>Periode aktif: <strong><?= esc($period_label) ?></strong></span>
        <?php else: ?>
          <span class="fst-italic">Silakan pilih periode ceklis.</span>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($slots)): ?>
      <div class="mb-3 checklist-slot-links">
        <label class="form-label small text-muted mb-1">Pilih Waktu</label>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($slots as $key => $label): ?>
            <a
              class="btn btn-outline-primary btn-sm <?= ($slot ?? '') === $key ? 'active' : '' ?>"
              href="<?= site_url('compliance/checklist/' . $inventory['id']) ?>?period_key=<?= $period_key ?>&ym=<?= $navYM ?? date('Y-m') ?>&slot=<?= $key ?>">
              <?= $key ?> <span class="small text-muted">(<?= $label ?>)</span>
            </a>
          <?php endforeach ?>
        </div>
      </div>
    <?php endif ?>

    <?php if (empty($period_key)): ?>
      <div class="alert alert-light border d-flex align-items-center gap-2">
        <i class="bi bi-calendar-event"></i>
        Silakan pilih periode ceklis terlebih dahulu.
      </div>

    <?php elseif ($isLocked): ?>

      <?php if ($lockReason === 'done'): ?>
        <div class="alert alert-info d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill"></i>
          Ceklis untuk periode ini sudah diisi.
        </div>

      <?php elseif ($lockReason === 'future'): ?>
        <div class="alert alert-secondary d-flex align-items-center gap-2">
          <i class="bi bi-lock-fill"></i>
          Ceklis untuk periode ini belum dapat diisi.
        </div>

      <?php elseif ($lockReason === 'expired'): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2">
          <i class="bi bi-exclamation-triangle-fill"></i>
          Ceklis untuk periode ini sudah melewati batas pengisian.
        </div>

      <?php elseif ($lockReason === 'offday'): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2">
          <i class="bi bi-calendar-x-fill"></i>
          Hari ini adalah hari libur. Ceklis tidak dapat diisi.
        </div>

      <?php elseif ($lockReason === 'slot'): ?>
        <div class="alert alert-secondary d-flex align-items-center gap-2">
          <i class="bi bi-clock"></i>
          Silakan pilih waktu terlebih dahulu.
        </div>
      <?php endif; ?>

    <?php elseif (! empty($questions)): ?>

      <form
        id="checklistForm"
        action="<?= site_url('compliance/checklist/submit') ?>"
        method="post"
        enctype="multipart/form-data">

        <?= csrf_field() ?>

        <input type="hidden" name="inventory_id" value="<?= $inventory['id'] ?>">
        <input type="hidden" name="item_type_id" value="<?= $inventory['item_type_id'] ?>">
        <input type="hidden" name="period_key" value="<?= $period_key ?>">
        <input type="hidden" name="frequency" value="<?= $frequency ?>">
        <input type="hidden" name="time_slot" id="time_slot_hidden" value="<?= esc($slot ?? '') ?>">

        <div class="checklist-progress card border-0 mb-3">
          <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center mb-1 small">
              <span class="fw-semibold">Progress Isian</span>
              <span id="checklistProgressValue">0/<?= count($questions) ?></span>
            </div>
            <div class="progress" role="progressbar" aria-label="Progress checklist" aria-valuemin="0" aria-valuemax="100">
              <div id="checklistProgressBar" class="progress-bar" style="width: 0%;"></div>
            </div>
            <div id="checklistProgressText" class="small text-muted mt-1">Pilih status untuk setiap pertanyaan.</div>
          </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 checklist-action-bar">
          <small class="text-muted">Pastikan item yang <strong>Tidak Sesuai</strong> memiliki catatan atau foto.</small>
          <button type="button" class="btn btn-outline-success btn-sm" id="btn-ok-all">
            <i class="bi bi-check2-square me-1"></i>
            Tandai Semua Sesuai
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-checklist align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th width="6%" class="text-center">No</th>
                <th>Pertanyaan</th>
                <th width="35%" class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($questions as $i => $q): ?>
                <tr class="question-row">
                  <td class="text-center"><?= $i + 1 ?></td>
                  <td><?= esc($q['question']) ?></td>
                  <td class="text-center">
                    <div class="status-group d-flex justify-content-center flex-wrap gap-2 mb-1">
                      <input
                        type="radio"
                        class="btn-check status-radio"
                        name="questions[<?= $q['id'] ?>]"
                        id="ok-<?= $q['id'] ?>"
                        value="ok"
                        data-qid="<?= $q['id'] ?>">

                      <label class="btn btn-outline-success btn-sm d-flex align-items-center gap-1" for="ok-<?= $q['id'] ?>">
                        <i class="bi bi-check-circle"></i>
                        Sesuai
                      </label>

                      <input
                        type="radio"
                        class="btn-check status-radio"
                        name="questions[<?= $q['id'] ?>]"
                        id="not_ok-<?= $q['id'] ?>"
                        value="not_ok"
                        data-qid="<?= $q['id'] ?>">

                      <label class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" for="not_ok-<?= $q['id'] ?>">
                        <i class="bi bi-x-circle"></i>
                        Tidak Sesuai
                      </label>

                      <?php if (!empty($inventory['allow_na'])): ?>
                        <input
                          type="radio"
                          class="btn-check status-radio"
                          name="questions[<?= $q['id'] ?>]"
                          id="na-<?= $q['id'] ?>"
                          value="na"
                          data-qid="<?= $q['id'] ?>">

                        <label class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" for="na-<?= $q['id'] ?>">
                          <i class="bi bi-dash-circle"></i>
                          NA
                        </label>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>

                <tr class="not-ok-row d-none" id="not_ok-row-<?= $q['id'] ?>">
                  <td colspan="3" class="p-2">
                    <div class="not-ok-fields text-start">
                      <div class="small text-warning fw-semibold mb-2 d-flex align-items-center gap-1">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        TIDAK SESUAI - Isi catatan atau unggah foto
                      </div>

                      <textarea
                        name="remarks[<?= $q['id'] ?>]"
                        class="form-control form-control-sm mb-2"
                        rows="2"
                        placeholder="Catatan (jika diperlukan)"></textarea>

                      <input
                        type="file"
                        name="photos[<?= $q['id'] ?>]"
                        class="form-control form-control-sm"
                        accept="image/*"
                        capture="environment">
                    </div>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>

        <div class="mt-3 d-flex justify-content-end">
          <button class="btn btn-success d-inline-flex align-items-center gap-1">
            <i class="bi bi-save"></i>
            Simpan Ceklis
          </button>
        </div>
      </form>

    <?php else: ?>
      <div class="alert alert-warning">Tidak ada pertanyaan checklist untuk periode ini.</div>
    <?php endif ?>
  </div>
</div>
