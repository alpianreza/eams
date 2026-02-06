<div class="card-body checklist-content">

  <!-- HEADER -->
  <div class="mb-3">
    <h5 class="fw-bold mb-1">
      <?= esc($inventory['item_display_name']) ?>
      <span class="text-muted fw-normal">– <?= esc($inventory['asset_code']) ?></span>
    </h5>

    <div class="d-flex flex-wrap gap-3 small text-muted">
      <div>
        Frekuensi:
        <span class="badge bg-info text-dark">
          <?= strtoupper($frequency) ?>
        </span>
      </div>

      <div>
        <?php if (! empty($period_label)): ?>
          Periode aktif:
          <strong><?= esc($period_label) ?></strong>
        <?php else: ?>
          <span class="fst-italic text-muted">
            Silakan pilih periode checklist
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <hr class="mb-3">

  <?php if (empty($period_key)): ?>

    <!-- BELUM PILIH PERIODE -->
    <div class="alert alert-light border d-flex align-items-center gap-2">
      <i class="bi bi-calendar-event"></i>
      Silakan pilih periode checklist terlebih dahulu.
    </div>

  <?php elseif ($isLocked): ?>

    <?php if ($lockReason === 'done'): ?>
      <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i>
        Checklist untuk periode ini <strong>sudah diisi</strong> dan terkunci.
      </div>

    <?php elseif ($lockReason === 'future'): ?>
      <div class="alert alert-secondary d-flex align-items-center gap-2">
        <i class="bi bi-lock-fill"></i>
        Checklist untuk periode ini <strong>belum dapat diisi</strong>.
      </div>

    <?php elseif ($lockReason === 'expired'): ?>
      <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Checklist untuk periode ini <strong>sudah melewati batas pengisian</strong>.
      </div>
    <?php endif; ?>


  <?php elseif (! empty($questions)): ?>

    <!-- FORM CHECKLIST -->
    <form id="checklistForm"
      action="<?= base_url('compliance/checklist/submit') ?>"
      method="post"
      enctype="multipart/form-data">

      <?= csrf_field() ?>

      <input type="hidden" name="inventory_id" value="<?= $inventory['id'] ?>">
      <input type="hidden" name="item_type_id" value="<?= $inventory['item_type_id'] ?>">
      <input type="hidden" name="period_key" value="<?= $period_key ?>">
      <input type="hidden" name="frequency" value="<?= $frequency ?>">

      <!-- ACTION BAR -->
      <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-outline-success btn-sm" id="btn-ok-all">
          <i class="bi bi-check2-square me-1"></i>
          Tandai Semua OK
        </button>
      </div>

      <!-- TABLE -->
      <div class="table-responsive">
        <table class="table table-bordered table-checklist align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="5%" class="text-center">No</th>
              <th>Pertanyaan</th>
              <th width="32%" class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>

            <?php foreach ($questions as $i => $q): ?>
              <tr>
                <td class="text-center"><?= $i + 1 ?></td>
                <td><?= esc($q['question']) ?></td>
                <td class="text-center">

                  <!-- STATUS -->
                  <div class="status-group d-flex justify-content-center gap-2 mb-1">

                    <input type="radio"
                      class="btn-check status-radio"
                      name="questions[<?= $q['id'] ?>]"
                      id="ok-<?= $q['id'] ?>"
                      value="ok"
                      data-qid="<?= $q['id'] ?>"
                      required>

                    <label class="btn btn-outline-success btn-sm d-flex align-items-center gap-1"
                      for="ok-<?= $q['id'] ?>">
                      <i class="bi bi-check-circle"></i>
                      OK
                    </label>

                    <input type="radio"
                      class="btn-check status-radio"
                      name="questions[<?= $q['id'] ?>]"
                      id="ng-<?= $q['id'] ?>"
                      value="ng"
                      data-qid="<?= $q['id'] ?>"
                      required>

                    <label class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1"
                      for="ng-<?= $q['id'] ?>">
                      <i class="bi bi-x-circle"></i>
                      NOT OK
                    </label>
                  </div>

                  <!-- NG ALERT -->
                  <div class="alert alert-warning ng-alert d-none py-2 px-2 small text-start"
                    id="ng-alert-<?= $q['id'] ?>">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Item ini <strong>TIDAK SESUAI</strong>. Mohon isi catatan atau foto.
                  </div>

                  <!-- NG FIELDS -->
                  <div class="ng-fields d-none mt-2 text-start"
                    id="ng-fields-<?= $q['id'] ?>">

                    <textarea
                      name="remarks[<?= $q['id'] ?>]"
                      class="form-control form-control-sm mb-2"
                      rows="2"
                      placeholder="Catatan (wajib jika tidak sesuai)"></textarea>

                    <input type="file"
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

      <!-- SUBMIT -->
      <div class="mt-3 d-flex justify-content-end">
        <button class="btn btn-success">
          <i class="bi bi-save me-1"></i>
          Simpan Checklist
        </button>
      </div>

    </form>

  <?php else: ?>

    <div class="alert alert-warning">
      Tidak ada checklist untuk periode ini.
    </div>

  <?php endif ?>

</div>