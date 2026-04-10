"use strict";

document.addEventListener("alpine:init", () => {
  const notify = (message, type = "info") => {
    if (typeof window.safeToast === "function") {
      window.safeToast(message, type);
      return;
    }
    window.alert(message);
  };

  const copyText = async (text) => {
    if (!text) {
      throw new Error("empty");
    }

    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return;
    }

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

    if (!ok) {
      throw new Error("copy_failed");
    }
  };

  const formatRelativeTime = (timestamp, now = Math.floor(Date.now() / 1000)) => {
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

    const days = Math.floor(hours / 24);
    return `${days} hari lalu`;
  };

  const formatCountdown = (seconds) => {
    const value = Math.max(0, Number(seconds || 0));
    if (value < 60) {
      return `${value} detik`;
    }

    const hours = Math.floor(value / 3600);
    const minutes = Math.floor((value % 3600) / 60);
    const secs = value % 60;
    const parts = [];

    if (hours > 0) {
      parts.push(`${hours} jam`);
    }
    if (minutes > 0) {
      parts.push(`${minutes} menit`);
    }
    if (secs > 0 && hours === 0) {
      parts.push(`${secs} detik`);
    }

    return parts.join(" ") || "0 detik";
  };

  window.ITSuiteUtils = {
    notify,
    copyText,
    formatRelativeTime,
    formatCountdown,
  };

  Alpine.data("itWorkspaceHome", (cards = []) => ({
    query: "",
    cards: Array.isArray(cards) ? cards : [],
    get filteredCards() {
      const keyword = String(this.query || "").trim().toLowerCase();
      if (!keyword) {
        return this.cards;
      }

      return this.cards.filter((card) => {
        const haystack = `${card.kicker || ""} ${card.title || ""} ${card.body || ""}`.toLowerCase();
        return haystack.includes(keyword);
      });
    },
  }));

  Alpine.data("itAssetIndex", (config = {}) => ({
    q: String(config.initialQuery || ""),
    activeType: String(config.initialType || ""),
    perPage: Number(config.initialPerPage || 20),
    page: "",
    tableHtml: String(config.initialTableHtml || ""),
    filterItems: Array.isArray(config.filterItems) ? config.filterItems : [],
    loading: false,
    searchTimer: null,

    init() {
      const currentUrl = new URL(window.location.href);
      this.q = currentUrl.searchParams.get("q") ?? this.q;
      this.activeType = currentUrl.searchParams.get("type") ?? this.activeType;
      this.perPage = Number(currentUrl.searchParams.get("perPage") || this.perPage || 20);
      this.page = currentUrl.searchParams.get("page") || "";

      window.addEventListener("popstate", () => {
        const url = new URL(window.location.href);
        this.q = url.searchParams.get("q") || "";
        this.activeType = url.searchParams.get("type") || "";
        this.perPage = Number(url.searchParams.get("perPage") || this.perPage || 20);
        this.page = url.searchParams.get("page") || "";
        this.loadTable(true, false);
      });

      if (!this.tableHtml) {
        this.loadTable(true);
      }
    },

    buildTableUrl(rawUrl = "") {
      const url = new URL(rawUrl || config.tableUrl || "/it-assets/ajax", window.location.origin);
      url.searchParams.set("perPage", String(this.perPage || 20));

      if (this.q) {
        url.searchParams.set("q", this.q);
      } else {
        url.searchParams.delete("q");
      }

      if (this.activeType) {
        url.searchParams.set("type", this.activeType);
      } else {
        url.searchParams.delete("type");
      }

      if (this.page) {
        url.searchParams.set("page", this.page);
      } else {
        url.searchParams.delete("page");
      }

      return url.toString();
    },

    syncUrl() {
      const url = new URL("/it-assets", window.location.origin);
      url.searchParams.set("perPage", String(this.perPage || 20));

      if (this.q) {
        url.searchParams.set("q", this.q);
      }
      if (this.activeType) {
        url.searchParams.set("type", this.activeType);
      }
      if (this.page) {
        url.searchParams.set("page", this.page);
      }

      window.history.replaceState({}, "", url.toString());
    },

    async loadTable(silent = false, syncHistory = true) {
      if (!silent) {
        this.loading = true;
      }

      try {
        const response = await fetch(this.buildTableUrl(), {
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        if (!response.ok) {
          throw new Error("asset_fetch_failed");
        }

        this.tableHtml = await response.text();
        if (syncHistory) {
          this.syncUrl();
        }
      } catch (error) {
        this.tableHtml = '<div class="alert alert-danger mb-0">Gagal memuat data asset. Silakan coba lagi.</div>';
      } finally {
        this.loading = false;
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

    setType(type) {
      this.activeType = String(type || "");
      this.page = "";
      this.loadTable();
    },

    resetFilters() {
      this.q = "";
      this.activeType = "";
      this.page = "";
      this.perPage = 20;
      this.loadTable();
    },

    handleContainerClick(event) {
      const link = event.target.closest("#itAssetAjax .pagination a");
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
    },
  }));

  Alpine.data("itAssetDetail", (config = {}) => ({
    activeSection: "overview",
    isMobile: false,
    mediaQuery: null,
    inventoryNo: String(config.inventoryNo || ""),

    init() {
      this.mediaQuery = window.matchMedia("(max-width: 991.98px)");
      this.updateViewportState();

      const handler = () => this.updateViewportState();
      if (typeof this.mediaQuery.addEventListener === "function") {
        this.mediaQuery.addEventListener("change", handler);
      } else if (typeof this.mediaQuery.addListener === "function") {
        this.mediaQuery.addListener(handler);
      }
    },

    updateViewportState() {
      this.isMobile = !!(this.mediaQuery && this.mediaQuery.matches);
      if (!this.isMobile) {
        this.activeSection = "overview";
      }
    },

    sectionVisible(section) {
      return !this.isMobile || this.activeSection === section;
    },

    async copyInventory() {
      try {
        await copyText(this.inventoryNo);
        notify("Nomor inventaris berhasil disalin.", "success");
      } catch (error) {
        notify("Gagal menyalin nomor inventaris.", "error");
      }
    },
  }));
});