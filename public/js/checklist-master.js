function relUrl(raw) {
  const u = new URL(raw, window.location.origin);
  return u.pathname + u.search;
}

document.addEventListener("DOMContentLoaded", function () {
  const modalAddEl = document.getElementById("modalAdd");
  const modalEditEl = document.getElementById("modalEdit");
  const formAdd = document.getElementById("formAdd");
  const formEdit = document.getElementById("formEdit");
  const frequencySelect = document.getElementById("itemFrequency");

  const addModal = modalAddEl ? bootstrap.Modal.getOrCreateInstance(modalAddEl) : null;
  const editModal = modalEditEl ? bootstrap.Modal.getOrCreateInstance(modalEditEl) : null;

  if (formAdd) {
    formAdd.addEventListener("submit", function (event) {
      event.preventDefault();

      fetch(relUrl(formAdd.action), {
        method: "POST",
        body: new FormData(formAdd),
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.status === "success") {
            safeToast("Pertanyaan berhasil ditambahkan.", "success");
            addModal?.hide();
            setTimeout(() => window.location.reload(), 400);
            return;
          }

          safeToast(res.message || "Gagal menyimpan data.", "error");
        })
        .catch(() => {
          safeToast("Terjadi kesalahan server.", "error");
        });
    });
  }

  document.addEventListener("click", function (event) {
    const editButton = event.target.closest('[data-action="edit"]');
    if (editButton && formEdit) {
      const questionField = formEdit.querySelector("#edit_question");
      const activeField = formEdit.querySelector("#edit_active");
      const requirePhotoField = formEdit.querySelector("#edit_require_photo");

      if (questionField) questionField.value = editButton.dataset.question || "";
      if (activeField) activeField.value = editButton.dataset.active || "1";
      if (requirePhotoField) requirePhotoField.value = editButton.dataset.requirePhoto || "0";

      formEdit.action = editButton.dataset.updateUrl || "";
      editModal?.show();
      return;
    }

    const deleteButton = event.target.closest('[data-action="delete"]');
    if (!deleteButton) {
      return;
    }

    Swal.fire({
      title: "Yakin hapus?",
      text: "Pertanyaan yang dihapus tidak bisa dikembalikan.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Ya, hapus",
      cancelButtonText: "Batal",
    }).then((result) => {
      if (!result.isConfirmed) return;

      fetch(relUrl(deleteButton.dataset.url), {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.status === "success") {
            safeToast("Pertanyaan berhasil dihapus.", "success");
            setTimeout(() => window.location.reload(), 300);
            return;
          }

          safeToast("Tidak bisa menghapus data.", "error");
        })
        .catch(() => {
          safeToast("Terjadi kesalahan server.", "error");
        });
    });
  });

  if (formEdit) {
    formEdit.addEventListener("submit", function (event) {
      event.preventDefault();

      fetch(relUrl(formEdit.action), {
        method: "POST",
        body: new FormData(formEdit),
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.status === "success") {
            safeToast("Pertanyaan berhasil diperbarui.", "success");
            editModal?.hide();
            setTimeout(() => window.location.reload(), 300);
            return;
          }

          safeToast(res.message || "Gagal update data.", "error");
        })
        .catch(() => {
          safeToast("Terjadi kesalahan server.", "error");
        });
    });
  }

  frequencySelect?.addEventListener("change", function () {
    fetch(relUrl(this.dataset.url), {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      body: new URLSearchParams({
        frequency: this.value,
      }),
    })
      .then((res) => res.json())
      .then((res) => {
        if (res.status === "success") {
          safeToast("Frekuensi item berhasil diubah.", "success");
          setTimeout(() => window.location.reload(), 250);
          return;
        }

        safeToast(res.message || "Gagal mengubah frekuensi.", "error");
      })
      .catch(() => {
        safeToast("Terjadi kesalahan server.", "error");
      });
  });
});
