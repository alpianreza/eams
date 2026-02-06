document.addEventListener("DOMContentLoaded", function () {
  /* =====================================================
     =============== EDIT INVENTORY ======================
  ===================================================== */
  const editModalEl = document.getElementById("modalEditInventory");
  const editForm = document.getElementById("formEditInventory");
  const qrModalEl = document.getElementById("modalQr");

  let currentRow = null;

  if (editModalEl && editForm) {
    const editModal = bootstrap.Modal.getOrCreateInstance(editModalEl);
    const qrModal = qrModalEl
      ? bootstrap.Modal.getOrCreateInstance(qrModalEl)
      : null;

    /* ---------- QR MODAL ---------- */
    document.addEventListener("click", function (e) {
      const btn = e.target.closest(".btn-qr");
      if (!btn || !qrModal) return;

      const img = document.getElementById("qrImage");
      if (img) img.src = btn.dataset.qr;

      qrModal.show();
    });

    /* ---------- OPEN EDIT MODAL ---------- */
    document.addEventListener("click", function (e) {
      const btn = e.target.closest(".btn-edit");
      if (!btn) return;

      currentRow = btn.closest("tr");
      const id = btn.dataset.id;

      editForm.action = `${BASE_URL}/compliance/inventory/update/${id}`;

      editForm.querySelector("#edit_id").value = id;
      editForm.querySelector("#edit_category_text").value = currentRow
        .querySelector(".col-category")
        .innerText.trim();
      editForm.querySelector("#edit_area_text").value = currentRow
        .querySelector(".col-area")
        .innerText.trim();
      editForm.querySelector("#edit_item_name").value = currentRow
        .querySelector(".col-item")
        .innerText.trim();

      editForm.querySelector("#edit_category_id").value =
        btn.dataset.categoryId;
      editForm.querySelector("#edit_area_id").value = btn.dataset.areaId;
      editForm.querySelector("#edit_item_type_id").value =
        btn.dataset.itemTypeId;

      editForm.querySelector("#edit_code").value = btn.dataset.code || "";
      editForm.querySelector("#edit_type").value = btn.dataset.type || "";
      editForm.querySelector("#edit_pic").value = btn.dataset.pic || "";
      editForm.querySelector("#edit_status").value = btn.dataset.status || "";
      editForm.querySelector("#edit_remark").value = btn.dataset.remark || "";

      editModal.show();
    });

    /* ---------- SUBMIT EDIT (AJAX) ---------- */
    editForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();

      fetch(editForm.action, {
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

          // UPDATE ROW
          currentRow.querySelector("td:nth-of-type(5)").innerText =
            editForm.querySelector("#edit_code").value || "-";

          currentRow.querySelector("td:nth-of-type(6)").innerText =
            editForm.querySelector("#edit_type").value || "-";

          currentRow.querySelector("td:nth-of-type(8)").innerText =
            editForm.querySelector("#edit_pic").value || "-";

          const status = editForm.querySelector("#edit_status").value;
          const statusCell = currentRow.querySelector("td:nth-of-type(9)");

          const statusMap = {
            Good: '<span class="badge bg-success">Good</span>',
            "Need Repair":
              '<span class="badge bg-warning text-dark">Need Repair</span>',
            "Not Active": '<span class="badge bg-secondary">Not Active</span>',
          };

          statusCell.innerHTML = statusMap[status] ?? "-";

          currentRow.querySelector("td:nth-of-type(10)").innerText =
            editForm.querySelector("#edit_remark").value || "-";

          editModal.hide();
          cleanupModal();
          safeToast("Inventory berhasil diperbarui", "success");
        })
        .catch((err) => {
          console.error(err);
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

      fetch(addForm.action, {
        method: "POST",
        body: new FormData(addForm),
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
          console.error(err);
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

  // BASE URL aman
  const BASE = BASE_URL.replace(/\/$/, "");

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

    fetch(url, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((res) => res.text())
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, "text/html");
        const newContent = doc.querySelector("#inventoryAjax");
        if (!newContent) return;

        ajaxContainer.innerHTML = newContent.innerHTML;
        window.history.pushState({}, "", url);

        bindPagination(); // ✅ hanya pagination
      })
      .catch(console.error)
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
        BASE +
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

    loadInventory(BASE + "/compliance/inventory");
    toggleReset();
  });

  /* =========================
     PAGINATION AJAX
  ========================= */
  function bindPagination() {
    document.querySelectorAll(".pagination a").forEach((link) => {
      link.onclick = function (e) {
        e.preventDefault();
        loadInventory(this.href);
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

      fetch(form.action, {
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
          console.error(err);
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

      CURRENT_QR_URL = qrUrl;
      CURRENT_QR_FILENAME =
        itemName.replace(/\s+/g, "_") +
        "_" +
        inventoryNo.replace(/\s+/g, "_") +
        "_QR.png";

      const img = document.getElementById("qrImage");
      img.src = qrUrl;

      new bootstrap.Modal(document.getElementById("modalQr")).show();
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


});
