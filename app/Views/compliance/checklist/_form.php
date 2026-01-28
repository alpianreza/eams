<div class="card-body checklist-content">

  <!-- TITLE -->
  <h5 class="checklist-title mb-1">
    <?= esc($inventory['item_display_name']) ?>
    -
    <?= esc($inventory['asset_code']) ?>
  </h5>

  <p class="text-muted mb-4">
    Frekuensi: <strong><?= strtoupper($frequency) ?></strong><br>
    Periode aktif: <strong><?= esc($period_label) ?></strong>
  </p>

  <?php if ($isLocked): ?>

    <div class="alert alert-info">
      Checklist untuk periode ini sudah diisi dan terkunci.
    </div>

  <?php elseif (! empty($questions)): ?>

    <form id="checklistForm"
      action="<?= base_url('compliance/checklist/submit') ?>"
      method="post"
      enctype="multipart/form-data">



      <?= csrf_field() ?>

      <input type="hidden" name="inventory_id" value="<?= $inventory['id'] ?>">
      <input type="hidden" name="item_type_id" value="<?= $inventory['item_type_id'] ?>">
      <input type="hidden" name="frequency" value="<?= $frequency ?>">
      <input type="hidden" name="period_key" value="<?= $period_key ?>">

      <!-- ACTION BAR -->
      <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-outline-success btn-sm" id="btn-ok-all">
          <i class="bi bi-check2-square me-1"></i>
          Tandai Semua OK
        </button>
      </div>

      <!-- TABLE RESPONSIVE -->
      <div class="table-responsive">
        <table class="table table-bordered table-checklist align-middle">
          <thead class="table-light">
            <tr>
              <th width="5%">No</th>
              <th>Pertanyaan</th>
              <th width="30%" class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>

            <?php foreach ($questions as $i => $q): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($q['question']) ?></td>
                <td class="text-center">

                  <!-- STATUS BUTTON -->
                  <div class="status-group d-flex justify-content-center gap-2">

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
                  <div class="alert alert-warning ng-alert d-none mt-2"
                    id="ng-alert-<?= $q['id'] ?>">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Item ini NOT OK. Mohon isi catatan dan foto.
                  </div>

                  <!-- NG FIELDS -->
                  <div class="ng-fields d-none mt-2"
                    id="ng-fields-<?= $q['id'] ?>">

                    <textarea
                      name="remarks[<?= $q['id'] ?>]"
                      class="form-control form-control-sm mb-2"
                      placeholder="Wajib diisi jika NOT OK"></textarea>

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
      <div class="mt-3">
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