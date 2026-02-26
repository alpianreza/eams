function initChecklistUI() {
  /* ================= TOGGLE OK / NG ================= */
  document.querySelectorAll(".status-radio").forEach((radio) => {
    radio.addEventListener("change", function () {
      const qid = this.dataset.qid;
      if (!qid) return;

      // ✅ HAPUS MERAH BARIS SAAT SUDAH DIISI
      const tr = this.closest("tr");
      if (tr) tr.classList.remove("table-danger");

      const isNg = this.value === "ng";
      const rowEl = document.getElementById("ng-row-" + qid);
      if (!rowEl) return;

      const remark = rowEl.querySelector("textarea");
      const photo = rowEl.querySelector("input[type='file']");

      if (isNg) {
        rowEl.classList.remove("d-none");
        if (remark) setTimeout(() => remark.focus(), 150);
      } else {
        rowEl.classList.add("d-none");
        if (remark) remark.value = "";
        if (photo) photo.value = "";
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
  /* ================= FORM + VALIDASI ================= */
  const form = document.getElementById("checklistForm");

  if (form) {
    if (form.dataset.bound) return;
    form.dataset.bound = "1";

    // ✅ FIX MIXED CONTENT — pakai relative path
    const raw = form.getAttribute("action");
    if (raw) {
      const url = new URL(raw, window.location.origin);
      form.setAttribute("action", url.pathname + url.search);
    }

    form.addEventListener("submit", function (e) {
      let valid = true;

      const radios = form.querySelectorAll(".status-radio");
      const groups = {};

      radios.forEach((r) => (groups[r.name] = false));
      radios.forEach((r) => {
        if (r.checked) groups[r.name] = true;
      });

      const invalidGroups = Object.keys(groups).filter((n) => !groups[n]);

      if (invalidGroups.length) {
        valid = false;

        invalidGroups.forEach((name) => {
          const el = form.querySelector(`input[name="${name}"]`);
          if (el) el.closest("tr").classList.add("table-danger");
        });

        const first = form.querySelector(`input[name="${invalidGroups[0]}"]`);
        if (first) first.closest("tr").scrollIntoView({ behavior: "smooth" });
      }

      form
        .querySelectorAll(".status-radio[value='ng']:checked")
        .forEach((radio) => {
          const qid = radio.dataset.qid;
          const row = document.getElementById("ng-row-" + qid);
          if (!row) return;

          const remark = row.querySelector("textarea");
          const photo = row.querySelector("input[type='file']");

          const remarkValue = remark ? remark.value.trim() : "";
          const hasPhoto = photo && photo.files.length;

          if (!remarkValue && !hasPhoto) {
            valid = false;
            row.classList.add("border", "border-danger", "rounded");
          } else {
            row.classList.remove("border", "border-danger", "rounded");
          }
        });

      if (!valid) {
        e.preventDefault();

        // fokus ke baris error pertama
        const firstError =
          form.querySelector(".table-danger") ||
          form.querySelector(".border-danger");

        if (firstError) {
          firstError.scrollIntoView({ behavior: "smooth", block: "center" });
        }

        // ✅ tampilkan toast
        window.safeToast?.("Lengkapi checklist dulu", "warning");
      }
    });
  }

  /* ================= FOTO COMPRESS + WATERMARK ================= */
  document
    .querySelectorAll("input[type='file'][name^='photos']")
    .forEach((input) => {
      input.onchange = function () {
        const file = this.files[0];
        if (!file || !file.type.startsWith("image/")) return;

        const reader = new FileReader();
        reader.onload = function (e) {
          const img = new Image();
          img.onload = function () {
            const MAX_WIDTH = 1280;
            const QUALITY = 0.7;
            const CURRENT_USER = window.CHECKLIST_USER || "User";

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

            ctx.fillStyle = "rgba(0,0,0,0.45)";
            ctx.fillRect(w - 320, h - 60, 310, 50);

            ctx.fillStyle = "#fff";
            ctx.font = `${Math.max(14, Math.round(w * 0.018))}px Arial`;

            const now = new Date();
            const dateText =
              now.getFullYear() +
              "-" +
              String(now.getMonth() + 1).padStart(2, "0") +
              "-" +
              String(now.getDate()).padStart(2, "0") +
              " " +
              String(now.getHours()).padStart(2, "0") +
              ":" +
              String(now.getMinutes()).padStart(2, "0");

            ctx.fillText(dateText, w - 300, h - 30);
            ctx.fillText(CURRENT_USER, w - 300, h - 10);

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
      };
    });
}

/* ================= INIT ================= */
document.addEventListener("DOMContentLoaded", function () {
  initChecklistUI();
});

/* ================= AJAX RE-INIT ================= */
// Panggil ini SETELAH checklistAjax.innerHTML diganti
window.reInitChecklistUI = function () {
  initChecklistUI();
};

document.addEventListener("click", function (e) {
  const link = e.target.closest(".calendar-grid a, .calendar-nav a");
  if (!link) return;

  e.preventDefault();

  const u = new URL(link.getAttribute("href"), window.location.origin);
  const url = u.pathname + u.search;

  fetch(url, {
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((res) => res.text())
    .then((html) => {
      const container = document.getElementById("checklistAjax");
      if (!container) return;

      container.innerHTML = html;

      // ✅ normalize form action → relative
      container.querySelectorAll("form").forEach((form) => {
        const raw = form.getAttribute("action");
        if (!raw) return;
        const fu = new URL(raw, window.location.origin);
        form.setAttribute("action", fu.pathname + fu.search);
      });

      // reinit UI
      window.reInitChecklistUI?.();

      window.history.pushState({}, "", url);
    })
    .catch(console.error);
});
