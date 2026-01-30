document.addEventListener("DOMContentLoaded", function () {
  /* =====================================================
     SWEETALERT2 GLOBAL TOAST (KIRI ATAS)
  ===================================================== */
  window.safeToast = function (message, type = "success") {
    Swal.fire({
      toast: true,
      position: "top-right",
      icon: type,
      title: message,
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      customClass: {
        popup: "colored-toast",
      },
      didOpen: (toast) => {
        toast.style.marginTop = "60px"; // aman AdminLTE
        toast.style.marginLeft = "15px";
      },
    });
  };

  /* =====================================================
     DATATABLE INVENTORY (OPSIONAL)
     → boleh kamu hapus kalau benar-benar ga dipakai
  ===================================================== */
  if (window.$ && $("#inventoryTable").length) {
    $("#inventoryTable").DataTable({
      paging: false,
      info: false,
      searching: true,
      ordering: true,
      autoWidth: false,
    });
  }
});
