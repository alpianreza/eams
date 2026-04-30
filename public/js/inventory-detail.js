"use strict";

document.addEventListener("DOMContentLoaded", function () {
  document.addEventListener("click", function (event) {
    const button = event.target.closest(".btn-month-nav");
    if (!button) {
      return;
    }

    event.preventDefault();

    const ym = button.dataset.ym;
    if (!ym) {
      return;
    }

    const container = document.getElementById("detailMonthContainer");
    if (!container) {
      return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set("ym", ym);
    url.searchParams.delete("page_checklist_history");

    container.classList.add("is-loading");

    fetch(url.pathname + url.search, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Gagal memuat data bulan");
        }

        return response.text();
      })
      .then((html) => {
        container.innerHTML = html;
        history.pushState(null, "", url.pathname + url.search);
        updateExportPdfLink(ym);
      })
      .catch(() => {
        window.safeToast?.("Gagal memuat data bulan.", "error");
      })
      .finally(() => {
        container.classList.remove("is-loading");
      });
  });

  function updateExportPdfLink(ym) {
    const button = document.getElementById("btnExportPdf");
    if (!button) {
      return;
    }

    const baseUrl = button.dataset.baseUrl;
    const frequency = (button.dataset.frequency || "").toLowerCase();

    let periodKey = ym;
    if (frequency === "daily") {
      periodKey = `${ym}-01`;
    }
    if (frequency === "weekly") {
      periodKey = `${ym}-W1`;
    }

    button.href = `${baseUrl}/${periodKey}`;
  }
});
