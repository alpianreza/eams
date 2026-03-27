"use strict";

function relUrl(raw) {
  const url = new URL(raw, window.location.origin);
  return url.pathname + url.search;
}

document.addEventListener("DOMContentLoaded", function () {
  initInventoryEditModal();
  initInventoryAddModal();
  initInventoryAjaxFilters();
  initInventoryDeleteAndQrActions();
});

function initInventoryEditModal() {
  const editModalEl = document.getElementById("modalEditInventory");
  const editForm = document.getElementById("formEditInventory");
  if (!editModalEl || !editForm) {
    return;
  }

  const editModal = bootstrap.Modal.getOrCreateInstance(editModalEl);
  let activeEditTarget = null;

  document.addEventListener("click", async function (event) {
    const button = event.target.closest(".btn-edit");
    if (!button) {
      return;
    }

    activeEditTarget = button.closest("tr") || button.closest(".inventory-card") || null;
    const inventoryId = button.dataset.id;

    if (!inventoryId) {
      safeToast("ID inventory tidak ditemukan.", "error");
      return;
    }

    try {
      const response = await fetch(relUrl(`/compliance/inventory/get/${inventoryId}`), {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      if (!response.ok) {
        throw new Error("Gagal mengambil data inventory");
      }

      const data = await response.json();

      editForm.action = relUrl(`/compliance/inventory/update/${inventoryId}`);
      setFormValue(editForm, "#edit_id", data.id);
      setFormValue(editForm, "#edit_category_text", data.category_name || "");
      setFormValue(editForm, "#edit_area_text", data.area_name || "");
      setFormValue(editForm, "#edit_item_name", data.item_name || "");
      setFormValue(editForm, "#edit_category_id", data.category_id || "");
      setFormValue(editForm, "#edit_area_id", data.area_id || "");
      setFormValue(editForm, "#edit_item_type_id", data.item_type_id || "");
      setFormValue(editForm, "#edit_code", data.asset_code || "");
      setFormValue(editForm, "#edit_type", data.type_description || "");
      setFormValue(editForm, "#edit_specific_area", data.specific_area || "");
      setFormValue(editForm, "#edit_pic", data.pic || "");
      setFormValue(editForm, "#edit_status", data.status || "");
      setFormValue(editForm, "#edit_remark", data.remark || "");
      setFormValue(editForm, "#edit_expired", data.expired_date || "");

      editModal.show();
    } catch (error) {
      safeToast("Gagal memuat data inventory.", "error");
    }
  });

  editForm.addEventListener("submit", function (event) {
    event.preventDefault();

    fetch(relUrl(editForm.action), {
      method: "POST",
      body: new FormData(editForm),
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
    })
      .then((response) => response.json())
      .then((result) => {
        if (!result || result.status !== "success") {
          safeToast("Gagal memperbarui inventory.", "error");
          return;
        }

        editModal.hide();
        cleanupModalBackdrop();

        if (activeEditTarget) {
          updateInventoryRowFromEditForm(activeEditTarget, editForm, result);
        } else {
          window.location.reload();
          return;
        }

        safeToast("Inventory berhasil diperbarui.", "success");
      })
      .catch(() => {
        safeToast("Terjadi kesalahan sistem.", "error");
      });
  });

  editModalEl.addEventListener("hidden.bs.modal", function () {
    editForm.reset();
  });
}

function initInventoryAddModal() {
  const addModalEl = document.getElementById("modalAddInventory");
  const addForm = document.getElementById("formAddInventory");
  if (!addModalEl || !addForm) {
    return;
  }

  const addModal = bootstrap.Modal.getOrCreateInstance(addModalEl);

  addForm.addEventListener("submit", function (event) {
    event.preventDefault();

    fetch(relUrl(addForm.getAttribute("action") || "/compliance/inventory/store"), {
      method: "POST",
      body: new FormData(addForm),
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Gagal menambahkan inventory");
        }

        return response.json().catch(() => ({}));
      })
      .then(() => {
        addModal.hide();
        cleanupModalBackdrop();
        safeToast("Inventory berhasil ditambahkan.", "success");

        setTimeout(() => {
          window.location.reload();
        }, 500);
      })
      .catch(() => {
        safeToast("Gagal menambahkan inventory.", "error");
      });
  });

  addModalEl.addEventListener("hidden.bs.modal", function () {
    addForm.reset();
    const previewImage = addModalEl.querySelector("#previewPhoto");
    if (previewImage) {
      previewImage.classList.add("d-none");
    }
  });
}

function initInventoryAjaxFilters() {
  const ajaxContainer = document.getElementById("inventoryAjax");
  if (!ajaxContainer) {
    return;
  }

  const filters = {
    category: document.getElementById("filterCategory"),
    area: document.getElementById("filterArea"),
    search: document.getElementById("searchInput"),
    perPage: document.getElementById("filterPerPage"),
    reset: document.getElementById("btnResetFilter"),
  };

  const defaultPerPage = filters.perPage?.value || "20";
  const rawBase = typeof BASE_URL !== "undefined" ? BASE_URL : window.location.origin;
  const indexBasePath = relUrl(`${rawBase}/compliance/inventory`).split("?")[0];

  let debounceTimer = null;
  let activeRequestController = null;

  function getSkeleton() {
    return ajaxContainer.querySelector("#inventorySkeleton");
  }

  function setLoadingState(isLoading) {
    ajaxContainer.classList.toggle("is-loading", isLoading);
    const skeleton = getSkeleton();
    if (skeleton) {
      skeleton.classList.toggle("d-none", !isLoading);
    }
  }

  function collectFilterQuery() {
    const params = new URLSearchParams();

    if (filters.category?.value) {
      params.set("category", filters.category.value);
    }

    if (filters.area?.value) {
      params.set("area", filters.area.value);
    }

    if (filters.search?.value.trim()) {
      params.set("q", filters.search.value.trim());
    }

    if (filters.perPage?.value) {
      params.set("perPage", filters.perPage.value);
    }

    return params;
  }

  function toggleResetButton() {
    const hasFilter =
      !!filters.category?.value ||
      !!filters.area?.value ||
      !!filters.search?.value.trim() ||
      ((filters.perPage?.value || defaultPerPage) !== defaultPerPage);

    if (hasFilter) {
      filters.reset?.classList.remove("d-none");
    } else {
      filters.reset?.classList.add("d-none");
    }
  }

  function loadInventory(url) {
    if (activeRequestController) {
      activeRequestController.abort();
    }

    const controller = new AbortController();
    activeRequestController = controller;

    const finalUrl = relUrl(url);
    setLoadingState(true);

    fetch(finalUrl, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
      signal: controller.signal,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Gagal memuat inventory");
        }

        return response.text();
      })
      .then((html) => {
        if (controller.signal.aborted) {
          return;
        }

        const parsed = new DOMParser().parseFromString(html, "text/html");
        const newContent = parsed.querySelector("#inventoryAjax");
        if (!newContent) {
          throw new Error("Konten inventory tidak ditemukan");
        }

        ajaxContainer.innerHTML = newContent.innerHTML;

        window.history.pushState({}, "", finalUrl);
        bindPaginationLinks();
        bindTooltips();
      })
      .catch((error) => {
        if (error.name === "AbortError") {
          return;
        }

        safeToast("Gagal memuat inventory.", "error");
      })
      .finally(() => {
        if (activeRequestController === controller) {
          activeRequestController = null;
        }

        setLoadingState(false);
      });
  }

  function applyFilterWithDebounce() {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
      const params = collectFilterQuery();
      const queryString = params.toString();
      const targetUrl = queryString
        ? `${indexBasePath}?${queryString}`
        : indexBasePath;

      loadInventory(targetUrl);
      toggleResetButton();
    }, 250);
  }

  function bindPaginationLinks() {
    ajaxContainer.querySelectorAll(".pagination a").forEach((link) => {
      link.addEventListener("click", function (event) {
        event.preventDefault();
        loadInventory(link.href);
      });
    });
  }

  function bindTooltips() {
    document.querySelectorAll("[title]").forEach((element) => {
      bootstrap.Tooltip.getOrCreateInstance(element);
    });
  }

  filters.category?.addEventListener("change", applyFilterWithDebounce);
  filters.area?.addEventListener("change", applyFilterWithDebounce);
  filters.search?.addEventListener("input", applyFilterWithDebounce);
  filters.perPage?.addEventListener("change", applyFilterWithDebounce);

  filters.reset?.addEventListener("click", function () {
    if (filters.category) filters.category.value = "";
    if (filters.area) filters.area.value = "";
    if (filters.search) filters.search.value = "";
    if (filters.perPage) filters.perPage.value = defaultPerPage;

    loadInventory(indexBasePath);
    toggleResetButton();
  });

  toggleResetButton();
  bindPaginationLinks();
  bindTooltips();
}

function initInventoryDeleteAndQrActions() {
  let currentQrUrl = null;
  let currentQrFilename = null;

  document.addEventListener("click", function (event) {
    const deleteButton = event.target.closest(".btn-delete");
    if (deleteButton) {
      const form = deleteButton.closest("form");
      if (!form) {
        return;
      }
      const inventoryId = deleteButton.closest("[data-inventory-id]")?.dataset.inventoryId || "";

      Swal.fire({
        title: "Hapus inventory?",
        text: "Data inventory yang dihapus tidak dapat dikembalikan.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Ya, hapus",
        cancelButtonText: "Batal",
      }).then((result) => {
        if (!result.isConfirmed) {
          return;
        }

        fetch(relUrl(form.action), {
          method: "POST",
          body: new FormData(form),
          headers: { "X-Requested-With": "XMLHttpRequest" },
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error("Gagal menghapus inventory");
            }

            const fallbackTarget = deleteButton.closest("tr") || deleteButton.closest(".inventory-card");
            removeInventoryViewsById(inventoryId, fallbackTarget);

            safeToast("Inventory berhasil dihapus.", "success");
          })
          .catch(() => {
            safeToast("Gagal menghapus inventory.", "error");
          });
      });

      return;
    }

    const qrButton = event.target.closest(".btn-show-qr");
    if (qrButton) {
      const modalElement = document.getElementById("modalQr");
      if (!modalElement) {
        return;
      }

      const inventoryId = qrButton.dataset.id;
      const qrUrl = qrButton.dataset.qr;
      const itemName = (qrButton.dataset.item || "inventory").replace(/\s+/g, "_");
      const assetCode = (qrButton.dataset.no || "item").replace(/\s+/g, "_");

      modalElement.dataset.inventoryId = inventoryId || "";
      currentQrUrl = qrUrl ? relUrl(qrUrl) : "";
      currentQrFilename = `${itemName}_${assetCode}_QR.png`;

      const image = modalElement.querySelector("#qrImage");
      if (image && currentQrUrl) {
        image.src = `${currentQrUrl}?v=${Date.now()}`;
      }

      bootstrap.Modal.getOrCreateInstance(modalElement).show();
      return;
    }

    if (event.target.closest("#btnDownloadQr")) {
      if (!currentQrUrl) {
        safeToast("QR belum tersedia.", "warning");
        return;
      }

      fetch(currentQrUrl)
        .then((response) => {
          if (!response.ok) {
            throw new Error("Gagal mengunduh QR");
          }

          return response.blob();
        })
        .then((blob) => {
          const downloadUrl = URL.createObjectURL(blob);
          const anchor = document.createElement("a");
          anchor.href = downloadUrl;
          anchor.download = currentQrFilename || "qr-code.png";
          document.body.appendChild(anchor);
          anchor.click();
          document.body.removeChild(anchor);
          URL.revokeObjectURL(downloadUrl);
        })
        .catch(() => {
          safeToast("Gagal mengunduh QR.", "error");
        });

      return;
    }

    const regenerateButton = event.target.closest("#btnRegenQrModal");
    if (regenerateButton) {
      const modalElement = document.getElementById("modalQr");
      const inventoryId = modalElement?.dataset.inventoryId;

      if (!inventoryId) {
        safeToast("ID inventory tidak ditemukan.", "error");
        return;
      }

      fetch(relUrl(`/compliance/inventory/regenerate-qr/${inventoryId}`), {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status !== "success") {
            throw new Error("Gagal memperbarui QR");
          }

          const qrPath = resolveQrPathFromFile(result.qr_image);

          const image = modalElement?.querySelector("#qrImage");
          if (image && qrPath) {
            image.src = `${qrPath}?t=${Date.now()}`;
          }

          currentQrUrl = qrPath;

          document.querySelectorAll(`.btn-show-qr[data-id="${inventoryId}"]`).forEach((button) => {
            button.dataset.qr = qrPath;
          });

          safeToast("QR berhasil diperbarui.", "success");
        })
        .catch(() => {
          safeToast("Gagal memperbarui QR.", "error");
        });
    }
  });
}

function setFormValue(form, selector, value) {
  const element = form.querySelector(selector);
  if (element) {
    element.value = value;
  }
}

function cleanupModalBackdrop() {
  document.body.classList.remove("modal-open");
  document.body.style.removeProperty("padding-right");
  document.querySelectorAll(".modal-backdrop").forEach((backdrop) => backdrop.remove());
}

function removeInventoryViewsById(inventoryId, fallbackElement) {
  if (inventoryId) {
    document.querySelectorAll(`[data-inventory-id="${inventoryId}"]`).forEach((element) => {
      element.remove();
    });
    return;
  }

  fallbackElement?.remove();
}

function resolveQrPathFromFile(qrFile) {
  if (!qrFile) {
    return "";
  }

  const rawBase = typeof BASE_URL !== "undefined" ? BASE_URL : window.location.origin;
  return relUrl(`${rawBase}/uploads/qr/${qrFile}`);
}

function updateInventoryRowFromEditForm(target, form, response) {
  const code = response.asset_code ?? form.querySelector("#edit_code")?.value ?? "";
  const type = form.querySelector("#edit_type")?.value ?? "";
  const pic = form.querySelector("#edit_pic")?.value ?? "";
  const remark = form.querySelector("#edit_remark")?.value ?? "";
  const specific = form.querySelector("#edit_specific_area")?.value ?? "";
  const status = form.querySelector("#edit_status")?.value ?? "";
  const statusMeta = getStatusMeta(status);
  const qrPath = resolveQrPathFromFile(response.qr_image);
  const inventoryId =
    form.querySelector("#edit_id")?.value ||
    target?.dataset?.inventoryId ||
    target?.querySelector?.(".btn-edit")?.dataset?.id ||
    "";

  const syncTargets = inventoryId
    ? Array.from(document.querySelectorAll(`[data-inventory-id="${inventoryId}"]`))
    : [];

  if (syncTargets.length === 0 && target) {
    syncTargets.push(target);
  }

  syncTargets.forEach((element) => {
    const editButton = element.querySelector(".btn-edit");
    if (editButton) {
      editButton.dataset.code = code;
      editButton.dataset.type = type;
      editButton.dataset.pic = pic;
      editButton.dataset.status = status;
      editButton.dataset.remark = remark;
      editButton.dataset.specific = specific;
    }

    if (qrPath) {
      if (editButton) {
        editButton.dataset.qr = qrPath;
      }

      const qrButton = element.querySelector(".btn-show-qr");
      if (qrButton) {
        qrButton.dataset.qr = qrPath;
      }
    }

    if (element.matches("tr")) {
      const cells = element.querySelectorAll("td");
      if (cells.length >= 10) {
        cells[4].textContent = code || "-";
        cells[5].textContent = type || "-";
        cells[6].textContent = specific || "-";
        cells[7].textContent = pic || "-";
        cells[8].innerHTML = `<span class="badge ${statusMeta.badgeClass}">${statusMeta.label}</span>`;
        cells[9].textContent = remark || "-";
      }

      element.classList.remove("table-warning", "table-secondary");
      if (status === "Need Repair") {
        element.classList.add("table-warning");
      }
      if (status === "Not Active") {
        element.classList.add("table-secondary");
      }
    }

    if (element.matches(".inventory-card")) {
      const metaRows = element.querySelectorAll(".inventory-mobile-meta div");
      if (metaRows[0]) {
        metaRows[0].innerHTML = `<strong>Tipe:</strong> ${escapeHtml(type || "-")}`;
      }
      if (metaRows[1]) {
        metaRows[1].innerHTML = `<strong>Area:</strong> ${escapeHtml(specific || "-")}`;
      }
      if (metaRows[2]) {
        metaRows[2].innerHTML = `<strong>PIC:</strong> ${escapeHtml(pic || "-")}`;
      }

      const badge = element.querySelector(".badge");
      if (badge) {
        badge.className = `badge ${statusMeta.badgeClass}`;
        badge.textContent = statusMeta.label;
      }
    }
  });
}

function getStatusMeta(rawStatus) {
  if (rawStatus === "Good") {
    return { badgeClass: "bg-success", label: "Baik" };
  }

  if (rawStatus === "Need Repair") {
    return { badgeClass: "bg-warning text-dark", label: "Perlu Perbaikan" };
  }

  if (rawStatus === "Not Active") {
    return { badgeClass: "bg-secondary", label: "Tidak Aktif" };
  }

  return { badgeClass: "bg-light text-dark", label: "-" };
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#39;");
}
