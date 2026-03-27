"use strict";

let complianceChart = null;
let progressTrendChart = null;
let statusPieChart = null;

const requestToken = {
  trend: 0,
  progress: 0,
  pie: 0,
  risk: 0,
  pending: 0,
};

const pendingState = {
  data: [],
  filtered: [],
  currentPage: 1,
  perPage: 10,
  activePopover: null,
  abortController: null,
  searchDebounce: null,
};

let currentType = "monthly";

document.addEventListener("DOMContentLoaded", () => {
  bindDashboardEvents();
  syncProgressTypeWithTab(currentType);
  loadDashboard();
});

function bindDashboardEvents() {
  document.querySelectorAll(".tab-frequency").forEach((button) => {
    button.addEventListener("click", () => {
      setActiveTrendTab(button.dataset.type);
      loadTrend(currentType);
      loadProgressTrend();
      loadStatusPie();
    });
  });

  document.getElementById("monthFilter")?.addEventListener("change", () => {
    loadTrend(currentType);
  });

  ["progressType", "progressYear", "progressMonth"].forEach((id) => {
    document.getElementById(id)?.addEventListener("change", () => {
      loadProgressTrend();
      loadStatusPie();
      loadRiskInsight();
    });
  });

  document.getElementById("pendingSearch")?.addEventListener("input", () => {
    clearTimeout(pendingState.searchDebounce);
    pendingState.searchDebounce = setTimeout(() => {
      applySearchAndRender(true);
    }, 180);
  });

  document
    .getElementById("pendingSort")
    ?.addEventListener("change", () => applySearchAndRender(true));

  document
    .getElementById("pendingMonth")
    ?.addEventListener("change", loadPendingChecklist);

  document
    .getElementById("pendingFrequency")
    ?.addEventListener("change", loadPendingChecklist);

  document
    .getElementById("pendingPagination")
    ?.addEventListener("click", handlePendingPaginationClick);

  document
    .getElementById("pendingTableBody")
    ?.addEventListener("click", handlePendingBadgeClick);

  document.addEventListener("click", (event) => {
    if (!pendingState.activePopover) {
      return;
    }

    if (
      event.target.closest(".pending-badge") ||
      event.target.closest(".popover")
    ) {
      return;
    }

    closeActivePopover();
  });
}

function loadDashboard() {
  loadTrend(currentType);
  loadProgressTrend();
  loadStatusPie();
  loadRiskInsight();
  loadPendingChecklist();
}

function setActiveTrendTab(type) {
  currentType = type;

  document.querySelectorAll(".tab-frequency").forEach((button) => {
    button.classList.toggle("active", button.dataset.type === type);
  });

  syncProgressTypeWithTab(type);
}

function syncProgressTypeWithTab(type) {
  const progressType = document.getElementById("progressType");
  if (progressType && progressType.value !== type) {
    progressType.value = type;
  }
}

async function loadTrend(type) {
  const token = ++requestToken.trend;
  setPanelLoading("trendPanel", true);

  const month = document.getElementById("monthFilter")?.value || "";

  try {
    const data = await fetchJson(
      buildUrl("/compliance/dashboard/trend", {
        type,
        year: selectedYear,
        month,
      }),
    );

    if (token !== requestToken.trend) {
      return;
    }

    if (data.error) {
      throw new Error(data.error);
    }

    const grouped = {};

    (Array.isArray(data) ? data : []).forEach((row) => {
      const key = row.period_key;
      const status = (row.status || "").toLowerCase();

      if (!key) {
        return;
      }

      if (!grouped[key]) {
        grouped[key] = { ok: 0, not_ok: 0, na: 0 };
      }

      if (["ok", "not_ok", "na"].includes(status)) {
        grouped[key][status] = toNumber(row.total);
      }
    });

    const labels = [];
    const okData = [];
    const notOkData = [];
    const naData = [];

    Object.keys(grouped)
      .sort()
      .forEach((key) => {
        labels.push(formatLabel(key, type));
        okData.push(grouped[key].ok || 0);
        notOkData.push(grouped[key].not_ok || 0);
        naData.push(grouped[key].na || 0);
      });

    renderTrendChart(labels, okData, notOkData, naData);
    toggleEmptyState(
      "trendEmptyState",
      labels.length === 0,
      "Belum ada data tren untuk filter ini.",
    );
  } catch (error) {
    if (token === requestToken.trend) {
      toggleEmptyState(
        "trendEmptyState",
        true,
        "Data tren belum tersedia.",
      );
      showError(error, "Gagal memuat data tren.");
    }
  } finally {
    if (token === requestToken.trend) {
      setPanelLoading("trendPanel", false);
    }
  }
}

function renderTrendChart(labels, okData, notOkData, naData) {
  const canvas = document.getElementById("complianceChart");
  if (!canvas) {
    return;
  }

  if (complianceChart) {
    complianceChart.destroy();
  }

  complianceChart = new Chart(canvas, {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          label: "Sesuai",
          data: okData,
          borderColor: "#16a34a",
          backgroundColor: "rgba(22, 163, 74, 0.16)",
          pointBackgroundColor: "#16a34a",
          pointRadius: 3,
          pointHoverRadius: 5,
          borderWidth: 2,
          fill: true,
          tension: 0.35,
        },
        {
          label: "Tidak Sesuai",
          data: notOkData,
          borderColor: "#dc2626",
          backgroundColor: "rgba(220, 38, 38, 0.12)",
          pointBackgroundColor: "#dc2626",
          pointRadius: 3,
          pointHoverRadius: 5,
          borderWidth: 2,
          fill: true,
          tension: 0.35,
        },
        {
          label: "Tidak Berlaku",
          data: naData,
          borderColor: "#0284c7",
          backgroundColor: "rgba(2, 132, 199, 0.12)",
          pointBackgroundColor: "#0284c7",
          pointRadius: 3,
          pointHoverRadius: 5,
          borderWidth: 2,
          fill: true,
          tension: 0.35,
        },
      ],
    },
    options: getLineChartOptions(),
  });
}

async function loadProgressTrend() {
  const token = ++requestToken.progress;
  setPanelLoading("progressPanel", true);

  const type = document.getElementById("progressType")?.value || "monthly";
  const year = document.getElementById("progressYear")?.value || selectedYear;
  const month =
    document.getElementById("progressMonth")?.value ||
    new Date().getMonth() + 1;

  try {
    const [trendData, totalInventoryRes] = await Promise.all([
      fetchJson(
        buildUrl("/compliance/dashboard/progress-trend", {
          type,
          year,
          month,
        }),
      ),
      fetchJson(
        buildUrl("/compliance/dashboard/total-inventory", {
          type,
        }),
      ),
    ]);

    if (token !== requestToken.progress) {
      return;
    }

    if (trendData.error) {
      throw new Error(trendData.error);
    }

    if (totalInventoryRes.error) {
      throw new Error(totalInventoryRes.error);
    }

    const labels = [];
    const checkedData = [];

    (Array.isArray(trendData) ? trendData : []).forEach((row) => {
      labels.push(formatLabel(row.period_key, type));
      checkedData.push(toNumber(row.total));
    });

    const totalInventory = toNumber(totalInventoryRes.total);
    const uncheckedData = checkedData.map((checked) =>
      Math.max(totalInventory - checked, 0),
    );

    renderProgressTrend(labels, checkedData, uncheckedData);
    toggleEmptyState(
      "progressEmptyState",
      labels.length === 0,
      "Belum ada data progress untuk filter ini.",
    );

    const meta = document.getElementById("progressMeta");
    if (meta) {
      meta.textContent = `Total item: ${totalInventory}`;
    }
  } catch (error) {
    if (token === requestToken.progress) {
      toggleEmptyState(
        "progressEmptyState",
        true,
        "Data progress belum tersedia.",
      );
      showError(error, "Gagal memuat tren progres.");
    }
  } finally {
    if (token === requestToken.progress) {
      setPanelLoading("progressPanel", false);
    }
  }
}

function renderProgressTrend(labels, checkedData, uncheckedData) {
  const canvas = document.getElementById("progressChart");
  if (!canvas) {
    return;
  }

  if (progressTrendChart) {
    progressTrendChart.destroy();
  }

  progressTrendChart = new Chart(canvas, {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          label: "Sudah Diceklis",
          data: checkedData,
          borderColor: "#2563eb",
          backgroundColor: "rgba(37, 99, 235, 0.16)",
          pointBackgroundColor: "#2563eb",
          borderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          fill: true,
          tension: 0.35,
        },
        {
          label: "Belum Diceklis",
          data: uncheckedData,
          borderColor: "#f97316",
          backgroundColor: "rgba(249, 115, 22, 0.16)",
          pointBackgroundColor: "#f97316",
          borderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          fill: true,
          tension: 0.35,
        },
      ],
    },
    options: getLineChartOptions(),
  });
}

async function loadStatusPie() {
  const token = ++requestToken.pie;
  setPanelLoading("piePanel", true);

  const type = document.getElementById("progressType")?.value || "monthly";
  const year = document.getElementById("progressYear")?.value || selectedYear;
  const month =
    document.getElementById("progressMonth")?.value ||
    new Date().getMonth() + 1;

  try {
    const data = await fetchJson(
      buildUrl("/compliance/dashboard/status-pie", {
        type,
        year,
        month,
      }),
    );

    if (token !== requestToken.pie) {
      return;
    }

    if (data.error) {
      throw new Error(data.error);
    }

    const ok = toNumber(data.ok);
    const notOk = toNumber(data.not_ok);

    renderStatusPie(ok, notOk);

    const total = ok + notOk;
    toggleEmptyState(
      "pieEmptyState",
      total === 0,
      "Belum ada data status untuk filter ini.",
    );

    const pieMeta = document.getElementById("statusPieMeta");
    if (pieMeta) {
      pieMeta.textContent = `Sesuai: ${ok} | Tidak Sesuai: ${notOk}`;
    }
  } catch (error) {
    if (token === requestToken.pie) {
      toggleEmptyState(
        "pieEmptyState",
        true,
        "Data distribusi status belum tersedia.",
      );
      showError(error, "Gagal memuat distribusi status.");
    }
  } finally {
    if (token === requestToken.pie) {
      setPanelLoading("piePanel", false);
    }
  }
}

function renderStatusPie(ok, notOk) {
  const canvas = document.getElementById("statusPieChart");
  if (!canvas) {
    return;
  }

  if (statusPieChart) {
    statusPieChart.destroy();
  }

  const total = ok + notOk;
  const hasData = total > 0;

  statusPieChart = new Chart(canvas, {
    type: "doughnut",
    data: {
      labels: hasData ? ["Sesuai", "Tidak Sesuai"] : ["Belum Ada Data"],
      datasets: [
        {
          data: hasData ? [ok, notOk] : [1],
          backgroundColor: hasData
            ? ["#22c55e", "#ef4444"]
            : ["rgba(148, 163, 184, 0.4)"],
          borderWidth: 0,
          hoverOffset: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "66%",
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            usePointStyle: true,
            boxWidth: 8,
            color: "#334155",
          },
        },
        tooltip: {
          callbacks: {
            label(context) {
              if (!hasData) {
                return "Belum ada data";
              }

              const value = toNumber(context.raw);
              const percent = total > 0 ? ((value / total) * 100).toFixed(1) : "0.0";
              return `${context.label}: ${value} (${percent}%)`;
            },
          },
        },
      },
    },
  });
}

async function loadRiskInsight() {
  const token = ++requestToken.risk;
  setPanelLoading("riskItemPanel", true);
  setPanelLoading("riskAreaPanel", true);

  const year = document.getElementById("progressYear")?.value || selectedYear;
  const month =
    document.getElementById("progressMonth")?.value ||
    new Date().getMonth() + 1;

  try {
    const data = await fetchJson(
      buildUrl("/compliance/dashboard/risk-insight", {
        year,
        month,
      }),
    );

    if (token !== requestToken.risk) {
      return;
    }

    if (data.error) {
      throw new Error(data.error);
    }

    renderRiskList("topItemRisk", Array.isArray(data.items) ? data.items : [], "item_name");
    renderRiskList(
      "topAreaRisk",
      Array.isArray(data.areas) ? data.areas : [],
      "specific_area",
    );
  } catch (error) {
    if (token === requestToken.risk) {
      renderRiskFallback("topItemRisk", "Data risiko item belum tersedia.");
      renderRiskFallback("topAreaRisk", "Data risiko area belum tersedia.");
      showError(error, "Gagal memuat wawasan risiko.");
    }
  } finally {
    if (token === requestToken.risk) {
      setPanelLoading("riskItemPanel", false);
      setPanelLoading("riskAreaPanel", false);
    }
  }
}

function renderRiskList(elementId, rows, labelKey) {
  const list = document.getElementById(elementId);
  if (!list) {
    return;
  }

  list.innerHTML = "";

  if (!rows.length) {
    list.innerHTML =
      '<li class="list-group-item text-muted px-0">Belum ada temuan tidak sesuai untuk periode ini.</li>';
    return;
  }

  const maxTotal = Math.max(...rows.map((row) => toNumber(row.total)), 1);

  rows.forEach((row, index) => {
    const title = escapeHtml(row[labelKey] || "-");
    const total = toNumber(row.total);
    const width = Math.max(8, Math.round((total / maxTotal) * 100));

    list.innerHTML += `
      <li class="list-group-item px-0 py-2 border-0">
        <div class="d-flex align-items-start justify-content-between gap-2">
          <div class="d-flex align-items-center gap-2 min-w-0">
            <span class="risk-rank">#${index + 1}</span>
            <span class="risk-title text-truncate">${title}</span>
          </div>
          <span class="badge text-bg-danger rounded-pill">${total}</span>
        </div>
        <div class="progress risk-mini-progress mt-2">
          <div class="progress-bar bg-danger" role="progressbar" style="width:${width}%"></div>
        </div>
      </li>
    `;
  });
}

function renderRiskFallback(elementId, message) {
  const list = document.getElementById(elementId);
  if (!list) {
    return;
  }

  list.innerHTML = `<li class="list-group-item text-muted px-0">${escapeHtml(message)}</li>`;
}

async function loadPendingChecklist() {
  const token = ++requestToken.pending;

  const month = document.getElementById("pendingMonth")?.value || "";
  const frequency = document.getElementById("pendingFrequency")?.value || "";

  if (pendingState.abortController) {
    pendingState.abortController.abort();
  }

  const controller = new AbortController();
  pendingState.abortController = controller;

  setPanelLoading("pendingPanel", true);
  renderPendingLoadingRows();

  try {
    const data = await fetchJson(
      buildUrl("/compliance/dashboard/pending-checklist", {
        month,
        frequency,
      }),
      {
        signal: controller.signal,
      },
    );

    if (controller.signal.aborted || token !== requestToken.pending) {
      return;
    }

    if (data.error) {
      throw new Error(data.error);
    }

    pendingState.data = Array.isArray(data) ? data : [];
    applySearchAndRender(true);
  } catch (error) {
    if (error.name === "AbortError") {
      return;
    }

    if (token === requestToken.pending) {
      pendingState.data = [];
      pendingState.filtered = [];
      renderPendingTable();
      renderPendingSummary();
      showError(error, "Gagal memuat ceklis tertunda.");
    }
  } finally {
    if (token === requestToken.pending) {
      setPanelLoading("pendingPanel", false);
    }
  }
}

function renderPendingLoadingRows() {
  const tbody = document.getElementById("pendingTableBody");
  if (!tbody) {
    return;
  }

  tbody.innerHTML = `
    <tr>
      <td colspan="5" class="text-muted">Memuat data ceklis tertunda...</td>
    </tr>
  `;
}

function applySearchAndRender(resetPage = false) {
  const keyword =
    document.getElementById("pendingSearch")?.value.toLowerCase().trim() || "";
  const sortType = document.getElementById("pendingSort")?.value || "name";

  pendingState.filtered = pendingState.data.filter((row) => {
    const inventory = (row.item_name || "").toLowerCase();
    const area = (row.specific_area || "").toLowerCase();
    const pic = (row.pic || "").toLowerCase();

    return (
      inventory.includes(keyword) ||
      area.includes(keyword) ||
      pic.includes(keyword)
    );
  });

  if (sortType === "name") {
    pendingState.filtered.sort((a, b) =>
      (a.item_name || "").localeCompare(b.item_name || ""),
    );
  }

  if (sortType === "area") {
    pendingState.filtered.sort((a, b) =>
      (a.specific_area || "").localeCompare(b.specific_area || ""),
    );
  }

  if (sortType === "frequency") {
    pendingState.filtered.sort((a, b) =>
      (a.frequency || "").localeCompare(b.frequency || ""),
    );
  }

  if (sortType === "status") {
    pendingState.filtered.sort(
      (a, b) =>
        ((b.missing && b.missing.length) || 0) -
        ((a.missing && a.missing.length) || 0),
    );
  }

  if (resetPage) {
    pendingState.currentPage = 1;
  }

  const countElement = document.getElementById("pendingCount");
  if (countElement) {
    countElement.textContent = String(pendingState.filtered.length);
  }

  renderPendingSummary();
  renderPendingTable();
}

function renderPendingTable() {
  const tbody = document.getElementById("pendingTableBody");
  if (!tbody) {
    return;
  }

  closeActivePopover();
  tbody.innerHTML = "";

  const dataSource = pendingState.filtered;

  if (!dataSource.length) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="text-muted text-center py-3">Tidak ada ceklis tertunda untuk filter ini.</td>
      </tr>
    `;
    renderPendingPagination(0);
    return;
  }

  const start = (pendingState.currentPage - 1) * pendingState.perPage;
  const end = start + pendingState.perPage;
  const pageData = dataSource.slice(start, end);

  pageData.forEach((row) => {
    const missing = Array.isArray(row.missing) ? row.missing : [];
    const missingJson = encodeURIComponent(JSON.stringify(missing));

    const frequencyBadgeClass = getFrequencyBadgeClass(row.frequency || "");
    const riskBadgeClass = getRiskBadgeClass(row);

    tbody.innerHTML += `
      <tr>
        <td class="fw-semibold">${escapeHtml(row.item_name || "-")}</td>
        <td>${escapeHtml(row.specific_area || "-")}</td>
        <td>
          <span class="badge text-bg-secondary">${escapeHtml(row.pic || "-")}</span>
        </td>
        <td>
          <span class="badge ${frequencyBadgeClass}">${escapeHtml(
            getFrequencyDisplayLabel(row.frequency || "-"),
          )}</span>
        </td>
        <td>
          <button
            type="button"
            class="badge border-0 pending-badge ${riskBadgeClass}"
            data-missing="${missingJson}">
            ${escapeHtml(row.status || "Lihat Detail")}
          </button>
        </td>
      </tr>
    `;
  });

  renderPendingPagination(dataSource.length);
}

function renderPendingPagination(totalData) {
  const container = document.getElementById("pendingPagination");
  if (!container) {
    return;
  }

  container.innerHTML = "";

  const totalPages = Math.ceil(totalData / pendingState.perPage);
  if (totalPages <= 1) {
    return;
  }

  const pages = getPaginationPages(totalPages, pendingState.currentPage);

  pages.forEach((page) => {
    if (page === "...") {
      const ellipsis = document.createElement("span");
      ellipsis.className = "px-2 py-1 text-muted small";
      ellipsis.textContent = "...";
      container.appendChild(ellipsis);
      return;
    }

    const button = document.createElement("button");
    button.type = "button";
    button.className =
      page === pendingState.currentPage
        ? "btn btn-sm btn-primary"
        : "btn btn-sm btn-outline-primary";
    button.dataset.page = String(page);
    button.textContent = String(page);
    container.appendChild(button);
  });
}

function getPaginationPages(totalPages, currentPage) {
  const pages = [1];

  const start = Math.max(2, currentPage - 1);
  const end = Math.min(totalPages - 1, currentPage + 1);

  if (start > 2) {
    pages.push("...");
  }

  for (let i = start; i <= end; i += 1) {
    pages.push(i);
  }

  if (end < totalPages - 1) {
    pages.push("...");
  }

  if (totalPages > 1) {
    pages.push(totalPages);
  }

  return pages;
}

function handlePendingPaginationClick(event) {
  const button = event.target.closest("button[data-page]");
  if (!button) {
    return;
  }

  const nextPage = toNumber(button.dataset.page);
  if (nextPage < 1) {
    return;
  }

  pendingState.currentPage = nextPage;
  renderPendingTable();
}

function handlePendingBadgeClick(event) {
  const badge = event.target.closest(".pending-badge");
  if (!badge) {
    return;
  }

  event.preventDefault();
  event.stopPropagation();

  showMissingPopover(badge);
}

function showMissingPopover(target) {
  closeActivePopover();

  let missingDates = [];
  try {
    missingDates = JSON.parse(decodeURIComponent(target.dataset.missing || "[]"));
  } catch (error) {
    missingDates = [];
  }

  const content = missingDates.length
    ? `<ul class="mb-0 ps-3">${missingDates
        .map((date) => `<li>${escapeHtml(String(date))}</li>`)
        .join("")}</ul>`
    : "<span class=\"text-muted\">Tidak ada detail tertunda.</span>";

  pendingState.activePopover = new bootstrap.Popover(target, {
    content,
    html: true,
    placement: "left",
    trigger: "manual",
    sanitize: false,
  });

  pendingState.activePopover.show();
}

function closeActivePopover() {
  if (pendingState.activePopover) {
    pendingState.activePopover.dispose();
    pendingState.activePopover = null;
  }
}

function renderPendingSummary() {
  let daily = 0;
  let weekly = 0;
  let monthly = 0;

  pendingState.filtered.forEach((row) => {
    const frequency = (row.frequency || "").toLowerCase();

    if (frequency === "daily") {
      daily += 1;
    }

    if (frequency === "weekly") {
      weekly += 1;
    }

    if (frequency === "monthly") {
      monthly += 1;
    }
  });

  setText("summaryDaily", String(daily));
  setText("summaryWeekly", String(weekly));
  setText("summaryMonthly", String(monthly));
  setText("summaryTotal", String(pendingState.filtered.length));
}

function getRiskBadgeClass(row) {
  const frequency = (row.frequency || "").toLowerCase();
  const missingCount = (row.missing && row.missing.length) || 0;

  if (frequency === "daily") {
    if (missingCount >= 5) {
      return "text-bg-danger";
    }

    if (missingCount >= 2) {
      return "text-bg-warning";
    }

    if (missingCount >= 1) {
      return "text-bg-success";
    }
  }

  if (frequency === "weekly") {
    if (missingCount >= 2) {
      return "text-bg-danger";
    }

    if (missingCount === 1) {
      return "text-bg-warning";
    }
  }

  if (frequency === "monthly" && missingCount >= 1) {
    return "text-bg-danger";
  }

  return "text-bg-secondary";
}

function getFrequencyBadgeClass(frequencyValue) {
  const frequency = frequencyValue.toLowerCase();

  if (frequency === "daily") {
    return "text-bg-danger";
  }

  if (frequency === "weekly") {
    return "text-bg-warning";
  }

  if (frequency === "monthly") {
    return "text-bg-dark";
  }

  return "text-bg-secondary";
}

function formatLabel(periodKey, type) {
  const key = String(periodKey || "");

  if (type === "monthly") {
    const month = key.substring(5, 7);
    const date = new Date(2000, toNumber(month) - 1, 1);
    if (!Number.isNaN(date.getTime())) {
      return date.toLocaleString("id-ID", { month: "short" });
    }
  }

  if (type === "weekly") {
    const weeklyPart = key.match(/W\d+$/);
    return weeklyPart ? weeklyPart[0] : key;
  }

  if (type === "daily") {
    return key.substring(8, 10) || key;
  }

  return key;
}

function buildUrl(path, params = {}) {
  const cleanPath = `/${String(path || "").replace(/^\/+/, "")}`;
  const rawBase = String(baseUrl || "").trim();

  let basePath = "";

  if (rawBase) {
    try {
      if (/^https?:\/\//i.test(rawBase)) {
        basePath = new URL(rawBase).pathname || "";
      } else if (rawBase.startsWith("/")) {
        basePath = rawBase;
      } else {
        basePath = `/${rawBase}`;
      }
    } catch (error) {
      basePath = "";
    }
  }

  basePath = basePath.replace(/\/+$/, "");

  const normalizedBase = basePath.replace(/^\/+/, "");
  const normalizedPath = cleanPath.replace(/^\/+/, "");
  const mergedPath = [normalizedBase, normalizedPath]
    .filter((part) => part !== "")
    .join("/");

  const finalUrl = `${window.location.origin}/${mergedPath}`;

  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined || value === "") {
      return;
    }

    query.set(key, String(value));
  });

  const queryString = query.toString();
  return queryString ? `${finalUrl}?${queryString}` : finalUrl;
}

async function fetchJson(url, options = {}) {
  const response = await fetch(url, options);

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }

  return response.json();
}

function setPanelLoading(panelId, isLoading) {
  const panel = document.getElementById(panelId);
  if (!panel) {
    return;
  }

  panel.classList.toggle("is-loading", isLoading);
}

function toggleEmptyState(id, isVisible, message) {
  const element = document.getElementById(id);
  if (!element) {
    return;
  }

  if (message) {
    element.textContent = message;
  }

  element.classList.toggle("d-none", !isVisible);
}

function getLineChartOptions() {
  return {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: "index",
      intersect: false,
    },
    plugins: {
      legend: {
        position: "bottom",
        labels: {
          usePointStyle: true,
          boxWidth: 8,
          color: "#334155",
        },
      },
      tooltip: {
        backgroundColor: "rgba(15, 23, 42, 0.92)",
      },
    },
    scales: {
      x: {
        grid: {
          display: false,
        },
        ticks: {
          color: "#64748b",
        },
      },
      y: {
        beginAtZero: true,
        ticks: {
          precision: 0,
          color: "#64748b",
        },
        grid: {
          color: "rgba(148, 163, 184, 0.22)",
        },
      },
    },
  };
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function showError(error, fallbackMessage) {
  const rawMessage = String(error?.message || "").toLowerCase();
  const isFetchFailure =
    rawMessage.includes("failed to fetch") ||
    rawMessage.includes("fetch failed") ||
    rawMessage.includes("networkerror") ||
    rawMessage.includes("load failed");

  const message = isFetchFailure
    ? "Gagal terhubung ke server. Silakan muat ulang halaman."
    : fallbackMessage;

  window.safeToast?.(message, "error");
}

function setText(id, value) {
  const element = document.getElementById(id);
  if (element) {
    element.textContent = value;
  }
}

function toNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

function getFrequencyDisplayLabel(value) {
  const frequency = String(value || "").toLowerCase();

  if (frequency === "daily") {
    return "Harian";
  }

  if (frequency === "weekly") {
    return "Mingguan";
  }

  if (frequency === "monthly") {
    return "Bulanan";
  }

  return String(value || "-");
}
