<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card">

  <div class="card-header">
    <h3 class="card-title">Compliance Print Center</h3>
  </div>

  <div class="card-body">

    <div class="row text-center mb-4">

      <div class="col-md-6">
        <button id="btnPrintItem" class="btn btn-outline-primary btn-lg btn-block">
          <i class="fas fa-print"></i><br>
          Print Per Inventory
        </button>
      </div>

      <div class="col-md-6">
        <button id="btnPrintBatch" class="btn btn-outline-success btn-lg btn-block">
          <i class="fas fa-layer-group"></i><br>
          Print Batch
        </button>
      </div>

    </div>

    <!-- AJAX CONTAINER -->
    <div id="printContent"></div>

  </div>

</div>

<script>
  const compliancePrintBase = "<?= esc(parse_url(site_url('compliance/print'), PHP_URL_PATH) ?: '/compliance/print', 'js') ?>";

  function loadPrintContent(url) {

    document.getElementById("printContent").innerHTML =
      '<div class="text-center p-4">Loading...</div>';

    fetch(url)
      .then(res => res.text())
      .then(html => {
        document.getElementById("printContent").innerHTML = html;
      });

  }

  document.getElementById("btnPrintItem").onclick = function() {
    loadPrintContent(compliancePrintBase + "/item");
  }

  document.getElementById("btnPrintBatch").onclick = function() {
    loadPrintContent(compliancePrintBase + "/batch");
  }
</script>
<script>
  document.addEventListener("change", function(e) {

    if (e.target.id === "itemTypeSelect") {

      let itemType = e.target.value

      if (!itemType) {
        document.getElementById("inventoryContainer").innerHTML = ""
        document.getElementById("periodContainer").innerHTML = ""
        return
      }

      // ambil frequency dari dropdown
      let freq = e.target.selectedOptions[0].dataset.frequency

      // load inventory
      fetch(compliancePrintBase + "/inventory/" + itemType)
        .then(res => res.text())
        .then(html => {

          document.getElementById("inventoryContainer").innerHTML = html

        })

      // render period selector
      renderPeriod(freq)

    }

    if (e.target.id === "batchItemTypeSelect") {

      let selected = e.target.selectedOptions[0] || null
      let freq = selected ? (selected.dataset.frequency || "") : ""
      let periodContainer = document.getElementById("batchPeriodContainer")
      let monthGroup = document.getElementById("batchMonthGroup")
      let yearGroup = document.getElementById("batchYearGroup")

      if (!periodContainer) {
        return
      }

      if (!freq) {
        periodContainer.style.display = "none"
        return
      }

      periodContainer.style.display = "flex"

      if (monthGroup) {
        monthGroup.style.display = (freq === "daily" || freq === "weekly") ? "block" : "none"
      }

      if (yearGroup) {
        yearGroup.className = (freq === "daily" || freq === "weekly") ? "col-md-6" : "col-md-12"
      }
    }

  })


  function renderPeriod(freq) {

    let html = ""

    html += `<hr>`
    html += `<div class="row">`

    // ===== TAHUN =====
    html += `
    <div class="col-md-4">
        <h6>Pilih Tahun</h6>
    `

    for (let y = 2026; y <= 2027; y++) {

      html += `
        <div class="form-check">
            <input class="form-check-input yearCheck" type="checkbox" value="${y}">
            <label class="form-check-label">${y}</label>
        </div>
        `
    }

    html += `</div>`


    // ===== BULAN =====
    if (freq === "daily" || freq === "weekly") {

      html += `
        <div class="col-md-8">
            <h6>Pilih Bulan</h6>

            <div class="row">
        `

      for (let m = 1; m <= 12; m++) {

        html += `
            <div class="col-3 mb-2">

                <div class="form-check">

                    <input class="form-check-input monthCheck"
                           type="checkbox"
                           value="${m}">

                    <label class="form-check-label">
                        ${m}
                    </label>

                </div>

            </div>
            `
      }

      html += `</div></div>`
    }

    html += `</div>`

    // ===== PRINT BUTTON =====

    html += `
    <div class="text-right mt-3">

        <button id="btnPreviewPrint" class="btn btn-primary">
            <i class="fas fa-print"></i> Print
        </button>

    </div>
    `

    document.getElementById("periodContainer").innerHTML = html

  }

  document.addEventListener("click", function(e) {

    if (e.target.closest("#btnPreviewPrint")) {

      let inventory = []
      let year = []
      let month = []

      document.querySelectorAll(".inventoryCheck:checked").forEach(el => {
        inventory.push(el.value)
      })

      document.querySelectorAll(".yearCheck:checked").forEach(el => {
        year.push(el.value)
      })

      document.querySelectorAll(".monthCheck:checked").forEach(el => {
        month.push(el.value)
      })

      if (inventory.length === 0) {
        alert("Pilih inventory dulu")
        return
      }

      if (year.length === 0) {
        alert("Pilih tahun")
        return
      }

      let url =
        compliancePrintBase + "/item/preview" +
        "?inventory=" + inventory.join(",") +
        "&year=" + year.join(",") +
        "&month=" + month.join(",")

      window.open(url, "_blank")

    }

    if (e.target.closest("#btnPreviewBatchPrint")) {

      let itemTypeId = document.getElementById("batchItemTypeSelect")?.value || ""
      let monthValue = document.getElementById("batchMonthSelect")?.value || ""
      let yearValue = document.getElementById("batchYearSelect")?.value || ""

      if (!itemTypeId) {
        alert("Pilih item type dulu")
        return
      }

      let url =
        compliancePrintBase + "/batch/preview" +
        "?item_type_id=" + encodeURIComponent(itemTypeId) +
        "&month=" + encodeURIComponent(monthValue) +
        "&year=" + encodeURIComponent(yearValue)

      window.open(url, "_blank")
    }

  })
</script>

<?= $this->endSection() ?>
