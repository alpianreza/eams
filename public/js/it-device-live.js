"use strict";

document.addEventListener("alpine:init", () => {
  const formatRelativeTime = (timestamp, now) => {
    if (window.ITSuiteUtils && typeof window.ITSuiteUtils.formatRelativeTime === "function") {
      return window.ITSuiteUtils.formatRelativeTime(timestamp, now);
    }

    const ts = Number(timestamp || 0);
    if (!Number.isFinite(ts) || ts <= 0) {
      return "Belum ada data";
    }

    const delta = Math.max(0, now - ts);
    if (delta < 10) {
      return "Baru saja";
    }
    if (delta < 60) {
      return `${delta} detik lalu`;
    }

    const minutes = Math.floor(delta / 60);
    if (minutes < 60) {
      return `${minutes} menit lalu`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
      return `${hours} jam lalu`;
    }

    return `${Math.floor(hours / 24)} hari lalu`;
  };

  const formatCountdown = (seconds) => {
    if (window.ITSuiteUtils && typeof window.ITSuiteUtils.formatCountdown === "function") {
      return window.ITSuiteUtils.formatCountdown(seconds);
    }

    const value = Math.max(0, Number(seconds || 0));
    if (value < 60) {
      return `${value} detik`;
    }

    const minutes = Math.floor(value / 60);
    const secs = value % 60;
    return secs > 0 ? `${minutes} menit ${secs} detik` : `${minutes} menit`;
  };

  Alpine.data("itDeviceIndex", (config = {}) => ({
    q: "",
    perPage: Number(config.initialPerPage || 20),
    page: "",
    tableHtml: "",
    loading: false,
    lastLoadedAt: null,
    stats: {},
    refreshTimer: null,
    searchTimer: null,
    paginationClickHandler: null,
    cardMeta:
      window.IT_DEVICE_INDEX_BOOT &&
      Array.isArray(window.IT_DEVICE_INDEX_BOOT.cards)
        ? window.IT_DEVICE_INDEX_BOOT.cards
        : [],

    init() {
      const currentUrl = new URL(window.location.href);
      this.page = currentUrl.searchParams.get("page") || "";
      this.q = currentUrl.searchParams.get("q") || "";
      this.perPage = Number(currentUrl.searchParams.get("perPage") || this.perPage || 20);

      this.loadStats(true);
      this.loadTable(true, false);
      this.refreshTimer = window.setInterval(() => {
        this.loadStats(true);
        this.loadTable(true, false);
      }, 15000);

      this.paginationClickHandler = (event) => {
        const link = event.target.closest("#deviceAjax .pagination a");
        if (!link) {
          return;
        }

        const href = link.getAttribute("href");
        if (!href) {
          return;
        }

        event.preventDefault();
        const url = new URL(href, window.location.origin);
        this.page = url.searchParams.get("page") || "";
        this.loadTable();
      };
      document.addEventListener("click", this.paginationClickHandler);

      this._detailRefreshListener = () => {
        this.loadStats(true);
        this.loadTable(true, false);
      };
      window.addEventListener("device-remote:updated", this._detailRefreshListener);

      this.$el.addEventListener("alpine:destroy", () => {
        if (this.refreshTimer) {
          window.clearInterval(this.refreshTimer);
        }
        if (this.searchTimer) {
          window.clearTimeout(this.searchTimer);
        }
        if (this.paginationClickHandler) {
          document.removeEventListener("click", this.paginationClickHandler);
        }
        if (this._detailRefreshListener) {
          window.removeEventListener("device-remote:updated", this._detailRefreshListener);
        }
      });
    },

    get cards() {
      return this.cardMeta.map((card) => ({
        ...card,
        value: Number(this.stats[card.key] ?? card.value ?? 0),
      }));
    },

    syncUrl() {
      const url = new URL("/it/devices", window.location.origin);
      url.searchParams.set("perPage", String(this.perPage || 20));

      if (this.q) {
        url.searchParams.set("q", this.q);
      }
      if (this.page) {
        url.searchParams.set("page", this.page);
      }

      window.history.replaceState({}, "", url.toString());
    },

    buildTableUrl(rawUrl = "") {
      const url = new URL(rawUrl || config.tableUrl || "/it/devices/ajax", window.location.origin);
      url.searchParams.set("perPage", String(this.perPage || 20));
      if (this.q) {
        url.searchParams.set("q", this.q);
      } else {
        url.searchParams.delete("q");
      }

      if (this.page) {
        url.searchParams.set("page", this.page);
      } else {
        url.searchParams.delete("page");
      }

      return url.toString();
    },

    async loadTable(silent = false, syncHistory = true) {
      if (!silent) {
        this.loading = true;
      }

      const loadingNode = document.getElementById("deviceLoadingState");
      if (loadingNode) {
        loadingNode.hidden = !!silent;
      }

      try {
        const response = await fetch(this.buildTableUrl(), {
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        if (!response.ok) {
          throw new Error("table_fetch_failed");
        }

        this.tableHtml = await response.text();
        this.lastLoadedAt = Date.now();
        if (syncHistory) {
          this.syncUrl();
        }
      } catch (error) {
        this.tableHtml = '<div class="alert alert-danger mb-0">Gagal memuat data device. Silakan coba lagi.</div>';
      } finally {
        this.loading = false;
        if (loadingNode) {
          loadingNode.hidden = true;
        }
      }
    },

    async loadStats(silent = false) {
      try {
        const response = await fetch(config.statsUrl || "/it/devices/stats", {
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        if (!response.ok) {
          throw new Error("stats_fetch_failed");
        }

        const payload = await response.json();
        if (payload && payload.ok && payload.kpi) {
          this.stats = payload.kpi;
          if (!silent) {
            this.lastLoadedAt = Date.now();
          }
        }
      } catch (error) {
        // Biarkan tampilan terakhir tetap aktif.
      }
    },

    handleSearchInput() {
      if (this.searchTimer) {
        window.clearTimeout(this.searchTimer);
      }

      this.searchTimer = window.setTimeout(() => {
        this.page = "";
        this.loadTable();
      }, 320);
    },

    changePerPage() {
      this.page = "";
      this.loadTable();
    },
  }));

  Alpine.data("itDeviceDetail", (config = {}) => ({
    loading: false,
    refreshTimer: null,
    clockTimer: null,
    visibilityHandler: null,
    remoteRefreshHandler: null,
    refreshUrl: config.refreshUrl || "",
    liveState: {
      lastSeenTs: 0,
      syncAtTs: 0,
      remoteLockUntil: 0,
      remoteActionLabel: "",
    },

    init() {
      this.hydrateLiveState();
      this.renderLiveState();

      this.clockTimer = window.setInterval(() => {
        this.renderLiveState();
      }, 1000);

      this.refreshTimer = window.setInterval(() => {
        this.refresh(true);
      }, 12000);

      this.remoteRefreshHandler = () => {
        this.refresh();
      };
      window.addEventListener("device-remote:updated", this.remoteRefreshHandler);

      this.visibilityHandler = () => {
        if (document.visibilityState === "visible") {
          this.refresh(true);
        }
      };
      document.addEventListener("visibilitychange", this.visibilityHandler);

      this.$el.addEventListener("alpine:destroy", () => {
        if (this.refreshTimer) {
          window.clearInterval(this.refreshTimer);
        }
        if (this.clockTimer) {
          window.clearInterval(this.clockTimer);
        }
        if (this.remoteRefreshHandler) {
          window.removeEventListener("device-remote:updated", this.remoteRefreshHandler);
        }
        if (this.visibilityHandler) {
          document.removeEventListener("visibilitychange", this.visibilityHandler);
        }
      });
    },

    hydrateLiveState() {
      const stateNode = this.$refs.content
        ? this.$refs.content.querySelector("[data-it-device-state]")
        : null;

      if (!stateNode) {
        return;
      }

      try {
        const parsed = JSON.parse(stateNode.getAttribute("data-it-device-state") || "{}");
        this.liveState = {
          lastSeenTs: Number(parsed.lastSeenTs || 0),
          syncAtTs: Number(parsed.syncAtTs || 0),
          remoteLockUntil: Number(parsed.remoteLockUntil || 0),
          remoteActionLabel: String(parsed.remoteActionLabel || "AKSI"),
        };
      } catch (error) {
        this.liveState = {
          lastSeenTs: 0,
          syncAtTs: 0,
          remoteLockUntil: 0,
          remoteActionLabel: "AKSI",
        };
      }
    },

    renderLiveState() {
      if (!this.$refs.content) {
        return;
      }

      const now = Math.floor(Date.now() / 1000);
      const lastSeenNode = this.$refs.content.querySelector("[data-live-last-seen-relative]");
      if (lastSeenNode) {
        lastSeenNode.textContent = formatRelativeTime(this.liveState.lastSeenTs, now);
      }

      const syncNode = this.$refs.content.querySelector("[data-live-sync-relative]");
      if (syncNode) {
        syncNode.textContent = formatRelativeTime(this.liveState.syncAtTs, now);
      }

      const remaining = Math.max(0, Number(this.liveState.remoteLockUntil || 0) - now);
      const alertNode = this.$refs.content.querySelector("[data-remote-lock-alert]");
      const badgeNode = this.$refs.content.querySelector("[data-remote-lock-badge]");

      if (remaining <= 0) {
        if (alertNode) {
          alertNode.remove();
        }
        if (badgeNode) {
          badgeNode.remove();
        }
        return;
      }

      [alertNode, badgeNode].forEach((node) => {
        if (!node) {
          return;
        }
        const valueNode = node.querySelector("[data-remote-lock-value]");
        if (valueNode) {
          valueNode.textContent = formatCountdown(remaining);
        }
      });
    },

    async refresh(silent = false) {
      if (!this.refreshUrl) {
        return;
      }

      if (!silent) {
        this.loading = true;
      }

      try {
        const response = await fetch(this.refreshUrl, {
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        if (!response.ok) {
          throw new Error("detail_fetch_failed");
        }

        const html = await response.text();
        if (this.$refs.content) {
          this.$refs.content.innerHTML = html;
        }

        this.hydrateLiveState();
        this.renderLiveState();

        if (window.DeviceRemote && typeof window.DeviceRemote.syncLockState === "function") {
          window.DeviceRemote.syncLockState();
        }
      } catch (error) {
        if (!silent && typeof window.safeToast === "function") {
          window.safeToast("Gagal menyegarkan detail device.", "error");
        }
      } finally {
        this.loading = false;
      }
    },
  }));
});