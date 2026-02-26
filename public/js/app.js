document.addEventListener("DOMContentLoaded", function () {
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
        toast.style.marginTop = "60px";
        toast.style.marginLeft = "15px";
      },
    });
  };

  // ✅ ALIAS — biar semua module lama tetap jalan
  window.appToast = window.safeToast;
});
