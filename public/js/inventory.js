// ================= URL HELPER (ANTI MIXED CONTENT) =================
function relUrl(raw) {
  const u = new URL(raw, window.location.origin);
  return u.pathname + u.search;
}

document.addEventListener("DOMContentLoaded", function () {
  /* =====================================================
     =============== EDIT INVENTORY ======================
  ===================================================== */
  const editModalEl = document.getElementById("modalEditInventory");
  const editForm = document.getElementById("formEditInventory");
  let currentRow = null;
  const qrModalEl = document.getElementById("modalQr");

  if (editModalEl && editForm) {
    const editModal = bootstrap.Modal.getOrCreateInstance(editModalEl);
    const qrModal = qrModalEl
      ? bootstrap.Modal.getOrCreateInstance(qrModalEl)
      : null;

    /* ---------- OPEN EDIT MODAL ---------- */
    document.addEventListener("click", async function (e) {
      const btn = e.target.closest(".btn-edit");
      if (!btn) return;

      const id = btn.dataset.id;
      currentRow = btn.closest("tr");

      const res = await fetch(relUrl(`/compliance/inventory/get/${id}`));
      const data = await res.json();

      editForm.action = relUrl(`/compliance/inventory/update/${id}`);

      // isi modal dari server
      editForm.querySelector("#edit_id").value = data.id;

      editForm.querySelector("#edit_category_text").value =
        data.category_name || "";
      editForm.querySelector("#edit_area_text").value = data.area_name || "";
      editForm.querySelector("#edit_item_name").value = data.item_name || "";

      editForm.querySelector("#edit_category_id").value = data.category_id;
      editForm.querySelector("#edit_area_id").value = data.area_id;
      editForm.querySelector("#edit_item_type_id").value = data.item_type_id;

      editForm.querySelector("#edit_code").value = data.asset_code || "";
      editForm.querySelector("#edit_type").value = data.type_description || "";
      editForm.querySelector("#edit_specific_area").value =
        data.specific_area || "";
      editForm.querySelector("#edit_pic").value = data.pic || "";
      editForm.querySelector("#edit_status").value = data.status || "";
      editForm.querySelector("#edit_remark").value = data.remark || "";
      editForm.querySelector("#edit_expired").value = data.expired_date || "";

      editModal.show();
    });

    /* ---------- SUBMIT EDIT (AJAX) ---------- */
    editForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();

      fetch(relUrl(editForm.action), {
        method: "POST",
        body: new FormData(editForm),
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
        },
      })
        .then((res) => res.json())
        .then((res) => {
          if (!res || res.status !== "success") {
            safeToast("Gagal memperbarui inventory", "error");
            return;
          }

          // ==============================
          // VALUE DARI FORM
          // ==============================
          const codeVal =
            res.asset_code ?? editForm.querySelector("#edit_code").value;
          const typeVal = editForm.querySelector("#edit_type").value;
          const picVal = editForm.querySelector("#edit_pic").value;
          const remarkVal = editForm.querySelector("#edit_remark").value;
          const status = editForm.querySelector("#edit_status").value;
          const specificVal = editForm.querySelector(
            "#edit_specific_area",
          ).value;

          // ==============================
          // UPDATE DATASET TOMBOL EDIT
          // ==============================
          const editBtn = currentRow.querySelector(".btn-edit");

          if (editBtn) {
            editBtn.dataset.code = codeVal || "";
            editBtn.dataset.type = typeVal || "";
            editBtn.dataset.pic = picVal || "";
            editBtn.dataset.status = status || "";
            editBtn.dataset.remark = remarkVal || "";
            editBtn.dataset.specific = specificVal || "";
            if (res.qr_image) {
              editBtn.dataset.qr = res.qr_image;
            }
          }

          // ==============================
          // CLOSE MODAL
          // ==============================
          editModal.hide();
          setTimeout(() => cleanupModal(), 200);

          safeToast("Inventory berhasil diperbarui", "success");
        })
        .catch((err) => {
          safeToast("Terjadi kesalahan sistem", "error");
        });
    });

    editModalEl.addEventListener("hidden.bs.modal", function () {
      editForm.reset();
    });
  }

  /* =========================
     ADD INVENTORY (AJAX)
  ========================= */
  const addModalEl = document.getElementById("modalAddInventory");
  const addForm = document.getElementById("formAddInventory");

  if (addModalEl && addForm) {
    const addModal = bootstrap.Modal.getOrCreateInstance(addModalEl);

    addForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // ✅ FIX MIXED CONTENT — pakai relative url
      const raw = addForm.getAttribute("action");
      const u = new URL(raw, window.location.origin);
      const url = u.pathname + u.search;

      fetch(url, {
        method: "POST",
        body: new FormData(addForm),
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((res) => {
          if (!res.ok) throw new Error("Request failed");

          addModal.hide();
          cleanupModal();
          safeToast("Inventory & QR Code berhasil ditambahkan", "success");

          setTimeout(() => {
            window.location.reload();
          }, 600);
        })
        .catch((err) => {
          safeToast("Gagal menambahkan inventory", "error");
        });
    });
    addModalEl.addEventListener("hidden.bs.modal", function () {
      addForm.reset();
      const img = addModalEl.querySelector("#previewPhoto");
      if (img) img.classList.add("d-none");
    });
  }

  /* =====================================================
     MODAL CLEANUP (ANTI FREEZE)
  ===================================================== */
  function cleanupModal() {
    document.body.classList.remove("modal-open");
    document.querySelectorAll(".modal-backdrop").forEach((b) => b.remove());
    document.body.style.removeProperty("padding-right");
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const ajaxContainer = document.getElementById("inventoryAjax");
  if (!ajaxContainer) return;

  const skeleton = document.getElementById("inventorySkeleton");

  let debounceTimer;

  /* =========================
     GET FILTER ELEMENT (ONCE)
  ========================= */
  const filters = {
    category: document.getElementById("filterCategory"),
    area: document.getElementById("filterArea"),
    search: document.getElementById("searchInput"),
    reset: document.getElementById("btnResetFilter"),
  };

  /* =========================
     LOAD INVENTORY (AJAX)
  ========================= */
  function loadInventory(url) {
    skeleton?.classList.remove("d-none");
    ajaxContainer.classList.add("is-loading");

    const finalUrl = relUrl(url);

    fetch(finalUrl, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((res) => {
        if (!res.ok) throw new Error("Load inventory failed");
        return res.text();
      })
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, "text/html");
        const newContent = doc.querySelector("#inventoryAjax");
        if (!newContent) return;

        ajaxContainer.innerHTML = newContent.innerHTML;

        // update browser url
        window.history.pushState({}, "", finalUrl);

        // rebind pagination (WAJIB)
        bindPagination();

        // UX smooth
        ajaxContainer.scrollIntoView({ behavior: "smooth", block: "start" });
      })
      .catch((err) => {
        safeToast("Gagal memuat inventory", "error");
      })
      .finally(() => {
        skeleton?.classList.add("d-none");
        ajaxContainer.classList.remove("is-loading");
      });
  }
  /* =========================
     APPLY FILTER (DEBOUNCE)
  ========================= */
  function applyFilter() {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
      const params = new URLSearchParams();

      if (filters.category?.value)
        params.set("category", filters.category.value);
      if (filters.area?.value) params.set("area", filters.area.value);
      if (filters.search?.value.trim())
        params.set("q", filters.search.value.trim());

      const url =
        window.location.origin +
        "/compliance/inventory" +
        (params.toString() ? "?" + params.toString() : "");

      loadInventory(url);
      toggleReset();
    }, 250);
  }

  /* =========================
     RESET BUTTON
  ========================= */
  function toggleReset() {
    if (
      filters.category?.value ||
      filters.area?.value ||
      filters.search?.value.trim()
    ) {
      filters.reset?.classList.remove("d-none");
    } else {
      filters.reset?.classList.add("d-none");
    }
  }

  /* =========================
     BIND FILTERS (ONCE ONLY)
  ========================= */
  filters.category?.addEventListener("change", applyFilter);
  filters.area?.addEventListener("change", applyFilter);
  filters.search?.addEventListener("input", applyFilter);

  filters.reset?.addEventListener("click", function () {
    if (filters.category) filters.category.value = "";
    if (filters.area) filters.area.value = "";
    if (filters.search) filters.search.value = "";

    loadInventory("/compliance/inventory");
    toggleReset();
  });

  /* =========================
     PAGINATION AJAX
  ========================= */
  function bindPagination() {
    document.querySelectorAll(".pagination a").forEach((link) => {
      const url = new URL(link.href);

      // paksa origin ikut browser sekarang (https / lan)
      url.protocol = window.location.protocol;
      url.host = window.location.host;

      link.href = url.toString();

      link.onclick = function (e) {
        e.preventDefault();
        loadInventory(link.href);
      };
    });
  }

  /* =========================
     INIT
  ========================= */
  toggleReset();
  bindPagination();

  /* =====================================================
     DELETE CONFIRMATION (SweetAlert2)
  ===================================================== */
  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-delete");
    if (!btn) return;

    const form = btn.closest("form");
    if (!form) return;

    Swal.fire({
      title: "Yakin hapus data?",
      text: "Data inventory yang dihapus tidak bisa dikembalikan.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Ya, hapus",
      cancelButtonText: "Batal",
    }).then((result) => {
      if (!result.isConfirmed) return;

      fetch(relUrl(form.action), {
        method: "POST",
        body: new FormData(form),
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((res) => {
          if (!res.ok) throw new Error("Delete failed");
          return res.text(); // controller kamu redirect, bukan JSON
        })
        .then(() => {
          // hapus row dari tabel
          const row = btn.closest("tr");
          if (row) row.remove();

          safeToast("Inventory berhasil dihapus", "success");
        })
        .catch((err) => {
          safeToast("Gagal menghapus inventory", "error");
        });
    });
  });

  let CURRENT_QR_URL = null;
  let CURRENT_QR_FILENAME = null;

  document.addEventListener("click", function (e) {
    /* ===============================
     OPEN MODAL QR
     =============================== */
    const btn = e.target.closest(".btn-show-qr");
    if (btn) {
      const qrUrl = btn.dataset.qr;
      const itemName = btn.dataset.item;
      const inventoryNo = btn.dataset.no;
      const id = btn.dataset.id;

      const modalQr = document.getElementById("modalQr");
      modalQr.dataset.inventoryId = id;

      CURRENT_QR_URL = qrUrl;
      CURRENT_QR_FILENAME =
        itemName.replace(/\s+/g, "_") +
        "_" +
        inventoryNo.replace(/\s+/g, "_") +
        "_QR.png";

      const img = document.getElementById("qrImage");
      img.src = qrUrl + "?v=" + Date.now();
      bootstrap.Modal.getOrCreateInstance(
        document.getElementById("modalQr"),
      ).show();
    }

    /* ===============================
     DOWNLOAD QR (INI FIX UTAMA)
     =============================== */
    if (e.target.closest("#btnDownloadQr")) {
      if (!CURRENT_QR_URL) {
        alert("QR belum siap");
        return;
      }

      fetch(CURRENT_QR_URL)
        .then((res) => res.blob())
        .then((blob) => {
          const url = URL.createObjectURL(blob);

          const a = document.createElement("a");
          a.href = url;
          a.download = CURRENT_QR_FILENAME;
          document.body.appendChild(a);
          a.click();

          document.body.removeChild(a);
          URL.revokeObjectURL(url);
        })
        .catch(() => {
          alert("Gagal download QR");
        });
    }
  });

  document.addEventListener("click", function (e) {
    const btn = e.target.closest("#btnRegenQrModal");
    if (!btn) return;

    const modal = document.getElementById("modalQr");
    const id = modal?.dataset.inventoryId;

    if (!id) {
      safeToast("ID tidak ditemukan", "error");
      return;
    }

    fetch(relUrl(`/compliance/inventory/regenerate-qr/${id}`), {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((r) => r.json())
      .then((res) => {
        if (res.status === "success") {
          const img = modal.querySelector("#qrImage");
          img.src = `/uploads/qr/${res.qr_image}?t=${Date.now()}`;

          // update dataset tombol edit juga
          const editBtn = document.querySelector(`.btn-edit[data-id="${id}"]`);
          if (editBtn) editBtn.dataset.qr = res.qr_image;

          safeToast("QR berhasil diperbarui", "success");
        }
      });
  });
});
