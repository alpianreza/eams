let complianceChart;
let progressTrendChart;
let statusPieChart;

let currentType = "monthly";

document.addEventListener("DOMContentLoaded", function () {
  // INITIAL LOAD
  loadTrend(currentType);
  loadProgressTrend();
  loadStatusPie();
  loadRiskInsight();
  loadPendingChecklist();

  // =========================
  // TAB SWITCH (Trend + Pie ikut)
  // =========================
  document.querySelectorAll(".tab-frequency").forEach((btn) => {
    btn.addEventListener("click", function () {
      document
        .querySelectorAll(".tab-frequency")
        .forEach((b) => b.classList.remove("active"));

      this.classList.add("active");
      currentType = this.dataset.type;

      loadTrend(currentType);
      loadProgressTrend();
      loadStatusPie();
    });
  });

  // =========================
  // MONTH FILTER (Trend)
  // =========================
  const monthFilter = document.getElementById("monthFilter");
  if (monthFilter) {
    monthFilter.addEventListener("change", function () {
      loadTrend(currentType, this.value);
      loadStatusPie();
    });
  }

  // =========================
  // PROGRESS DROPDOWN
  // =========================
  document
    .querySelectorAll("#progressType, #progressYear, #progressMonth")
    .forEach((el) =>
      el.addEventListener("change", function () {
        loadProgressTrend();
        loadStatusPie();
        loadRiskInsight();
        loadRiskTrend();
      }),
    );

  // =========================
  // PENDING FILTERS
  // =========================
  document
    .getElementById("pendingSearch")
    ?.addEventListener("keyup", applySearchAndRender);

  document
    .getElementById("pendingSort")
    ?.addEventListener("change", applySearchAndRender);

  document
    .getElementById("pendingMonth")
    ?.addEventListener("change", loadPendingChecklist);

  document
    .getElementById("pendingFrequency")
    ?.addEventListener("change", loadPendingChecklist);
});

// ======================================================
// ===================== TREND ==========================
// ======================================================

// =========================
// TREND SECTION CLEAN
// =========================

function loadTrend(type) {
  const monthVal = document.getElementById("monthFilter")?.value || "";

  fetch(
    baseUrl +
      `/compliance/dashboard/trend?type=${type}&year=${selectedYear}&month=${monthVal}`,
  )
    .then((res) => res.json())
    .then((res) => {
      if (res.error) {
        console.error(res.error);
        return;
      }

      let grouped = {};

      res.forEach((row) => {
        if (!grouped[row.period_key]) {
          grouped[row.period_key] = { ok: 0, not_ok: 0, na: 0 };
        }
        grouped[row.period_key][row.status] = parseInt(row.total);
      });

      let labels = [];
      let okData = [];
      let notOkData = [];
      let naData = [];

      Object.keys(grouped)
        .sort()
        .forEach((key) => {
          labels.push(formatLabel(key, type));
          okData.push(grouped[key].ok || 0);
          notOkData.push(grouped[key].not_ok || 0);
          naData.push(grouped[key].na || 0);
        });

      renderTrendChart(labels, okData, notOkData, naData);
    });
}

function renderTrendChart(labels, okData, notOkData, naData) {
  const ctx = document.getElementById("complianceChart");

  if (complianceChart) complianceChart.destroy();

  complianceChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        {
          label: "✓ Sesuai",
          data: okData,
          tension: 0.3,
          fill: false,
        },
        {
          label: "✗ Tidak Sesuai",
          data: notOkData,
          tension: 0.3,
          fill: false,
        },
        {
          label: "– Tidak Berlaku",
          data: naData,
          tension: 0.3,
          fill: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: "index", intersect: false },
      scales: { y: { beginAtZero: true } },
    },
  });
}

// ======================================================
// =============== PROGRESS TREND =======================
// ======================================================

function loadProgressTrend() {
  const type = document.getElementById("progressType").value;
  const year = document.getElementById("progressYear").value;
  const month = document.getElementById("progressMonth").value;

  fetch(
    baseUrl +
      `/compliance/dashboard/progress-trend?type=${type}&year=${year}&month=${month}`,
  )
    .then((res) => res.json())
    .then((data) => {
      if (data.error) {
        console.error(data.error);
        return;
      }

      let labels = [];
      let sudahData = [];
      let belumData = [];

      data.forEach((row) => {
        labels.push(formatLabel(row.period_key, type));
        sudahData.push(parseInt(row.total));
      });

      // Ambil total inventory by frequency
      fetch(baseUrl + `/compliance/dashboard/total-inventory?type=${type}`)
        .then((res) => res.json())
        .then((totalRes) => {
          const total = totalRes.total;

          sudahData.forEach((val) => {
            belumData.push(total - val);
          });

          renderProgressTrend(labels, sudahData, belumData);
        });
    });
}

function renderProgressTrend(labels, sudahData, belumData) {
  const ctx = document.getElementById("progressChart");

  if (progressTrendChart) progressTrendChart.destroy();

  progressTrendChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Sudah Checklist",
          data: sudahData,
          tension: 0.4,
          fill: true,
        },
        {
          label: "Belum Checklist",
          data: belumData,
          tension: 0.4,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: "index", intersect: false },
      scales: { y: { beginAtZero: true } },
    },
  });
}

// ======================================================
// ======================= PIE ==========================
// ======================================================

function loadStatusPie() {
  const type = document.getElementById("progressType").value;
  const year = document.getElementById("progressYear").value;
  const month = document.getElementById("progressMonth").value;

  const params = new URLSearchParams({
    type: type,
    year: year,
    month: month,
  });

  fetch(baseUrl + `/compliance/dashboard/status-pie?` + params.toString())
    .then((res) => res.json())
    .then((data) => {
      if (data.error) {
        console.error(data.error);
        return;
      }

      renderStatusPie(parseInt(data.ok || 0), parseInt(data.not_ok || 0));
    });
}

function renderStatusPie(ok, notOk) {
  const ctx = document.getElementById("statusPieChart");

  if (statusPieChart) statusPieChart.destroy();

  statusPieChart = new Chart(ctx, {
    type: "pie",
    data: {
      labels: ["✓ Sesuai", "✗ Tidak Sesuai"],
      datasets: [
        {
          data: [ok, notOk],
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        tooltip: {
          callbacks: {
            label: function (context) {
              const total = ok + notOk;
              const value = context.raw;
              const percent =
                total > 0 ? ((value / total) * 100).toFixed(1) : 0;
              return `${context.label}: ${value} (${percent}%)`;
            },
          },
        },
      },
    },
  });
}

// ======================================================
// ================= LABEL FORMAT =======================
// ======================================================

function formatLabel(periodKey, type) {
  if (type === "monthly") {
    const month = periodKey.substring(5, 7);
    const date = new Date(2000, parseInt(month) - 1, 1);
    return date.toLocaleString("default", { month: "short" });
  }

  if (type === "weekly") {
    return periodKey.replace(/^\d{4}-\d{2}-/, "");
  }

  if (type === "daily") {
    return periodKey.substring(8, 10);
  }

  return periodKey;
}

let riskTrendChart;

// ==========================
// LOAD RISK INSIGHT
// ==========================
function loadRiskInsight() {
  const year = document.getElementById("progressYear").value;
  const month = document.getElementById("progressMonth").value;

  fetch(
    baseUrl + `/compliance/dashboard/risk-insight?year=${year}&month=${month}`,
  )
    .then((res) => res.json())
    .then((data) => {
      const itemList = document.getElementById("topItemRisk");
      const areaList = document.getElementById("topAreaRisk");

      itemList.innerHTML = "";
      areaList.innerHTML = "";

      // ======================
      // ITEMS
      // ======================
      if (data.items && data.items.length > 0) {
        data.items.forEach((row, index) => {
          itemList.innerHTML += `
            <li class="list-group-item d-flex align-items-center">

              <div class="flex-grow-1">
                <div class="fw-semibold">${row.item_name}</div>
              </div>

              <div class="me-3">
                <span class="badge bg-danger rounded-pill">${row.total}</span>
              </div>

              <div style="width:120px;">
                <span id="spark-item-${index}"></span>
              </div>

            </li>
          `;
        });

        // Render sparkline item
        data.items.forEach((row, index) => {
          if (row.trend && row.trend.length > 0) {
            $(`#spark-item-${index}`).sparkline(row.trend, {
              type: "line",
              width: "120",
              height: "35",
              lineColor: "#dc3545",
              fillColor: false,
              lineWidth: 2,
              spotRadius: 3,
              highlightSpotColor: "#000",
              highlightLineColor: "#000",
              chartRangeMin: 0,

              tooltipFormatter: function (sparkline, options, fields) {
                const months = [
                  "Jan",
                  "Feb",
                  "Mar",
                  "Apr",
                  "May",
                  "Jun",
                  "Jul",
                  "Aug",
                  "Sep",
                  "Oct",
                  "Nov",
                  "Dec",
                ];

                return `
      <div style="padding:4px 6px;">
        <strong>${months[fields.x]}</strong><br>
        ✗ ${fields.y}
      </div>
    `;
              },
            });
          }
        });
      } else {
        itemList.innerHTML = `<li class="text-muted">Tidak ada temuan</li>`;
      }

      // ======================
      // AREAS
      // ======================
      if (data.areas && data.areas.length > 0) {
        data.areas.forEach((row, index) => {
          areaList.innerHTML += `
            <li class="list-group-item d-flex align-items-center">

              <div class="flex-grow-1">
                <div class="fw-semibold">${row.specific_area}</div>
              </div>

              <div class="me-3">
                <span class="badge bg-danger rounded-pill">${row.total}</span>
              </div>

              <div style="width:120px;">
                <span id="spark-area-${index}"></span>
              </div>

            </li>
          `;
        });

        // Render sparkline area
        data.areas.forEach((row, index) => {
          if (row.trend && row.trend.length > 0) {
            $(`#spark-area-${index}`).sparkline(row.trend, {
              type: "line",
              width: "120",
              height: "35",
              lineColor: "#dc3545",
              fillColor: false,
              lineWidth: 2,
              spotRadius: 3,
              highlightSpotColor: "#000",
              highlightLineColor: "#000",
              chartRangeMin: 0,

              tooltipFormatter: function (sparkline, options, fields) {
                const months = [
                  "Jan",
                  "Feb",
                  "Mar",
                  "Apr",
                  "May",
                  "Jun",
                  "Jul",
                  "Aug",
                  "Sep",
                  "Oct",
                  "Nov",
                  "Dec",
                ];

                return `
      <div style="padding:4px 6px;">
        <strong>${months[fields.x]}</strong><br>
        ✗ ${fields.y}
      </div>
    `;
              },
            });
          }
        });
      } else {
        areaList.innerHTML = `<li class="text-muted">Tidak ada temuan</li>`;
      }
    })
    .catch((err) => {
      console.error("Risk Insight Error:", err);
    });
}

let pendingData = [];
let filteredData = [];
let currentPage = 1;
const perPage = 10;

function loadPendingChecklist() {
  const month = document.getElementById("pendingMonth")?.value || "";
  const frequency = document.getElementById("pendingFrequency")?.value || "";

  const params = new URLSearchParams({
    month: month,
    frequency: frequency,
  });

  fetch(
    baseUrl + "/compliance/dashboard/pending-checklist?" + params.toString(),
  )
    .then((res) => res.json())
    .then((data) => {
      pendingData = data;
      filteredData = data;
      currentPage = 1;
      applySearchAndRender(); // 🔥 penting
    });
}

function renderPendingTable() {
  const tbody = document.getElementById("pendingTableBody");
  const pagination = document.getElementById("pendingPagination");

  tbody.innerHTML = "";
  pagination.innerHTML = "";

  const dataSource = filteredData.length ? filteredData : pendingData;

  if (!dataSource.length) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-success">
          Tidak ada data 👍
        </td>
      </tr>
    `;
    return;
  }

  const start = (currentPage - 1) * perPage;
  const end = start + perPage;

  const pageData = dataSource.slice(start, end);

  pageData.forEach((row) => {
    const missingJson = JSON.stringify(row.missing).replace(/"/g, "&quot;");

    const badgeClass = getRiskBadgeClass(row);

    tbody.innerHTML += `
  <tr>
    <td>${row.item_name}</td>
    <td>${row.specific_area}</td>
    <td>
      <span class="badge bg-secondary">
        ${row.pic ?? "-"}
      </span>
    </td>
    <td>${row.frequency}</td>
    <td>
      <span class="badge ${badgeClass} pending-badge"
            style="cursor:pointer"
            data-missing="${missingJson}">
        ${row.status}
      </span>
    </td>
  </tr>
`;
  });

  renderPagination(dataSource.length);
}

function renderPagination(totalData) {
  const pagination = document.getElementById("pendingPagination");
  pagination.innerHTML = "";

  const totalPages = Math.ceil(totalData / perPage);
  if (totalPages <= 1) return;

  let start = Math.max(1, currentPage - 2);
  let end = Math.min(totalPages, currentPage + 2);

  if (currentPage > 1) {
    pagination.innerHTML += `
      <button class="btn btn-sm btn-outline-primary me-1"
              onclick="goToPage(${currentPage - 1})">«</button>
    `;
  }

  if (start > 1) {
    pagination.innerHTML += `
      <button class="btn btn-sm btn-outline-primary me-1"
              onclick="goToPage(1)">1</button>
    `;
    if (start > 2) pagination.innerHTML += `<span class="me-2">...</span>`;
  }

  for (let i = start; i <= end; i++) {
    pagination.innerHTML += `
      <button class="btn btn-sm ${i === currentPage ? "btn-primary" : "btn-outline-primary"} me-1"
              onclick="goToPage(${i})">${i}</button>
    `;
  }

  if (end < totalPages) {
    if (end < totalPages - 1)
      pagination.innerHTML += `<span class="me-2">...</span>`;
    pagination.innerHTML += `
      <button class="btn btn-sm btn-outline-primary me-1"
              onclick="goToPage(${totalPages})">${totalPages}</button>
    `;
  }

  if (currentPage < totalPages) {
    pagination.innerHTML += `
      <button class="btn btn-sm btn-outline-primary"
              onclick="goToPage(${currentPage + 1})">»</button>
    `;
  }
}

function goToPage(page) {
  // Tutup popover kalau ada
  if (activePopover) {
    activePopover.dispose();
    activePopover = null;
  }

  currentPage = page;
  renderPendingTable();
}

// Popover click
let activePopover = null;

document.addEventListener("click", function (e) {
  // Klik badge
  if (e.target.classList.contains("pending-badge")) {
    // Tutup popover lama kalau ada
    if (activePopover) {
      activePopover.dispose();
      activePopover = null;
    }

    const dates = JSON.parse(e.target.getAttribute("data-missing"));

    let content = '<ul class="mb-0 ps-3">';
    dates.forEach((d) => (content += `<li>${d}</li>`));
    content += "</ul>";

    const popover = new bootstrap.Popover(e.target, {
      content: content,
      html: true,
      placement: "left",
      trigger: "manual",
    });

    popover.show();
    activePopover = popover;
  } else {
    // Klik di luar → tutup popover
    if (activePopover) {
      activePopover.dispose();
      activePopover = null;
    }
  }
});

function applySearchAndRender() {
  const keyword =
    document.getElementById("pendingSearch")?.value.toLowerCase().trim() || "";

  const sortType = document.getElementById("pendingSort")?.value;

  // ================= SEARCH =================
  filteredData = pendingData.filter((row) => {
    const inventory = (row.item_name || "").toLowerCase();
    const area = (row.specific_area || "").toLowerCase();
    const pic = (row.pic || "").toLowerCase();

    return (
      inventory.includes(keyword) ||
      area.includes(keyword) ||
      pic.includes(keyword)
    );
  });

  // ================= SORT =================
  if (sortType === "name") {
    filteredData.sort((a, b) =>
      (a.item_name || "").localeCompare(b.item_name || ""),
    );
  }

  if (sortType === "area") {
    filteredData.sort((a, b) =>
      (a.specific_area || "").localeCompare(b.specific_area || ""),
    );
  }

  if (sortType === "frequency") {
    filteredData.sort((a, b) =>
      (a.frequency || "").localeCompare(b.frequency || ""),
    );
  }

  if (sortType === "status") {
    filteredData.sort(
      (a, b) => (b.missing?.length || 0) - (a.missing?.length || 0),
    );
  }

  document.getElementById("pendingCount").innerText = filteredData.length;

  currentPage = 1;
  renderPendingTable();
  renderPendingSummary();
}

function getRiskBadgeClass(row) {
  const freq = (row.frequency || "").toLowerCase();
  const missingCount = row.missing?.length || 0;

  if (freq === "daily") {
    if (missingCount >= 5) return "bg-danger";
    if (missingCount >= 2) return "bg-warning text-dark";
    if (missingCount >= 1) return "bg-success";
  }

  if (freq === "weekly") {
    if (missingCount >= 2) return "bg-danger";
    if (missingCount === 1) return "bg-warning text-dark";
  }

  if (freq === "monthly") {
    if (missingCount >= 1) return "bg-danger";
  }

  return "bg-secondary";
}

function renderPendingSummary() {
  let daily = 0;
  let weekly = 0;
  let monthly = 0;

  filteredData.forEach((row) => {
    const freq = row.frequency.toLowerCase();

    if (freq === "daily") daily++;
    if (freq === "weekly") weekly++;
    if (freq === "monthly") monthly++;
  });

  document.getElementById("summaryDaily").innerText = daily;
  document.getElementById("summaryWeekly").innerText = weekly;
  document.getElementById("summaryMonthly").innerText = monthly;
  document.getElementById("summaryTotal").innerText = filteredData.length;
}
