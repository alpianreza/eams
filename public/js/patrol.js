"use strict";

document.addEventListener("alpine:init", () => {
  const notify = (message, type = "info") => {
    if (typeof window.safeToast === "function") {
      window.safeToast(message, type);
      return;
    }
    window.alert(message);
  };

  const relUrl = (raw) => {
    const url = new URL(raw, window.location.origin);
    return url.pathname + url.search;
  };

  const formatDateTime = (date = new Date()) => {
    const pad = (value) => String(value).padStart(2, "0");
    return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
  };

  Alpine.data("patrolSecurityPage", (boot = {}) => ({
    today: String(boot.today || ""),
    user: boot.user || {},
    routes: Array.isArray(boot.routes) ? boot.routes : [],
    checkpoints: Array.isArray(boot.checkpoints) ? boot.checkpoints : [],
    layout: boot.layout || { id: 0, name: "Layout Utama", image_url: "" },
    activeSession: boot.activeSession || null,
    csrfName: String(boot.csrfName || ""),
    csrfHash: String(boot.csrfHash || ""),

    selectedRouteId: boot.activeSession?.session?.route_id || (Array.isArray(boot.routes) && boot.routes[0] ? boot.routes[0].id : ""),
    barcode: "",
    note: "",
    status: "ok",
    photoFiles: [],
    photoPreviews: [],
    busy: false,
    errorMessage: "",
    successMessage: "",
    gps: {
      latitude: null,
      longitude: null,
      accuracy: null,
    },

    init() {
      if (this.activeSession?.session?.route_id) {
        this.selectedRouteId = this.activeSession.session.route_id;
      } else if (!this.selectedRouteId && this.routes.length) {
        this.selectedRouteId = this.routes[0].id;
      }

      if (this.activeSession?.nextCheckpoint) {
        this.barcode = this.activeSession.nextCheckpoint.barcode_value || "";
      }
    },

    canViewDashboard() {
      return ["admin", "compliance"].includes(String(this.user.role || "").toLowerCase());
    },

    routeById(routeId) {
      const targetId = Number(routeId || 0);
      return this.routes.find((route) => Number(route.id || 0) === targetId) || null;
    },

    sessionRunning() {
      return this.activeSession && String(this.activeSession.session?.status || "").toLowerCase() === "active";
    },

    routeLocked() {
      return this.sessionRunning();
    },

    activeRouteCheckpoints() {
      if (this.sessionRunning() && this.activeSession?.checkpoints?.length) {
        return this.activeSession.checkpoints;
      }

      return this.routeById(this.selectedRouteId)?.checkpoints || [];
    },

    selectedRoute() {
      return this.routeById(this.selectedRouteId);
    },

    nextCheckpoint() {
      if (this.sessionRunning() && this.activeSession?.nextCheckpoint) {
        return this.activeSession.nextCheckpoint;
      }

      return this.activeRouteCheckpoints().find((checkpoint) => !checkpoint.checked) || null;
    },

    progressValue() {
      return this.activeSession?.progress?.percent || 0;
    },

    progressText() {
      const checked = this.activeSession?.progress?.checked || 0;
      const total = this.activeSession?.progress?.total || this.activeRouteCheckpoints().length || 0;
      return `${checked}/${total}`;
    },

    markerClass(checkpoint) {
      const next = this.nextCheckpoint();
      if (checkpoint.checked) {
        return "is-done";
      }
      if (next && Number(next.id || 0) === Number(checkpoint.id || 0)) {
        return "is-next";
      }
      return "is-pending";
    },

    markerStyle(checkpoint) {
      const x = Number(checkpoint.map_x || 0);
      const y = Number(checkpoint.map_y || 0);
      return `left:${x}%;top:${y}%;`;
    },

    focusCheckpoint(checkpoint) {
      this.barcode = checkpoint.barcode_value || "";
      this.note = "";
      this.$nextTick(() => {
        this.$refs.barcodeInput?.focus?.();
      });
    },

    selectRoute(routeId) {
      if (this.routeLocked()) {
        notify("Sesi patroli sedang aktif. Selesaikan dulu sebelum ganti rute.", "warning");
        return;
      }

      this.selectedRouteId = Number(routeId || 0);
    },

    clearAlerts() {
      this.errorMessage = "";
      this.successMessage = "";
    },

    flash(type, message) {
      this.clearAlerts();
      if (type === "error") {
        this.errorMessage = message;
      } else {
        this.successMessage = message;
      }

      window.setTimeout(() => {
        if (type === "error") {
          this.errorMessage = "";
        } else {
          this.successMessage = "";
        }
      }, 4500);
    },

    async startSession() {
      if (this.busy) {
        return;
      }

      const routeId = Number(this.selectedRouteId || 0);
      if (!routeId) {
        this.flash("error", "Pilih rute patroli terlebih dahulu.");
        return;
      }

      this.busy = true;
      this.clearAlerts();

      try {
        const body = new FormData();
        body.append("route_id", String(routeId));
        if (this.csrfName) {
          body.append(this.csrfName, this.csrfHash);
        }

        const response = await fetch(relUrl("/patrol/sessions/start"), {
          method: "POST",
          body,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        const contentType = response.headers.get("content-type") || "";
        const result = contentType.includes("application/json") ? await response.json() : null;

        if (!response.ok || !result?.ok || !result?.payload) {
          throw new Error(result?.message || "Gagal memulai sesi patroli.");
        }

        this.activeSession = result.payload;
        this.csrfHash = result.csrfHash || this.csrfHash;
        this.barcode = this.activeSession.nextCheckpoint?.barcode_value || "";
        this.note = "";
        this.status = "ok";
        this.photoFiles = [];
        this.photoPreviews.forEach((previewUrl) => URL.revokeObjectURL(previewUrl));
        this.photoPreviews = [];
        if (this.$refs.photoInput) {
          this.$refs.photoInput.value = "";
        }
        this.flash("success", "Sesi patroli berhasil dimulai.");
        window.setTimeout(() => this.$refs.barcodeInput?.focus?.(), 150);
      } catch (error) {
        console.error(error);
        this.flash("error", error.message || "Gagal memulai sesi patroli.");
      } finally {
        this.busy = false;
      }
    },

    async cancelSession() {
      if (this.busy || !this.sessionRunning()) {
        return;
      }

      if (!window.confirm("Batalkan sesi patroli aktif ini?")) {
        return;
      }

      this.busy = true;
      this.clearAlerts();

      try {
        const body = new FormData();
        body.append("session_id", String(this.activeSession.session.id));
        if (this.csrfName) {
          body.append(this.csrfName, this.csrfHash);
        }

        const response = await fetch(relUrl("/patrol/sessions/cancel"), {
          method: "POST",
          body,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        const contentType = response.headers.get("content-type") || "";
        const result = contentType.includes("application/json") ? await response.json() : null;

        if (!response.ok || !result?.ok) {
          throw new Error(result?.message || "Gagal membatalkan sesi patroli.");
        }

        this.activeSession = null;
        this.barcode = "";
        this.note = "";
        this.status = "ok";
        this.photoFiles = [];
        this.photoPreviews.forEach((previewUrl) => URL.revokeObjectURL(previewUrl));
        this.photoPreviews = [];
        if (this.$refs.photoInput) {
          this.$refs.photoInput.value = "";
        }
        this.flash("success", result.message || "Sesi patroli dibatalkan.");
      } catch (error) {
        console.error(error);
        this.flash("error", error.message || "Gagal membatalkan sesi patroli.");
      } finally {
        this.busy = false;
      }
    },

    async handlePhotoChange(event) {
      const files = Array.from(event.target.files || []).filter(Boolean);
      if (!files.length) {
        this.photoFiles = [];
        this.photoPreviews.forEach((previewUrl) => URL.revokeObjectURL(previewUrl));
        this.photoPreviews = [];
        return;
      }

      this.photoFiles = files;
      this.photoPreviews.forEach((previewUrl) => URL.revokeObjectURL(previewUrl));
      this.photoPreviews = files.map((file) => URL.createObjectURL(file));
    },

    async getCurrentPosition() {
      if (!navigator.geolocation) {
        throw new Error("GPS tidak tersedia di perangkat ini.");
      }

      return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            resolve({
              latitude: position.coords.latitude,
              longitude: position.coords.longitude,
              accuracy: position.coords.accuracy,
            });
          },
          (error) => {
            reject(new Error(error?.message || "Gagal membaca lokasi GPS."));
          },
          {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0,
          }
        );
      });
    },

    createWatermarkLines(checkpoint, gps) {
      const routeName = this.activeSession?.session?.route_name || this.selectedRoute()?.name || "Patrol";
      return [
        "PT Younghyun Star",
        `Security: ${this.user.name || "-"}`,
        `Rute: ${routeName}`,
        `Checkpoint: ${checkpoint.code || "-"} - ${checkpoint.name || "-"}`,
        `Waktu: ${formatDateTime(new Date())}`,
        `Lokasi: ${gps.latitude.toFixed(6)}, ${gps.longitude.toFixed(6)}`,
      ];
    },

    async compressAndWatermark(file, checkpoint, gps) {
      const imageUrl = URL.createObjectURL(file);

      try {
        const image = await new Promise((resolve, reject) => {
          const img = new Image();
          img.onload = () => resolve(img);
          img.onerror = () => reject(new Error("Gagal memuat foto patroli."));
          img.src = imageUrl;
        });

        const maxWidth = 1280;
        const scale = Math.min(1, maxWidth / image.width);
        const width = Math.max(1, Math.round(image.width * scale));
        const height = Math.max(1, Math.round(image.height * scale));

        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext("2d");
        if (!ctx) {
          throw new Error("Canvas tidak tersedia.");
        }

        ctx.drawImage(image, 0, 0, width, height);

        const lines = this.createWatermarkLines(checkpoint, gps);
        ctx.font = "bold 20px Arial";
        const padding = 18;
        const lineHeight = 22;
        const maxTextWidth = Math.max(...lines.map((line) => ctx.measureText(line).width));
        const boxWidth = Math.min(width - 24, maxTextWidth + padding * 2);
        const boxHeight = lines.length * lineHeight + padding * 2;
        const boxX = width - boxWidth - 16;
        const boxY = height - boxHeight - 16;

        ctx.fillStyle = "rgba(0, 0, 0, 0.55)";
        ctx.fillRect(boxX, boxY, boxWidth, boxHeight);
        ctx.strokeStyle = "rgba(255, 255, 255, 0.35)";
        ctx.lineWidth = 2;
        ctx.strokeRect(boxX, boxY, boxWidth, boxHeight);

        ctx.fillStyle = "#ffffff";
        ctx.textAlign = "left";
        ctx.textBaseline = "middle";
        lines.forEach((line, index) => {
          const y = boxY + padding + lineHeight / 2 + index * lineHeight;
          ctx.fillText(line, boxX + padding, y);
        });

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.82));
        if (!blob) {
          throw new Error("Gagal memproses foto patroli.");
        }

        const fileName = (file.name || "patrol.jpg").replace(/\.[^.]+$/, "") + ".jpg";
        return new File([blob], fileName, {
          type: "image/jpeg",
          lastModified: Date.now(),
        });
      } finally {
        URL.revokeObjectURL(imageUrl);
      }
    },

    async submitScan() {
      if (this.busy) {
        return;
      }

      if (!this.sessionRunning()) {
        this.flash("error", "Mulai sesi patroli terlebih dahulu.");
        return;
      }

      if (!this.barcode.trim()) {
        this.flash("error", "Barcode checkpoint wajib di-scan.");
        return;
      }

      if (!this.photoFiles.length) {
        this.flash("error", "Foto bukti wajib diambil dari kamera.");
        return;
      }

      if (this.status === "not_ok" && !String(this.note || "").trim()) {
        this.flash("error", "Catatan temuan wajib diisi saat status temuan.");
        return;
      }

      this.busy = true;
      this.clearAlerts();

      try {
        const gps = await this.getCurrentPosition();
        this.gps = gps;
        const checkpoint = this.nextCheckpoint();
        if (!checkpoint) {
          throw new Error("Semua checkpoint pada sesi ini sudah selesai.");
        }

        const compressedPhotos = [];
        for (const photoFile of this.photoFiles) {
          const photo = await this.compressAndWatermark(photoFile, checkpoint, gps);
          compressedPhotos.push(photo);
        }

        const formData = new FormData();
        formData.append("session_id", String(this.activeSession.session.id));
        formData.append("barcode", this.barcode.trim());
        formData.append("status", this.status);
        formData.append("latitude", String(gps.latitude));
        formData.append("longitude", String(gps.longitude));
        formData.append("accuracy", String(gps.accuracy || ""));
        formData.append("note", this.note || "");
        compressedPhotos.forEach((photo) => {
          formData.append("photos[]", photo);
        });

        if (this.csrfName) {
          formData.append(this.csrfName, this.csrfHash);
        }

        const response = await fetch(relUrl("/patrol/sessions/scan"), {
          method: "POST",
          body: formData,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        const contentType = response.headers.get("content-type") || "";
        const result = contentType.includes("application/json") ? await response.json() : null;

        if (!response.ok || !result?.ok || !result?.payload) {
          throw new Error(result?.message || "Gagal menyimpan check-in patroli.");
        }

        this.activeSession = result.payload;
        this.csrfHash = result.csrfHash || this.csrfHash;
        this.barcode = this.activeSession.nextCheckpoint?.barcode_value || "";
        this.note = "";
        this.status = "ok";
        this.photoFiles = [];
        this.photoPreviews.forEach((previewUrl) => URL.revokeObjectURL(previewUrl));
        this.photoPreviews = [];
        if (this.$refs.photoInput) {
          this.$refs.photoInput.value = "";
        }
        this.flash("success", result.message || "Check-in berhasil.");
        window.setTimeout(() => this.$refs.barcodeInput?.focus?.(), 180);
      } catch (error) {
        console.error(error);
        this.flash("error", error.message || "Gagal menyimpan check-in patroli.");
      } finally {
        this.busy = false;
      }
    },
  }));

  const deepClone = (value) => {
    try {
      return JSON.parse(JSON.stringify(value));
    } catch (error) {
      return value;
    }
  };

  Alpine.data("patrolDashboardPage", (boot = {}) => ({
    today: String(boot.today || ""),
    user: boot.user || {},
    adminStats: boot.adminStats || {},
    recentSessions: Array.isArray(boot.recentSessions) ? boot.recentSessions : [],
    recentLogs: Array.isArray(boot.recentLogs) ? boot.recentLogs : [],
    layout: boot.layout || { id: 0, name: "Layout Utama", image_url: "" },
    csrfName: String(boot.csrfName || ""),
    csrfHash: String(boot.csrfHash || ""),

    layoutName: String(boot.layout?.name || "Layout Utama"),
    layoutPreviewUrl: String(boot.layout?.image_url || ""),
    layoutFile: null,
    layoutFileName: "",
    layoutTempUrl: "",
    checkpointDrafts: deepClone(Array.isArray(boot.checkpoints) ? boot.checkpoints : []).map((checkpoint) => ({
      ...checkpoint,
      map_x: Number(checkpoint.map_x ?? 0),
      map_y: Number(checkpoint.map_y ?? 0),
      lat: checkpoint.lat !== null && checkpoint.lat !== undefined ? Number(checkpoint.lat) : null,
      lng: checkpoint.lng !== null && checkpoint.lng !== undefined ? Number(checkpoint.lng) : null,
      radius_m: Number(checkpoint.radius_m ?? 10),
    })),
    selectedCheckpointId: null,
    selectedCheckpointDraft: null,
    draggingId: null,
    draggingPointerId: null,
    busy: false,
    errorMessage: "",
    successMessage: "",

    init() {
      if (this.checkpointDrafts.length) {
        const first = this.checkpointDrafts[0];
        this.selectCheckpoint(first.id);
      }
    },

    canViewDashboard() {
      return ["admin", "compliance"].includes(String(this.user.role || "").toLowerCase());
    },

    clearAlerts() {
      this.errorMessage = "";
      this.successMessage = "";
    },

    flash(type, message) {
      this.clearAlerts();
      if (type === "error") {
        this.errorMessage = message;
      } else {
        this.successMessage = message;
      }

      window.setTimeout(() => {
        if (type === "error") {
          this.errorMessage = "";
        } else {
          this.successMessage = "";
        }
      }, 4500);
    },

    checkpointById(checkpointId) {
      const targetId = Number(checkpointId || 0);
      return this.checkpointDrafts.find((checkpoint) => Number(checkpoint.id || 0) === targetId) || null;
    },

    selectCheckpoint(checkpointId) {
      const checkpoint = this.checkpointById(checkpointId);
      if (!checkpoint) {
        return;
      }

      this.selectedCheckpointId = Number(checkpoint.id || 0);
      this.selectedCheckpointDraft = checkpoint;
    },

    markerClass(checkpoint) {
      return checkpoint === this.selectedCheckpointDraft ? "is-selected" : "";
    },

    markerStyle(checkpoint) {
      const x = Number(checkpoint.map_x || 0);
      const y = Number(checkpoint.map_y || 0);
      return `left:${x}%;top:${y}%;`;
    },

    layoutCanvasStyle() {
      if (this.layoutTempUrl || this.layoutPreviewUrl) {
        const imageUrl = this.layoutTempUrl || this.layoutPreviewUrl;
        return `background-image: linear-gradient(rgba(239, 246, 255, 0.35), rgba(226, 232, 240, 0.6)), url('${imageUrl}')`;
      }

      return "";
    },

    handleLayoutFileChange(event) {
      const files = Array.from(event.target.files || []).filter(Boolean);
      if (!files.length) {
        this.layoutFile = null;
        this.layoutFileName = "";
        if (this.layoutTempUrl) {
          URL.revokeObjectURL(this.layoutTempUrl);
          this.layoutTempUrl = "";
        }
        return;
      }

      this.layoutFile = files[0];
      this.layoutFileName = this.layoutFile.name || "layout.jpg";

      if (this.layoutTempUrl) {
        URL.revokeObjectURL(this.layoutTempUrl);
      }
      this.layoutTempUrl = URL.createObjectURL(this.layoutFile);
    },

    beginDrag(checkpoint, event) {
      if (this.busy) {
        return;
      }

      this.selectCheckpoint(checkpoint.id);
      this.draggingId = Number(checkpoint.id || 0);
      this.draggingPointerId = event.pointerId;
      event.preventDefault();
      event.stopPropagation();
    },

    stopDrag(event) {
      if (event && this.draggingPointerId !== null && event.pointerId !== this.draggingPointerId) {
        return;
      }

      this.draggingId = null;
      this.draggingPointerId = null;
    },

    dragCheckpoint(event) {
      if (!this.draggingId || this.draggingPointerId === null || event.pointerId !== this.draggingPointerId) {
        return;
      }

      const checkpoint = this.checkpointById(this.draggingId);
      const canvas = this.$refs.layoutCanvas;
      if (!checkpoint || !canvas) {
        return;
      }

      const rect = canvas.getBoundingClientRect();
      if (!rect.width || !rect.height) {
        return;
      }

      const x = ((event.clientX - rect.left) / rect.width) * 100;
      const y = ((event.clientY - rect.top) / rect.height) * 100;
      checkpoint.map_x = Math.max(0, Math.min(100, Number(x.toFixed(2))));
      checkpoint.map_y = Math.max(0, Math.min(100, Number(y.toFixed(2))));
      this.selectedCheckpointDraft = checkpoint;
    },

    resetDraft() {
      if (!window.confirm("Batal semua perubahan layout patroli?")) {
        return;
      }

      if (this.layoutTempUrl) {
        URL.revokeObjectURL(this.layoutTempUrl);
        this.layoutTempUrl = "";
      }

      this.layoutName = String(boot.layout?.name || "Layout Utama");
      this.layoutPreviewUrl = String(boot.layout?.image_url || "");
      this.layoutFile = null;
      this.layoutFileName = "";
      if (this.$refs.layoutFileInput) {
        this.$refs.layoutFileInput.value = "";
      }

      this.checkpointDrafts = deepClone(Array.isArray(boot.checkpoints) ? boot.checkpoints : []).map((checkpoint) => ({
        ...checkpoint,
        map_x: Number(checkpoint.map_x ?? 0),
        map_y: Number(checkpoint.map_y ?? 0),
        lat: checkpoint.lat !== null && checkpoint.lat !== undefined ? Number(checkpoint.lat) : null,
        lng: checkpoint.lng !== null && checkpoint.lng !== undefined ? Number(checkpoint.lng) : null,
        radius_m: Number(checkpoint.radius_m ?? 10),
      }));
      if (this.checkpointDrafts.length) {
        this.selectCheckpoint(this.checkpointDrafts[0].id);
      } else {
        this.selectedCheckpointId = null;
        this.selectedCheckpointDraft = null;
      }
      this.flash("success", "Perubahan layout dibatalkan.");
    },

    async saveLayout() {
      if (this.busy) {
        return;
      }

      this.busy = true;
      this.clearAlerts();

      try {
        const body = new FormData();
        body.append("name", this.layoutName || "Layout Utama");
        body.append(
          "checkpoints_json",
          JSON.stringify(
            this.checkpointDrafts.map((checkpoint) => ({
              id: checkpoint.id,
              name: checkpoint.name || "",
              area: checkpoint.area || "",
              barcode_value: checkpoint.barcode_value || "",
              lat: checkpoint.lat,
              lng: checkpoint.lng,
              radius_m: checkpoint.radius_m,
              map_x: checkpoint.map_x,
              map_y: checkpoint.map_y,
            }))
          )
        );
        if (this.layoutFile) {
          body.append("layout_image", this.layoutFile);
        }
        if (this.csrfName) {
          body.append(this.csrfName, this.csrfHash);
        }

        const response = await fetch(relUrl("/patrol/layout/save"), {
          method: "POST",
          body,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });

        const contentType = response.headers.get("content-type") || "";
        const result = contentType.includes("application/json") ? await response.json() : null;

        if (!response.ok || !result?.ok) {
          throw new Error(result?.message || "Gagal menyimpan layout patroli.");
        }

        this.csrfHash = result.csrfHash || this.csrfHash;
        if (result.payload?.layout) {
          this.layout = result.payload.layout;
          this.layoutName = result.payload.layout.name || this.layoutName;
          if (this.layoutTempUrl) {
            URL.revokeObjectURL(this.layoutTempUrl);
            this.layoutTempUrl = "";
          }
          this.layoutPreviewUrl = result.payload.layout.image_url || "";
          this.layoutFile = null;
          this.layoutFileName = "";
          if (this.$refs.layoutFileInput) {
            this.$refs.layoutFileInput.value = "";
          }
        }

        if (Array.isArray(result.payload?.checkpoints)) {
          this.checkpointDrafts = deepClone(result.payload.checkpoints).map((checkpoint) => ({
            ...checkpoint,
            map_x: Number(checkpoint.map_x ?? 0),
            map_y: Number(checkpoint.map_y ?? 0),
            lat: checkpoint.lat !== null && checkpoint.lat !== undefined ? Number(checkpoint.lat) : null,
            lng: checkpoint.lng !== null && checkpoint.lng !== undefined ? Number(checkpoint.lng) : null,
            radius_m: Number(checkpoint.radius_m ?? 10),
          }));
          if (this.checkpointDrafts.length) {
            this.selectCheckpoint(this.checkpointDrafts[0].id);
          }
        }

        this.flash("success", result.message || "Layout patroli berhasil disimpan.");
      } catch (error) {
        console.error(error);
        this.flash("error", error.message || "Gagal menyimpan layout patroli.");
      } finally {
        this.busy = false;
      }
    },
  }));
});
