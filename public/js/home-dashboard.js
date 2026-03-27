"use strict";

function getHomeCfg() {
  const cfg = window.HOME_DASHBOARD || {};
  return {
    selectedMonth: cfg.selectedMonth || "",
    checklistBaseUrl: cfg.checklistBaseUrl || "/compliance/checklist",
  };
}

function normalizeToPath(rawUrl) {
  const url = new URL(rawUrl, window.location.origin);
  return `${url.pathname}${url.search}`;
}

function buildPeriodKey(frequency, selectedMonth, value) {
  const token = String(value || "").trim();

  if (!selectedMonth) {
    return token;
  }

  if (frequency === "daily") {
    return `${selectedMonth}-${token}`;
  }

  if (frequency === "weekly") {
    return `${selectedMonth}-W${Number(token)}`;
  }

  return selectedMonth;
}

function buildChecklistUrl(baseChecklistUrl, id, periodKey) {
  const basePath = normalizeToPath(`${baseChecklistUrl}/${id}`);
  return `${basePath}?period_key=${encodeURIComponent(periodKey)}`;
}

document.addEventListener("DOMContentLoaded", function () {
  const cfg = getHomeCfg();

  document.addEventListener("click", function (event) {
    const button = event.target.closest(".open-popover");
    if (!button) {
      return;
    }

    event.preventDefault();

    const inventoryId = button.dataset.id || "";
    const frequency = String(button.dataset.frequency || "").toLowerCase();

    let missing = [];
    try {
      missing = JSON.parse(button.dataset.missing || "[]");
    } catch (_) {
      missing = [];
    }

    let contentHtml = "";

    if (!Array.isArray(missing) || missing.length === 0) {
      contentHtml = '<span class="text-success">Semua periode sudah selesai.</span>';
    } else {
      const links = missing
        .map((periodToken) => {
          const periodKey = buildPeriodKey(frequency, cfg.selectedMonth, periodToken);
          const checklistUrl = buildChecklistUrl(cfg.checklistBaseUrl, inventoryId, periodKey);
          return `<a href="${checklistUrl}" class="badge bg-warning text-dark text-decoration-none">${periodToken}</a>`;
        })
        .join(" ");

      contentHtml = `<div class="d-flex flex-wrap gap-1">${links}</div>`;
    }

    const existingPopover = bootstrap.Popover.getInstance(button);
    if (existingPopover) {
      existingPopover.dispose();
    }

    const popover = new bootstrap.Popover(button, {
      html: true,
      content: contentHtml,
      trigger: "focus",
      placement: window.innerWidth < 768 ? "bottom" : "left",
      sanitize: false,
    });

    popover.show();
  });
});
