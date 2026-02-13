document.addEventListener("DOMContentLoaded", function () {
  /* ===============================
     ADD CHECKLIST QUESTION (AJAX)
     =============================== */
  const formAdd = document.querySelector("#modalAdd form");
  if (formAdd) {
    formAdd.addEventListener("submit", function (e) {
      e.preventDefault();

      fetch(formAdd.action, {
        method: "POST",
        body: new FormData(formAdd),
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.status === "success") {
            safeToast("Pertanyaan berhasil ditambahkan", "success");
            bootstrap.Modal.getInstance(
              document.getElementById("modalAdd"),
            ).hide();

            setTimeout(() => location.reload(), 500);
          } else {
            safeToast("Gagal menyimpan data", "danger");
          }
        })
        .catch(() => {
          safeToast("Terjadi kesalahan server", "danger");
        });
    });
  }

  /* ===============================
     EDIT BUTTON CLICK (OPEN MODAL)
     =============================== */
  document.addEventListener("click", function (e) {
    const btn = e.target.closest('[data-action="edit"]');
    if (!btn) return;

    document.getElementById("edit_question").value = btn.dataset.question;

    document.getElementById("edit_active").checked = btn.dataset.active == 1;

    const formEdit = document.getElementById("formEdit");
    formEdit.action = btn.dataset.updateUrl;

    new bootstrap.Modal(document.getElementById("modalEdit")).show();
  });

  const formEdit = document.getElementById("formEdit");
  if (formEdit) {
    formEdit.addEventListener("submit", function (e) {
      e.preventDefault();

      fetch(formEdit.action, {
        method: "POST",
        body: new FormData(formEdit),
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.status === "success") {
            safeToast("Pertanyaan berhasil diupdate", "success");
            bootstrap.Modal.getInstance(
              document.getElementById("modalEdit"),
            ).hide();

            setTimeout(() => location.reload(), 500);
          } else {
            safeToast("Gagal update data", "danger");
          }
        })
        .catch(() => {
          safeToast("Terjadi kesalahan server", "danger");
        });
    });
  }

  /* ===============================
   UPDATE ITEM FREQUENCY
   =============================== */
  document
    .getElementById("itemFrequency")
    ?.addEventListener("change", function () {
      fetch(this.dataset.url, {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: new URLSearchParams({ frequency: this.value }),
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.status === "success") {
            safeToast("Frekuensi item berhasil diubah", "success");
            setTimeout(() => location.reload(), 300);
          } else {
            safeToast(res.message || "Gagal mengubah frekuensi", "danger");
          }
        })
        .catch(() => {
          safeToast("Terjadi kesalahan server", "danger");
        });
    });
});

/* ===============================
   DELETE CHECKLIST QUESTION
================================ */
/* ===============================
   DELETE CHECKLIST QUESTION
================================ */
document.addEventListener("click", function (e) {
  const btn = e.target.closest('[data-action="delete"]');
  if (!btn) return;

  Swal.fire({
    title: "Yakin hapus?",
    text: "Data yang dihapus tidak bisa dikembalikan!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Ya, hapus",
    cancelButtonText: "Batal",
  }).then((result) => {
    if (!result.isConfirmed) return;

    fetch(btn.dataset.url, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((res) => res.json())
      .then((res) => {
        if (res.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: "Pertanyaan berhasil dihapus",
            timer: 1200,
            showConfirmButton: false,
          });

          setTimeout(() => location.reload(), 1200);
        } else {
          Swal.fire("Gagal", "Tidak bisa menghapus data", "error");
        }
      })
      .catch(() => {
        Swal.fire("Error", "Terjadi kesalahan server", "error");
      });
  });
});
