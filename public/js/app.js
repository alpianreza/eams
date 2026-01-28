function showToast(message, type = "success") {
  const toastEl = document.getElementById("appToast");
  const msgEl = document.getElementById("toastMessage");

  toastEl.className = `toast align-items-center text-bg-${type} border-0`;
  msgEl.innerText = message;

  new bootstrap.Toast(toastEl, {
    delay: 3000, // 3 detik
  }).show();
}

document.addEventListener("DOMContentLoaded", function () {
  /* ===============================
     DATATABLE INVENTORY
     =============================== */
  if (window.$ && $("#inventoryTable").length) {
    $("#inventoryTable").DataTable({
      paging: false,
      info: false,
      searching: true,
      ordering: true,
    });
  }

  /* ===============================
     DELETE CONFIRMATION (SweetAlert)
     =============================== */
  document.querySelectorAll(".btn-delete").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const form = this.closest("form");

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
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
});

// ===============================
// GLOBAL TOAST (ADMINLTE)
// ===============================
window.safeToast = function (message, type = "success") {
  const toastEl = document.getElementById("appToast");
  const toastMsg = document.getElementById("toastMessage");

  if (!toastEl || !toastMsg) {
    console.warn("Toast element not found");
    return;
  }

  toastEl.className = "toast align-items-center border-0";
  toastEl.classList.add(
    type === "success" ? "text-bg-success" : "text-bg-danger",
  );

  toastMsg.innerText = message;

  bootstrap.Toast.getOrCreateInstance(toastEl, {
    delay: 3000,
  }).show();
};
