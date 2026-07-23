"use strict";

function relUrl(raw) {
  const url = new URL(raw, window.location.origin);
  return url.pathname + url.search;
}

document.addEventListener("DOMContentLoaded", function () {
  const cfg = window.REPORT_CONFIG || {};
  const base = cfg.baseUrl || window.location.origin;

  const endpoints = {
    itemByCategory: relUrl(cfg.itemByCategoryUrl || `${base}/compliance/report/item-by-category`),
    inventoryByType: relUrl(cfg.inventoryByTypeUrl || `${base}/compliance/report/inventory-by-type`),
    load: relUrl(cfg.loadUrl || `${base}/compliance/report/load`),
    exportBase: relUrl(cfg.exportBaseUrl || `${base}/export/pdf/recap`),
  };

  const els = {
    category: document.getElementById("categorySelect"),
    itemType: document.getElementById("itemTypeSelect"),
    inventory: document.getElementById("inventorySelect"),
    year: document.getElementById("yearSelect"),
    month: document.getElementById("monthSelect"),
    loadBtn: document.getElementById("loadReport"),
    loading: document.getElementById("reportLoading"),
    container: document.getElementById("reportContainer"),
    exportBtn: document.getElementById("exportFloating"),
    previewModal: document.getElementById("imagePreviewModal"),
    previewImage: document.getElementById("previewImage"),
  };

  if (!els.category || !els.itemType || !els.inventory || !els.container) {
    return;
  }

  let reportAbortController = null;

  function setLoading(isLoading) {
    if (els.loading) {
      els.loading.classList.toggle("d-none", !isLoading);
    }

    els.container.classList.toggle("is-loading", isLoading);
  }

  function resetItemSelect() {
    els.itemType.replaceChildren();
    const opt = document.createElement("option");
    opt.value = "";
    opt.textContent = "Pilih Item";
    els.itemType.appendChild(opt);
    els.itemType.value = "";
    els.itemType.disabled = true;
  }

  function resetInventorySelect() {
    els.inventory.replaceChildren();
    const opt = document.createElement("option");
    opt.value = "";
    opt.textContent = "Pilih No Inventaris";
    els.inventory.appendChild(opt);
    els.inventory.value = "";
    els.inventory.disabled = true;
  }

  function showExportButton(inventoryId, year, month) {
    if (!els.exportBtn) {
      return;
    }

    els.exportBtn.href = `${endpoints.exportBase}/${inventoryId}/${year}/${month}`;
    els.exportBtn.classList.remove("d-none");
  }

  function hideExportButton() {
    if (!els.exportBtn) {
      return;
    }
    els.exportBtn.classList.add("d-none");
    els.exportBtn.removeAttribute("href");
  }

  function fetchJson(url) {
    return fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } }).then((response) => {
      if (!response.ok) {
        throw new Error("Request gagal");
      }
      return response.json();
    });
  }

  function loadItemTypesByCategory(categoryId) {
    resetItemSelect();
    resetInventorySelect();
    hideExportButton();

    if (!categoryId) {
      return;
    }

    fetchJson(`${endpoints.itemByCategory}?category_id=${encodeURIComponent(categoryId)}`)
      .then((rows) => {
        els.itemType.replaceChildren();
        const firstOpt = document.createElement("option");
        firstOpt.value = "";
        firstOpt.textContent = "Pilih Item";
        els.itemType.appendChild(firstOpt);

        rows.forEach((row) => {
          const opt = document.createElement("option");
          opt.value = row.id;
          opt.textContent = row.name;
          els.itemType.appendChild(opt);
        });

        els.itemType.disabled = false;
      })
      .catch(() => {
        window.safeToast?.("Gagal memuat daftar item.", "error");
      });
  }

  function loadInventoriesByType(itemTypeId) {
    resetInventorySelect();
    hideExportButton();

    if (!itemTypeId) {
      return;
    }

    fetchJson(`${endpoints.inventoryByType}?item_type_id=${encodeURIComponent(itemTypeId)}`)
      .then((rows) => {
        els.inventory.replaceChildren();
        const firstOpt = document.createElement("option");
        firstOpt.value = "";
        firstOpt.textContent = "Pilih No Inventaris";
        els.inventory.appendChild(firstOpt);

        rows.forEach((row) => {
          const opt = document.createElement("option");
          opt.value = row.id;
          opt.textContent = row.asset_code;
          els.inventory.appendChild(opt);
        });

        els.inventory.disabled = false;
      })
      .catch(() => {
        window.safeToast?.("Gagal memuat nomor inventory.", "error");
      });
  }

  function loadReport(inventoryId, year, month) {
    if (!inventoryId) {
      window.safeToast?.("Pilih nomor inventory terlebih dahulu.", "warning");
      return;
    }

    if (reportAbortController) {
      reportAbortController.abort();
    }

    reportAbortController = new AbortController();

    const url = `${endpoints.load}?inventory_id=${encodeURIComponent(inventoryId)}&year=${encodeURIComponent(year)}&month=${encodeURIComponent(month)}`;
    setLoading(true);

    fetch(url, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
      signal: reportAbortController.signal,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Gagal memuat laporan");
        }
        return response.text();
      })
      .then((html) => {
        els.container.innerHTML = html;
        showExportButton(inventoryId, year, month);
      })
      .catch((error) => {
        if (error.name === "AbortError") {
          return;
        }
        window.safeToast?.("Gagal memuat laporan. Silakan coba lagi.", "error");
      })
      .finally(() => {
        if (reportAbortController?.signal?.aborted !== true) {
          reportAbortController = null;
        }
        setLoading(false);
      });
  }

  els.category.addEventListener("change", function () {
    loadItemTypesByCategory(this.value);
  });

  els.itemType.addEventListener("change", function () {
    loadInventoriesByType(this.value);
  });

  els.loadBtn?.addEventListener("click", function () {
    loadReport(els.inventory.value, els.year.value, els.month.value);
  });

  document.addEventListener("click", function (event) {
    const navBtn = event.target.closest(".navInventory");
    if (navBtn) {
      const inventoryId = navBtn.dataset.id || "";
      if (!inventoryId) {
        return;
      }

      loadReport(inventoryId, els.year.value, els.month.value);
      els.inventory.value = inventoryId;
      return;
    }

    const image = event.target.closest(".img-preview");
    if (!image || !els.previewImage || !els.previewModal) {
      return;
    }

    els.previewImage.src = image.getAttribute("data-src") || "";
    bootstrap.Modal.getOrCreateInstance(els.previewModal).show();
  });
});
