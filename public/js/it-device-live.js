"use strict";

document.addEventListener("alpine:init", () => {
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
      this.loadTable(true);
      this.refreshTimer = window.setInterval(() => {
        this.loadStats(true);
        this.loadTable(true);
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
      });

      window.addEventListener("device-remote:updated", () => {
        this.loadStats(true);
        this.loadTable(true);
      });
    },

    get cards() {
      return this.cardMeta.map((card) => ({
        ...card,
        value: Number(this.stats[card.key] ?? card.value ?? 0),
      }));
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

    async loadTable(silent = false) {
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
      } catch (error) {
        this.tableHtml =
          '<div class="alert alert-danger mb-0">Gagal memuat data device. Silakan coba lagi.</div>';
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
    refreshUrl: config.refreshUrl || "",

    init() {
      this.refreshTimer = window.setInterval(() => {
        this.refresh(true);
      }, 12000);

      window.addEventListener("device-remote:updated", () => {
        this.refresh();
      });

      document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "visible") {
          this.refresh(true);
        }
      });

      this.$el.addEventListener("alpine:destroy", () => {
        if (this.refreshTimer) {
          window.clearInterval(this.refreshTimer);
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
