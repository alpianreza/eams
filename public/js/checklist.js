document.addEventListener("DOMContentLoaded", function () {
  function swalToast(message, type = "success") {
    Swal.fire({
      icon: type,
      title: type === "success" ? "Berhasil" : "Perhatian",
      text: message,
      timer: 3000,
      showConfirmButton: false,
      toast: true,
      position: "top-end",
    });
  }

  /* ================= TOAST FLASH ================= */
  if (window.CHECKLIST_FLASH?.success) {
    swalToast(window.CHECKLIST_FLASH.success, "success");
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  if (window.CHECKLIST_FLASH?.error) {
    swalToast(window.CHECKLIST_FLASH.error, "error");
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  /* ================= CONFIG FOTO ================= */
  const MAX_WIDTH = 1280;
  const QUALITY = 0.7;
  const CURRENT_USER = window.CHECKLIST_USER || "User";

  function formatDate(date) {
    const pad = (n) => n.toString().padStart(2, "0");
    return (
      date.getFullYear() +
      "-" +
      pad(date.getMonth() + 1) +
      "-" +
      pad(date.getDate()) +
      " " +
      pad(date.getHours()) +
      ":" +
      pad(date.getMinutes())
    );
  }

  /* ================= TOGGLE OK / NG ================= */
  document.querySelectorAll(".status-radio").forEach((radio) => {
    radio.addEventListener("change", function () {
      const qid = this.dataset.qid;
      if (!qid) return;

      const isNg = this.value === "ng";
      const alertEl = document.getElementById("ng-alert-" + qid);
      const fieldsEl = document.getElementById("ng-fields-" + qid);
      if (!alertEl || !fieldsEl) return;

      const remark = fieldsEl.querySelector("textarea");
      const photo = fieldsEl.querySelector("input[type='file']");

      if (isNg && !fieldsEl.dataset.scrolled) {
        setTimeout(() => {
          fieldsEl.scrollIntoView({ behavior: "smooth", block: "center" });
          fieldsEl.dataset.scrolled = "1";
        }, 150);
        alertEl.classList.remove("d-none");
        fieldsEl.classList.remove("d-none");
        if (remark) {
          remark.required = true;
          setTimeout(() => remark.focus(), 200);
        }
        if (photo) photo.required = true;

        setTimeout(() => {
          fieldsEl.scrollIntoView({ behavior: "smooth", block: "center" });
        }, 150);
      } else {
        alertEl.classList.add("d-none");
        fieldsEl.classList.add("d-none");

        if (remark) {
          remark.required = false;
          remark.value = "";
        }
        if (photo) {
          photo.required = false;
          photo.value = "";
        }
      }
    });
  });

  /* ================= TANDAI SEMUA OK ================= */
  const btnOkAll = document.getElementById("btn-ok-all");
  if (btnOkAll) {
    btnOkAll.addEventListener("click", function () {
      document
        .querySelectorAll(".status-radio[value='ok']")
        .forEach((radio) => {
          radio.checked = true;
          radio.dispatchEvent(new Event("change", { bubbles: true }));
        });
    });
  }

  /* ================= VALIDASI SUBMIT ================= */
  const form = document.getElementById("checklistForm");
  if (!form) {
    console.error("Form checklistForm tidak ditemukan");
    return;
  }

  form.addEventListener("submit", function (e) {
    let valid = true;
    let firstError = null;

    document
      .querySelectorAll(".status-radio[value='ng']:checked")
      .forEach((radio) => {
        const qid = radio.dataset.qid;
        const fieldsEl = document.getElementById("ng-fields-" + qid);
        if (!fieldsEl) return;

        const remark = fieldsEl.querySelector("textarea");
        const photo = fieldsEl.querySelector("input[type='file']");

        if (
          !remark ||
          remark.value.trim() === "" ||
          !photo ||
          photo.files.length === 0
        ) {
          valid = false;
          fieldsEl.classList.add("border", "border-warning", "rounded");
          if (!firstError) firstError = fieldsEl;
        } else {
          fieldsEl.classList.remove("border", "border-warning", "rounded");
        }
      });

    if (!valid) {
      e.preventDefault();

      Swal.fire({
        icon: "warning",
        title: "Checklist belum lengkap",
        text: "Item NOT OK wajib diisi catatan dan foto",
        confirmButtonText: "OK",
      });

      if (firstError) {
        firstError.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  });

  /* ================= KOMPRES + WATERMARK ================= */
  document
    .querySelectorAll("input[type='file'][name^='photos']")
    .forEach((input) => {
      input.addEventListener("change", function () {
        const file = this.files[0];
        if (!file || !file.type.startsWith("image/")) return;

        const reader = new FileReader();
        reader.onload = function (e) {
          const img = new Image();
          img.onload = function () {
            let w = img.width;
            let h = img.height;
            if (w > MAX_WIDTH) {
              h = Math.round(h * (MAX_WIDTH / w));
              w = MAX_WIDTH;
            }

            const canvas = document.createElement("canvas");
            canvas.width = w;
            canvas.height = h;

            const ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0, w, h);

            const fontSize = Math.max(14, Math.round(w * 0.018));
            ctx.font = `${fontSize}px Arial`;
            ctx.fillStyle = "rgba(0,0,0,0.45)";

            const dateText = formatDate(new Date());
            const userText = CURRENT_USER;
            ctx.fillRect(w - 320, h - 60, 310, 50);

            ctx.fillStyle = "#fff";
            ctx.fillText(dateText, w - 300, h - 30);
            ctx.fillText(userText, w - 300, h - 10);

            canvas.toBlob(
              (blob) => {
                if (!blob) return;
                const f = new File([blob], "photo.jpg", { type: "image/jpeg" });
                const dt = new DataTransfer();
                dt.items.add(f);
                input.files = dt.files;
              },
              "image/jpeg",
              QUALITY,
            );
          };
          img.src = e.target.result;
        };
        reader.readAsDataURL(file);
      });
    });
});
