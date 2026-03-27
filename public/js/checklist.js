"use strict";

function normalizeFormAction(form) {
  const rawAction = form.getAttribute("action");
  if (!rawAction) {
    return;
  }

  const url = new URL(rawAction, window.location.origin);
  form.setAttribute("action", url.pathname + url.search);
}

function updateChecklistProgress(form) {
  if (!form) {
    return;
  }

  const grouped = {};
  form.querySelectorAll(".status-radio").forEach((radio) => {
    grouped[radio.name] = grouped[radio.name] || false;
    if (radio.checked) {
      grouped[radio.name] = true;
    }
  });

  const total = Object.keys(grouped).length;
  const filled = Object.values(grouped).filter(Boolean).length;
  const percent = total > 0 ? Math.round((filled / total) * 100) : 0;

  const value = form.querySelector("#checklistProgressValue");
  const bar = form.querySelector("#checklistProgressBar");
  const text = form.querySelector("#checklistProgressText");

  if (value) {
    value.textContent = `${filled}/${total}`;
  }

  if (bar) {
    bar.style.width = `${percent}%`;
    bar.setAttribute("aria-valuenow", String(percent));
  }

  if (text) {
    if (filled === total && total > 0) {
      text.textContent = "Semua pertanyaan sudah diisi. Siap disimpan.";
    } else {
      text.textContent = "Pilih status untuk setiap pertanyaan.";
    }
  }
}

function bindStatusRadios(form) {
  form.querySelectorAll(".status-radio").forEach((radio) => {
    radio.addEventListener("change", function () {
      const questionId = this.dataset.qid;
      if (!questionId) {
        return;
      }

      const currentRow = this.closest("tr");
      currentRow?.classList.remove("table-danger");

      const detailRow = document.getElementById(`not_ok-row-${questionId}`);
      if (!detailRow) {
        updateChecklistProgress(form);
        return;
      }

      const isNotOk = this.value === "not_ok";
      const remarkInput = detailRow.querySelector("textarea");
      const photoInput = detailRow.querySelector("input[type='file']");

      if (isNotOk) {
        detailRow.classList.remove("d-none");
        setTimeout(() => remarkInput?.focus(), 120);
      } else {
        detailRow.classList.add("d-none");
        detailRow.classList.remove("border", "border-danger", "rounded");

        if (remarkInput) remarkInput.value = "";
        if (photoInput) photoInput.value = "";
      }

      updateChecklistProgress(form);
    });
  });
}

function bindMarkAllOk(form) {
  const markAllButton = form.querySelector("#btn-ok-all");
  if (!markAllButton) {
    return;
  }

  markAllButton.addEventListener("click", function () {
    form.querySelectorAll(".status-radio[value='ok']").forEach((radio) => {
      radio.checked = true;
      radio.dispatchEvent(new Event("change", { bubbles: true }));
    });

    window.safeToast?.("Semua pertanyaan ditandai sesuai.", "success");
  });
}

function validateChecklistForm(form, event) {
  let isValid = true;

  const grouped = {};
  form.querySelectorAll(".status-radio").forEach((radio) => {
    grouped[radio.name] = grouped[radio.name] || false;
    if (radio.checked) {
      grouped[radio.name] = true;
    }
  });

  const invalidGroups = Object.keys(grouped).filter((name) => !grouped[name]);
  if (invalidGroups.length > 0) {
    isValid = false;

    invalidGroups.forEach((name) => {
      const input = form.querySelector(`input[name="${name}"]`);
      input?.closest("tr")?.classList.add("table-danger");
    });
  }

  form.querySelectorAll(".status-radio[value='not_ok']:checked").forEach((radio) => {
    const questionId = radio.dataset.qid;
    const detailRow = document.getElementById(`not_ok-row-${questionId}`);
    if (!detailRow) {
      return;
    }

    const remarkInput = detailRow.querySelector("textarea");
    const photoInput = detailRow.querySelector("input[type='file']");

    const hasRemark = (remarkInput?.value || "").trim().length > 0;
    const hasPhoto = !!(photoInput && photoInput.files.length > 0);

    if (!hasRemark && !hasPhoto) {
      isValid = false;
      detailRow.classList.add("border", "border-danger", "rounded");
    } else {
      detailRow.classList.remove("border", "border-danger", "rounded");
    }
  });

  if (isValid) {
    return;
  }

  event.preventDefault();

  const firstError = form.querySelector(".table-danger") || form.querySelector(".border-danger");
  firstError?.scrollIntoView({ behavior: "smooth", block: "center" });

  window.safeToast?.("Lengkapi isian checklist terlebih dahulu.", "warning");
}

function bindChecklistForm() {
  const form = document.getElementById("checklistForm");
  if (!form) {
    return;
  }

  if (form.dataset.bound === "1") {
    return;
  }

  form.dataset.bound = "1";
  normalizeFormAction(form);

  bindStatusRadios(form);
  bindMarkAllOk(form);
  bindPhotoCompression(form);
  updateChecklistProgress(form);

  form.addEventListener("submit", function (event) {
    validateChecklistForm(form, event);
  });
}

function bindPhotoCompression(form) {
  form.querySelectorAll("input[type='file'][name^='photos']").forEach((input) => {
    input.addEventListener("change", function () {
      const file = this.files?.[0];
      if (!file || !file.type.startsWith("image/")) {
        return;
      }

      const reader = new FileReader();
      reader.onload = function (event) {
        const image = new Image();
        image.onload = function () {
          const maxWidth = 1280;
          const quality = 0.7;
          const currentUser = window.CHECKLIST_USER || "User";

          let width = image.width;
          let height = image.height;

          if (width > maxWidth) {
            height = Math.round(height * (maxWidth / width));
            width = maxWidth;
          }

          const canvas = document.createElement("canvas");
          canvas.width = width;
          canvas.height = height;

          const context = canvas.getContext("2d");
          context.drawImage(image, 0, 0, width, height);

          context.fillStyle = "rgba(0,0,0,0.45)";
          context.fillRect(width - 320, height - 62, 310, 52);

          context.fillStyle = "#fff";
          context.font = `${Math.max(14, Math.round(width * 0.018))}px Arial`;

          const now = new Date();
          const dateText = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")} ${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}`;

          context.fillText(dateText, width - 300, height - 34);
          context.fillText(currentUser, width - 300, height - 14);

          canvas.toBlob(
            (blob) => {
              if (!blob) {
                return;
              }

              const converted = new File([blob], "photo.jpg", { type: "image/jpeg" });
              const transfer = new DataTransfer();
              transfer.items.add(converted);
              input.files = transfer.files;
            },
            "image/jpeg",
            quality,
          );
        };

        image.src = event.target?.result;
      };

      reader.readAsDataURL(file);
    });
  });
}

function showChecklistFlash() {
  const flash = window.CHECKLIST_FLASH || {};

  if (flash.success) {
    window.safeToast?.(flash.success, "success");
  }

  if (flash.error) {
    window.safeToast?.(flash.error, "error");
  }
}

function setChecklistAjaxLoading(isLoading) {
  const container = document.getElementById("checklistAjax");
  if (!container) {
    return;
  }

  container.classList.toggle("is-loading", isLoading);
}

function renderChecklistFromUrl(targetUrl, updateHistory = true) {
  const url = new URL(targetUrl, window.location.origin);
  const requestUrl = url.pathname + url.search;

  setChecklistAjaxLoading(true);

  fetch(requestUrl, {
    headers: { "X-Requested-With": "XMLHttpRequest" },
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Gagal memuat checklist");
      }

      return response.text();
    })
    .then((html) => {
      const container = document.getElementById("checklistAjax");
      if (!container) {
        return;
      }

      container.innerHTML = html;
      container.querySelectorAll("form").forEach((form) => normalizeFormAction(form));

      bindChecklistForm();

      if (updateHistory) {
        window.history.pushState({}, "", requestUrl);
      }
    })
    .catch(() => {
      window.safeToast?.("Gagal memuat checklist.", "error");
    })
    .finally(() => {
      setChecklistAjaxLoading(false);
    });
}

function initChecklistAjaxNavigation() {
  document.addEventListener("click", function (event) {
    const link = event.target.closest(".calendar-grid a, .calendar-nav a, .checklist-slot-links a");
    if (!link) {
      return;
    }

    event.preventDefault();
    renderChecklistFromUrl(link.getAttribute("href"));
  });

  window.addEventListener("popstate", function () {
    renderChecklistFromUrl(window.location.href, false);
  });
}

function initChecklistUI() {
  bindChecklistForm();
  showChecklistFlash();
  initChecklistAjaxNavigation();
}

document.addEventListener("DOMContentLoaded", initChecklistUI);
