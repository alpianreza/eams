"use strict";

(function () {
  const actionLabelMap = {
    shutdown: "Shutdown OS",
    restart: "Restart OS",
    update: "Push Update",
    sync: "Sync Sekarang",
    restart_agent: "Restart Agent",
    lock: "Lock Screen",
    logoff: "Log Off User",
    popup_message: "Kirim Pesan",
  };

  const dangerActions = new Set(["shutdown", "restart", "logoff"]);
  const remoteSelector = ".remote-btn, .remote-message-btn";
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

  function allRemoteButtons() {
    return $(remoteSelector);
  }

  function applyRemoteLock(lockUntil, options = {}) {
    const now = Math.floor(Date.now() / 1000);
    const parsedLockUntil = Number(lockUntil || 0);
    const remaining = Math.max(0, parsedLockUntil - now);

    if (!Number.isFinite(parsedLockUntil) || parsedLockUntil <= 0 || remaining <= 0) {
      allRemoteButtons().prop("disabled", false).attr("title", "").data("lock-until", 0);
      if (lockTimer) {
        window.clearInterval(lockTimer);
        lockTimer = null;
      }
      return;
    }

    allRemoteButtons().each(function () {
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
        allRemoteButtons().prop("disabled", false).attr("title", "").data("lock-until", 0);
        window.clearInterval(lockTimer);
        lockTimer = null;
        return;
      }

      allRemoteButtons().attr("title", `Remote lock aktif (${left} detik)`);
    }, 1000);
  }

  function currentLockUntil($button) {
    return Number($button.data("lock-until") || 0);
  }

  function syncLockState() {
    const latestLockUntil = Math.max(
      0,
      ...allRemoteButtons()
        .map(function () {
          return Number($(this).data("lock-until") || 0);
        })
        .get()
    );

    if (latestLockUntil > 0) {
      applyRemoteLock(latestLockUntil, { silent: true });
      return;
    }

    applyRemoteLock(0, { silent: true });
  }

  function ensureUnlocked($button) {
    const lockUntil = currentLockUntil($button);
    const now = Math.floor(Date.now() / 1000);

    if (lockUntil > now) {
      const waitSeconds = lockUntil - now;
      notify(`Aksi masih terkunci. Coba lagi ${waitSeconds} detik lagi.`, "warning");
      return false;
    }

    return true;
  }

  function submitRemoteAction($button, action, extraData = {}) {
    const id = Number($button.data("id"));
    if (!id || !action) {
      return;
    }

    const actionLabel = actionLabelMap[action] || action;
    const payload = { id, action, ...extraData };

    $button.prop("disabled", true);

    $.post("/it/device/remote", payload)
      .done((res) => {
        if (res && res.ok) {
          notify(res.message || `Perintah ${actionLabel} berhasil dikirim`, "success");
          if (res.lock_until) {
            applyRemoteLock(res.lock_until, { silent: true });
          }
          window.dispatchEvent(
            new CustomEvent("device-remote:updated", {
              detail: {
                action,
                response: res,
              },
            })
          );
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
        const lockUntil = currentLockUntil($button);
        if (!lockUntil || lockUntil <= Math.floor(Date.now() / 1000)) {
          $button.prop("disabled", false);
        }
      });
  }

  function confirmRemoteAction($button, action) {
    const actionLabel = actionLabelMap[action] || action;
    const run = () => submitRemoteAction($button, action);

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
          run();
        }
      });
      return;
    }

    if (window.confirm(`Kirim perintah ${actionLabel}?`)) {
      run();
    }
  }

  function promptRemoteMessage($button) {
    if (!window.Swal) {
      const fallback = window.prompt("Isi pesan untuk pengguna:");
      if (!fallback) {
        return;
      }
      submitRemoteAction($button, "popup_message", {
        title: "Pesan dari Tim IT",
        message: fallback,
        timeout: 90,
      });
      return;
    }

    Swal.fire({
      title: "Kirim Pesan ke Pengguna",
      html: `
        <input id="remoteMessageTitle" class="swal2-input" placeholder="Judul pesan" value="Pesan dari Tim IT">
        <textarea id="remoteMessageBody" class="swal2-textarea" placeholder="Tulis pesan yang ingin tampil di PC client"></textarea>
        <select id="remoteMessageTimeout" class="swal2-select">
          <option value="60">Tampil 60 detik</option>
          <option value="90" selected>Tampil 90 detik</option>
          <option value="120">Tampil 120 detik</option>
        </select>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: "Kirim Pesan",
      cancelButtonText: "Batal",
      preConfirm: () => {
        const title = document.getElementById("remoteMessageTitle")?.value?.trim() || "Pesan dari Tim IT";
        const message = document.getElementById("remoteMessageBody")?.value?.trim() || "";
        const timeout = document.getElementById("remoteMessageTimeout")?.value || "90";

        if (!message) {
          Swal.showValidationMessage("Isi pesan tidak boleh kosong.");
          return false;
        }

        return { title, message, timeout };
      },
    }).then((result) => {
      if (!result.isConfirmed || !result.value) {
        return;
      }

      submitRemoteAction($button, "popup_message", result.value);
    });
  }

  $(document).on("click", ".remote-btn", function () {
    const $button = $(this);
    const action = String($button.data("action") || "").trim().toLowerCase();

    if (!ensureUnlocked($button)) {
      return;
    }

    confirmRemoteAction($button, action);
  });

  $(document).on("click", ".remote-message-btn", function () {
    const $button = $(this);

    if (!ensureUnlocked($button)) {
      return;
    }

    promptRemoteMessage($button);
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

  window.DeviceRemote = window.DeviceRemote || {};
  window.DeviceRemote.syncLockState = syncLockState;
  syncLockState();
})();
