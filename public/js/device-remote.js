"use strict";

(function () {
  const actionLabelMap = {
    shutdown: "Shutdown OS",
    restart: "Restart OS",
    update: "Push Update",
    sync: "Sync Sekarang",
    restart_agent: "Restart Agent",
    lock: "Lock Screen",
  };

  const dangerActions = new Set(["shutdown", "restart"]);
  let lockTimer = null;

  function notify(message, type = "info") {
    if (typeof window.safeToast === "function") {
      window.safeToast(message, type);
      return;
    }

    window.alert(message);
  }

  function copyText(text) {
    if (!text) {
      return Promise.reject(new Error("empty"));
    }

    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    return new Promise((resolve, reject) => {
      try {
        const input = document.createElement("textarea");
        input.value = text;
        input.setAttribute("readonly", "readonly");
        input.style.position = "fixed";
        input.style.left = "-9999px";
        document.body.appendChild(input);
        input.focus();
        input.select();

        const ok = document.execCommand("copy");
        document.body.removeChild(input);

        if (ok) {
          resolve();
          return;
        }

        reject(new Error("copy_failed"));
      } catch (error) {
        reject(error);
      }
    });
  }

  function applyRemoteLock(lockUntil, options = {}) {
    const now = Math.floor(Date.now() / 1000);
    const parsedLockUntil = Number(lockUntil || 0);
    const remaining = Math.max(0, parsedLockUntil - now);

    if (!Number.isFinite(parsedLockUntil) || parsedLockUntil <= 0 || remaining <= 0) {
      $(".remote-btn").prop("disabled", false).attr("title", "").data("lock-until", 0);
      if (lockTimer) {
        window.clearInterval(lockTimer);
        lockTimer = null;
      }
      return;
    }

    $(".remote-btn").each(function () {
      const $btn = $(this);
      $btn.data("lock-until", parsedLockUntil);
      $btn.prop("disabled", true);
      $btn.attr("title", `Remote lock aktif (${remaining} detik)`);
    });

    if (!options.silent) {
      notify(`Remote lock aktif ${remaining} detik`, "info");
    }

    if (lockTimer) {
      window.clearInterval(lockTimer);
    }

    lockTimer = window.setInterval(() => {
      const left = Math.max(0, parsedLockUntil - Math.floor(Date.now() / 1000));

      if (left <= 0) {
        $(".remote-btn").prop("disabled", false).attr("title", "").data("lock-until", 0);
        window.clearInterval(lockTimer);
        lockTimer = null;
        notify("Remote lock selesai. Aksi bisa digunakan lagi.", "success");
        return;
      }

      $(".remote-btn").attr("title", `Remote lock aktif (${left} detik)`);
    }, 1000);
  }

  $(document).on("click", ".remote-btn", function () {
    const $button = $(this);
    const id = Number($button.data("id"));
    const action = String($button.data("action") || "").trim().toLowerCase();
    const lockUntil = Number($button.data("lock-until") || 0);
    const now = Math.floor(Date.now() / 1000);

    if (!id || !action) {
      return;
    }

    if (lockUntil > now) {
      const waitSeconds = lockUntil - now;
      notify(`Aksi masih terkunci. Coba lagi ${waitSeconds} detik lagi.`, "warning");
      return;
    }

    const actionLabel = actionLabelMap[action] || action;

    const submitAction = () => {
      $button.prop("disabled", true);

      $.post("/it/device/remote", { id, action })
        .done((res) => {
          if (res && res.ok) {
            notify(res.message || `Perintah ${actionLabel} berhasil dikirim`, "success");
            if (res.lock_until) {
              applyRemoteLock(res.lock_until, { silent: true });
            }
            return;
          }

          if (res && res.lock_until) {
            applyRemoteLock(res.lock_until, { silent: true });
          }

          notify((res && res.message) || `Perintah ${actionLabel} gagal diproses`, "error");
        })
        .fail(() => {
          notify("Gagal terhubung ke server device", "error");
        })
        .always(() => {
          const currentLockUntil = Number($button.data("lock-until") || 0);
          if (!currentLockUntil || currentLockUntil <= Math.floor(Date.now() / 1000)) {
            $button.prop("disabled", false);
          }
        });
    };

    if (window.Swal) {
      Swal.fire({
        icon: dangerActions.has(action) ? "warning" : "question",
        title: `Kirim perintah ${actionLabel}?`,
        text: "Pastikan perangkat tujuan benar sebelum melanjutkan.",
        showCancelButton: true,
        confirmButtonText: "Kirim",
        cancelButtonText: "Batal",
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) {
          submitAction();
        }
      });
      return;
    }

    if (window.confirm(`Kirim perintah ${actionLabel}?`)) {
      submitAction();
    }
  });

  $(document).on("click", ".copy-token-btn", function () {
    const token = String($(this).data("token") || "").trim();

    copyText(token)
      .then(() => {
        notify("Token device berhasil disalin", "success");
      })
      .catch(() => {
        notify("Gagal menyalin token device", "error");
      });
  });

  const initialLockUntil = Math.max(
    0,
    ...$(".remote-btn")
      .map(function () {
        return Number($(this).data("lock-until") || 0);
      })
      .get()
  );

  if (initialLockUntil > 0) {
    applyRemoteLock(initialLockUntil, { silent: true });
  }
})();
