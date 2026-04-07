<div class="card">
  <div class="card-body">

    <h5>Print Batch / Form Kolektif</h5>
    <p class="text-muted mb-3">
      Format ini terpisah dari laporan. Pilih item type untuk mencetak PDF kolektif beserta finding dan foto yang berstatus Tidak sesuai.
    </p>

    <div class="form-group">
      <label for="batchItemTypeSelect">Item Type</label>
      <select id="batchItemTypeSelect" class="form-control">
        <option value="">-- pilih item --</option>

        <?php foreach ($itemTypes as $it): ?>
          <option
            value="<?= esc($it['id']) ?>"
            data-frequency="<?= esc($it['checklist_frequency'] ?? '') ?>">
            <?= esc($it['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div id="batchPeriodContainer" class="row mt-3" style="display:none;">
      <div class="col-md-6">
        <label for="batchMonthSelect">Bulan</label>
        <select id="batchMonthSelect" class="form-control">
          <?php
          $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
          ];
          $currentMonth = (int) date('n');
          foreach ($monthNames as $monthNumber => $monthLabel):
          ?>
            <option value="<?= $monthNumber ?>" <?= $currentMonth === $monthNumber ? 'selected' : '' ?>>
              <?= esc($monthLabel) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label for="batchYearSelect">Tahun</label>
        <select id="batchYearSelect" class="form-control">
          <?php
          $currentYear = (int) date('Y');
          for ($year = $currentYear - 1; $year <= $currentYear + 2; $year++):
          ?>
            <option value="<?= $year ?>" <?= $currentYear === $year ? 'selected' : '' ?>>
              <?= $year ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
      <button id="btnPreviewBatchPrint" class="btn btn-success">
        <i class="fas fa-print"></i> Preview Print
      </button>
    </div>

  </div>
</div>
