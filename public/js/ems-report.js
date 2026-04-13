"use strict";

document.addEventListener("alpine:init", () => {
  const toast = (message, type = "info") => {
    if (typeof window.safeToast === "function") {
      window.safeToast(message, type);
      return;
    }
    console.log(`[${type}] ${message}`);
  };

  Alpine.data("emsReportIndex", (boot = {}) => ({
    query: "",
    reports: Array.isArray(boot.reports) ? boot.reports : [],
    get filteredReports() {
      const keyword = String(this.query || "").trim().toLowerCase();
      if (!keyword) {
        return this.reports;
      }
      return this.reports.filter((report) => {
        const haystack = `${report.title || ""} ${report.subtitle || ""}`.toLowerCase();
        return haystack.includes(keyword);
      });
    },
  }));

  Alpine.data("emsWaterConsumptionPage", (boot = {}) => ({
    saveUrl: String(boot.saveUrl || ""),
    csrfName: String(boot.csrfName || ""),
    csrfHash: String(boot.csrfHash || ""),
    years: [],
    selectedYear: null,
    baselineYear: null,
    monthItems: [],
    editorYears: {},
    yearMeta: {},
    monthlySummary: [],
    summaryRows: [],
    editor: { productionOutput: "", months: {} },
    saveTimer: null,
    saveState: "idle",
    saveMessage: "Siap diedit",

    init() {
      this.applyDataset(boot.dataset || {});
      this.setEditorFromYear(this.selectedYear);
    },

    applyDataset(dataset) {
      this.years = Array.isArray(dataset.years) ? dataset.years.map((year) => Number(year)) : [];
      this.selectedYear = Number(dataset.selectedYear || this.years[0] || new Date().getFullYear());
      this.baselineYear = Number(dataset.baselineYear || this.selectedYear);
      this.monthItems = Object.entries(dataset.months || {}).map(([number, labels]) => ({
        number: Number(number),
        short: labels.short,
        long: labels.long,
      }));
      this.editorYears = dataset.editorYears || {};
      this.yearMeta = dataset.yearMeta || {};
      this.monthlySummary = Array.isArray(dataset.monthlySummary) ? dataset.monthlySummary : [];
      this.summaryRows = Array.isArray(dataset.summaryRows) ? dataset.summaryRows : [];
    },

    setEditorFromYear(year) {
      const data = this.editorYears?.[year] || { productionOutput: null, months: {} };
      const months = {};
      this.monthItems.forEach((month) => {
        const value = data.months?.[month.number] ?? data.months?.[String(month.number)] ?? "";
        months[month.number] = value === null ? "" : String(value);
      });
      this.editor = {
        productionOutput: data.productionOutput === null || data.productionOutput === undefined ? "" : String(data.productionOutput),
        months,
      };
    },

    selectYear(year) {
      this.selectedYear = Number(year);
      this.setEditorFromYear(this.selectedYear);
      this.saveState = "idle";
      this.saveMessage = "Siap diedit";
      const url = new URL(window.location.href);
      url.searchParams.set("year", String(this.selectedYear));
      window.history.replaceState({}, "", url.toString());
    },

    scheduleAutosave() {
      this.saveState = "dirty";
      this.saveMessage = "Perubahan belum tersimpan";
      if (this.saveTimer) {
        window.clearTimeout(this.saveTimer);
      }
      this.saveTimer = window.setTimeout(() => {
        this.saveNow();
      }, 700);
    },

    async saveNow() {
      if (!this.saveUrl) {
        return;
      }

      this.saveState = "saving";
      this.saveMessage = "Menyimpan otomatis...";

      const params = new URLSearchParams();
      params.append("report_year", String(this.selectedYear));
      params.append("production_output", this.editor.productionOutput ?? "");
      this.monthItems.forEach((month) => {
        params.append(`months[${month.number}]`, this.editor.months?.[month.number] ?? "");
      });
      if (this.csrfName && this.csrfHash) {
        params.append(this.csrfName, this.csrfHash);
      }

      try {
        const response = await fetch(this.saveUrl, {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "Accept": "application/json",
          },
          credentials: "same-origin",
          body: params.toString(),
        });

        const contentType = String(response.headers.get("content-type") || "").toLowerCase();
        const raw = await response.text();
        let payload = null;

        if (contentType.includes("application/json")) {
          payload = JSON.parse(raw);
        } else {
          throw new Error("Server membalas format yang tidak valid.");
        }

        if (!response.ok || !payload.ok) {
          throw new Error(payload.message || "save_failed");
        }

        this.csrfHash = payload.csrfHash || this.csrfHash;
        this.applyDataset(payload.dataset || {});
        this.setEditorFromYear(this.selectedYear);
        if (typeof payload.summaryHtml === "string" && this.$refs.summaryPanels) {
          this.$refs.summaryPanels.innerHTML = payload.summaryHtml;
        }
        this.saveState = "saved";
        this.saveMessage = payload.message || "Tersimpan";
      } catch (error) {
        this.saveState = "error";
        this.saveMessage = error.message || "Gagal menyimpan";
        toast(this.saveMessage, "error");
      }
    },

    formatNumber(value) {
      if (value === null || value === undefined || value === "") {
        return "-";
      }
      const number = Number(value);
      if (!Number.isFinite(number)) {
        return "-";
      }
      return new Intl.NumberFormat("id-ID", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(number);
    },

    formatPercent(value) {
      if (value === null || value === undefined || value === "") {
        return "-";
      }
      const number = Number(value);
      if (!Number.isFinite(number)) {
        return "-";
      }
      return `${new Intl.NumberFormat("id-ID", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(number)}%`;
    },

    formatIntensity(value) {
      if (value === null || value === undefined || value === "") {
        return "-";
      }
      const number = Number(value);
      if (!Number.isFinite(number)) {
        return "-";
      }
      return new Intl.NumberFormat("id-ID", {
        minimumFractionDigits: 5,
        maximumFractionDigits: 5,
      }).format(number);
    },

    totalChangeFor(year, previousYear) {
      const currentTotal = Number(this.yearMeta?.[year]?.total ?? 0);
      const previousTotal = Number(this.yearMeta?.[previousYear]?.total ?? 0);
      if (!previousYear || !Number.isFinite(previousTotal) || previousTotal <= 0) {
        return null;
      }
      return ((currentTotal - previousTotal) / previousTotal) * 100;
    },

    statusBadge(status) {
      if (status === "Baseline") {
        return "text-bg-primary";
      }
      if (status === "Decrease") {
        return "text-bg-success";
      }
      if (status === "Increase") {
        return "text-bg-danger";
      }
      if (status === "Stable") {
        return "text-bg-info";
      }
      return "text-bg-secondary";
    },

    get saveStateClass() {
      return {
        "is-idle": this.saveState === "idle",
        "is-dirty": this.saveState === "dirty",
        "is-saving": this.saveState === "saving",
        "is-saved": this.saveState === "saved",
        "is-error": this.saveState === "error",
      };
    },

    get saveStateIcon() {
      if (this.saveState === "saving") return "bi-arrow-repeat spin";
      if (this.saveState === "saved") return "bi-check-circle";
      if (this.saveState === "error") return "bi-exclamation-octagon";
      if (this.saveState === "dirty") return "bi-pencil-square";
      return "bi-info-circle";
    },

    get saveStateLabel() {
      return this.saveMessage;
    },
  }));

  Alpine.data("emsElectricConsumptionPage", (boot = {}) => ({
    saveUrl: String(boot.saveUrl || ""),
    csrfName: String(boot.csrfName || ""),
    csrfHash: String(boot.csrfHash || ""),
    years: [],
    selectedYear: null,
    monthItems: [],
    editorYears: {},
    yearMeta: {},
    monthlySummary: [],
    emissionFactor: 0.87,
    editor: { productionOutput: "", months: {} },
    saveTimer: null,
    saveState: "idle",
    saveMessage: "Siap diedit",

    init() {
      this.applyDataset(boot.dataset || {});
      this.setEditorFromYear(this.selectedYear);
    },

    applyDataset(dataset) {
      this.years = Array.isArray(dataset.years) ? dataset.years.map((year) => Number(year)) : [];
      this.selectedYear = Number(dataset.selectedYear || this.years[0] || new Date().getFullYear());
      this.monthItems = Object.entries(dataset.months || {}).map(([number, labels]) => ({
        number: Number(number),
        short: labels.short,
        long: labels.long,
      }));
      this.editorYears = dataset.editorYears || {};
      this.yearMeta = dataset.yearMeta || {};
      this.monthlySummary = Array.isArray(dataset.monthlySummary) ? dataset.monthlySummary : [];
      this.emissionFactor = Number(dataset.emissionFactor || 0.87);
    },

    setEditorFromYear(year) {
      const data = this.editorYears?.[year] || { productionOutput: null, months: {} };
      const months = {};
      this.monthItems.forEach((month) => {
        const value = data.months?.[month.number] ?? data.months?.[String(month.number)] ?? "";
        months[month.number] = value === null ? "" : String(value);
      });
      this.editor = {
        productionOutput: data.productionOutput === null || data.productionOutput === undefined ? "" : String(data.productionOutput),
        months,
      };
    },

    selectYear(year) {
      this.selectedYear = Number(year);
      this.setEditorFromYear(this.selectedYear);
      this.saveState = "idle";
      this.saveMessage = "Siap diedit";
      const url = new URL(window.location.href);
      url.searchParams.set("year", String(this.selectedYear));
      window.history.replaceState({}, "", url.toString());
    },

    scheduleAutosave() {
      this.saveState = "dirty";
      this.saveMessage = "Perubahan belum tersimpan";
      if (this.saveTimer) {
        window.clearTimeout(this.saveTimer);
      }
      this.saveTimer = window.setTimeout(() => this.saveNow(), 700);
    },

    async saveNow() {
      if (!this.saveUrl) {
        return;
      }

      this.saveState = "saving";
      this.saveMessage = "Menyimpan otomatis...";

      const params = new URLSearchParams();
      params.append("report_year", String(this.selectedYear));
      params.append("production_output", this.editor.productionOutput ?? "");
      this.monthItems.forEach((month) => {
        params.append(`months[${month.number}]`, this.editor.months?.[month.number] ?? "");
      });
      if (this.csrfName && this.csrfHash) {
        params.append(this.csrfName, this.csrfHash);
      }

      try {
        const response = await fetch(this.saveUrl, {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "Accept": "application/json",
          },
          credentials: "same-origin",
          body: params.toString(),
        });

        const contentType = String(response.headers.get("content-type") || "").toLowerCase();
        const raw = await response.text();
        let payload = null;

        if (contentType.includes("application/json")) {
          payload = JSON.parse(raw);
        } else {
          throw new Error("Server membalas format yang tidak valid.");
        }

        if (!response.ok || !payload.ok) {
          throw new Error(payload.message || "save_failed");
        }

        this.csrfHash = payload.csrfHash || this.csrfHash;
        this.applyDataset(payload.dataset || {});
        this.setEditorFromYear(this.selectedYear);
        if (typeof payload.summaryHtml === "string" && this.$refs.summaryPanels) {
          this.$refs.summaryPanels.innerHTML = payload.summaryHtml;
        }
        this.saveState = "saved";
        this.saveMessage = payload.message || "Tersimpan";
      } catch (error) {
        this.saveState = "error";
        this.saveMessage = error.message || "Gagal menyimpan";
        toast(this.saveMessage, "error");
      }
    },

    get saveStateClass() {
      return {
        "is-idle": this.saveState === "idle",
        "is-dirty": this.saveState === "dirty",
        "is-saving": this.saveState === "saving",
        "is-saved": this.saveState === "saved",
        "is-error": this.saveState === "error",
      };
    },

    get saveStateIcon() {
      if (this.saveState === "saving") return "bi-arrow-repeat spin";
      if (this.saveState === "saved") return "bi-check-circle";
      if (this.saveState === "error") return "bi-exclamation-octagon";
      if (this.saveState === "dirty") return "bi-pencil-square";
      return "bi-info-circle";
    },

    get saveStateLabel() {
      return this.saveMessage;
    },
  }));
});
