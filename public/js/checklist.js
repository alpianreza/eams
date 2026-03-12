function normalizeFormAction(form) {
  const raw = form.getAttribute("action");
  if (!raw) return;

  const url = new URL(raw, window.location.origin);
  form.setAttribute("action", url.pathname + url.search);
}

function bindStatusRadios() {
  document.querySelectorAll(".status-radio").forEach((radio) => {
    radio.addEventListener("change", function () {
      const qid = this.dataset.qid;
      if (!qid) return;

      const row = this.closest("tr");
      if (row) row.classList.remove("table-danger");

      const isNotOk = this.value === "not_ok";
      const detailRow = document.getElementById("ng-row-" + qid);
      if (!detailRow) return;

      const remark = detailRow.querySelector("textarea");
      const photo = detailRow.querySelector("input[type='file']");

      if (isNotOk) {
        detailRow.classList.remove("d-none");
        if (remark) setTimeout(() => remark.focus(), 150);
        return;
      }

      detailRow.classList.add("d-none");
      if (remark) remark.value = "";
      if (photo) photo.value = "";
    });
  });
}

function bindMarkAllOk() {
  const btnOkAll = document.getElementById("btn-ok-all");
  if (!btnOkAll) return;

  btnOkAll.addEventListener("click", () => {
    document.querySelectorAll(".status-radio[value='ok']").forEach((radio) => {
      radio.checked = true;
      radio.dispatchEvent(new Event("change", { bubbles: true }));
    });
  });
}

function validateChecklistForm(form, e) {
  let valid = true;

  const radios = form.querySelectorAll(".status-radio");
  const groups = {};

  radios.forEach((radio) => {
    groups[radio.name] = false;
  });

  radios.forEach((radio) => {
    if (radio.checked) groups[radio.name] = true;
  });

  const invalidGroups = Object.keys(groups).filter((name) => !groups[name]);

  if (invalidGroups.length) {
    valid = false;

    invalidGroups.forEach((name) => {
      const el = form.querySelector(`input[name="${name}"]`);
      if (el) el.closest("tr")?.classList.add("table-danger");
    });

    const first = form.querySelector(`input[name="${invalidGroups[0]}"]`);
    first?.closest("tr")?.scrollIntoView({ behavior: "smooth" });
  }

  form
    .querySelectorAll(".status-radio[value='not_ok']:checked")
    .forEach((radio) => {
      const qid = radio.dataset.qid;
      const row = document.getElementById("ng-row-" + qid);
      if (!row) return;

      const remark = row.querySelector("textarea");
      const photo = row.querySelector("input[type='file']");

      const remarkValue = remark ? remark.value.trim() : "";
      const hasPhoto = !!(photo && photo.files.length);

      if (!remarkValue && !hasPhoto) {
        valid = false;
        row.classList.add("border", "border-danger", "rounded");
      } else {
        row.classList.remove("border", "border-danger", "rounded");
      }
    });

  if (valid) return;

  e.preventDefault();

  const firstError =
    form.querySelector(".table-danger") || form.querySelector(".border-danger");

  firstError?.scrollIntoView({ behavior: "smooth", block: "center" });
  window.safeToast?.("Lengkapi checklist dulu", "warning");
}

function bindChecklistForm() {
  const form = document.getElementById("checklistForm");
  if (!form) return;

  if (form.dataset.bound) return;
  form.dataset.bound = "1";

  normalizeFormAction(form);

  form.addEventListener("submit", function (e) {
    validateChecklistForm(form, e);
  });
}

function bindPhotoCompression() {
  document
    .querySelectorAll("input[type='file'][name^='photos']")
    .forEach((input) => {
      input.onchange = function () {
        const file = this.files[0];
        if (!file || !file.type.startsWith("image/")) return;

        const reader = new FileReader();
        reader.onload = function (event) {
          const img = new Image();
          img.onload = function () {
            const MAX_WIDTH = 1280;
            const QUALITY = 0.7;
            const CURRENT_USER = window.CHECKLIST_USER || "User";

            let width = img.width;
            let height = img.height;

            if (width > MAX_WIDTH) {
              height = Math.round(height * (MAX_WIDTH / width));
              width = MAX_WIDTH;
            }

            const canvas = document.createElement("canvas");
            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0, width, height);

            ctx.fillStyle = "rgba(0,0,0,0.45)";
            ctx.fillRect(width - 320, height - 60, 310, 50);

            ctx.fillStyle = "#fff";
            ctx.font = `${Math.max(14, Math.round(width * 0.018))}px Arial`;

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

            ctx.fillText(dateText, width - 300, height - 30);
            ctx.fillText(CURRENT_USER, width - 300, height - 10);

            canvas.toBlob(
              (blob) => {
                if (!blob) return;

                const converted = new File([blob], "photo.jpg", {
                  type: "image/jpeg",
                });

                const dt = new DataTransfer();
                dt.items.add(converted);
                input.files = dt.files;
              },
              "image/jpeg",
              QUALITY,
            );
          };

          img.src = event.target.result;
        };

        reader.readAsDataURL(file);
      };
    });
}

function initChecklistUI() {
  bindStatusRadios();
  bindMarkAllOk();
  bindChecklistForm();
  bindPhotoCompression();
}

document.addEventListener("DOMContentLoaded", () => {
  initChecklistUI();
});

window.reInitChecklistUI = function () {
  initChecklistUI();
};

document.addEventListener("click", (e) => {
  const link = e.target.closest(".calendar-grid a, .calendar-nav a");
  if (!link) return;

  e.preventDefault();

  const urlObj = new URL(link.getAttribute("href"), window.location.origin);
  const url = urlObj.pathname + urlObj.search;

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

      container.querySelectorAll("form").forEach((form) => {
        normalizeFormAction(form);
      });

      window.reInitChecklistUI?.();
      window.history.pushState({}, "", url);
    })
    .catch(console.error);
});
