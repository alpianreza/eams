<?= $this->extend('layouts/main') ?>

<?php
$boot = $boot ?? [];
?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/patrol.css?v=<?= filemtime(FCPATH . 'assets/css/patrol.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<script>
  window.PATROL_BOOT = <?= json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<div class="patrol-page" x-cloak x-data="patrolSecurityPage(window.PATROL_BOOT)" x-init="init()">
  <section class="card patrol-hero border-0 shadow-sm no-lift mb-3">
    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <p class="inventory-kicker mb-1">Patrol Security</p>
        <h4 class="fw-bold mb-2">Patroli Harian PT Younghyun Star</h4>
        <p class="text-muted mb-0">
          Scan barcode di checkpoint, foto wajib dari kamera, dan GPS radius 10 meter untuk validasi lokasi.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="badge rounded-pill text-bg-dark px-3 py-2">
          <i class="bi bi-calendar3 me-1"></i>
          <span x-text="today"></span>
        </span>
        <span class="badge rounded-pill text-bg-success px-3 py-2">
          <i class="bi bi-person-badge me-1"></i>
          <span x-text="user.name || '-'"></span>
        </span>
        <span class="badge rounded-pill text-bg-primary px-3 py-2">
          <i class="bi bi-shield-check me-1"></i>
          <span x-text="user.role ? user.role.toUpperCase() : '-'"></span>
        </span>
        <a href="/patrol/dashboard" class="btn btn-sm btn-outline-primary" x-show="canViewDashboard()">
          <i class="bi bi-speedometer2 me-1"></i>
          Dashboard
        </a>
      </div>
    </div>
  </section>

  <section class="row g-3">
    <div class="col-xl-8">
      <div class="card patrol-map-card border-0 shadow-sm no-lift h-100">
        <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <h6 class="mb-0 fw-semibold">Layout PT</h6>
            <div class="text-muted small">Checkpoint ditandai di atas denah. Klik marker untuk fokus ke titik.</div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <template x-for="route in routes" :key="route.id">
              <button
                type="button"
                class="btn btn-sm"
                :class="selectedRouteId == route.id ? 'btn-primary' : 'btn-outline-primary'"
                @click="selectRoute(route.id)"
                x-text="route.name">
              </button>
            </template>
          </div>
        </div>

        <div class="card-body">
          <div
            class="patrol-map"
            :class="{'has-session': !!activeSession, 'has-layout-image': !!layout.image_url}"
            :style="layout.image_url ? `background-image: linear-gradient(rgba(239, 246, 255, 0.42), rgba(226, 232, 240, 0.65)), url('${layout.image_url}')` : ''">
            <div class="patrol-map-grid"></div>
            <div class="patrol-map-building patrol-building-main"></div>
            <div class="patrol-map-building patrol-building-top"></div>
            <div class="patrol-map-building patrol-building-left"></div>
            <div class="patrol-map-building patrol-building-bottom"></div>

            <template x-for="checkpoint in activeRouteCheckpoints()" :key="checkpoint.id">
              <button
                type="button"
                class="patrol-marker"
                :class="markerClass(checkpoint)"
                :style="markerStyle(checkpoint)"
                @click="focusCheckpoint(checkpoint)"
                :title="`${checkpoint.code} - ${checkpoint.name}`">
                <span x-text="checkpoint.route_order"></span>
              </button>
            </template>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <template x-for="checkpoint in activeRouteCheckpoints()" :key="`chip-${checkpoint.id}`">
              <span class="badge rounded-pill text-bg-light border text-dark patrol-chip">
                <span class="fw-semibold" x-text="checkpoint.code"></span>
                <span class="mx-1">-</span>
                <span x-text="checkpoint.name"></span>
                <span class="mx-1 text-muted">|</span>
                <span class="text-primary" x-text="checkpoint.barcode_value"></span>
              </span>
            </template>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card border-0 shadow-sm no-lift mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
              <h6 class="fw-semibold mb-1">Sesi Patroli</h6>
              <p class="text-muted small mb-0">Buat sesi baru lalu scan barcode sesuai urutan checkpoint.</p>
            </div>
            <span class="badge rounded-pill text-bg-secondary" x-text="activeSession ? activeSession.session.status : 'belum mulai'"></span>
          </div>

          <template x-if="!sessionRunning()">
            <div class="border rounded-3 p-3 bg-light-subtle">
              <div class="mb-2 small text-muted">Pilih rute patroli</div>
              <div class="d-grid gap-2 mb-3">
                <template x-for="route in routes" :key="`route-${route.id}`">
                  <button
                    type="button"
                    class="btn text-start"
                    :class="selectedRouteId == route.id ? 'btn-primary' : 'btn-outline-primary'"
                    @click="selectRoute(route.id)">
                    <div class="fw-semibold" x-text="route.name"></div>
                    <small class="opacity-75" x-text="route.description || 'Rute patroli'"></small>
                  </button>
                </template>
              </div>

              <button type="button" class="btn btn-success w-100" @click="startSession()" :disabled="busy">
                <span x-show="!busy"><i class="bi bi-play-fill me-1"></i> Mulai Patroli</span>
                <span x-show="busy"><i class="bi bi-hourglass-split me-1"></i> Memproses...</span>
              </button>
            </div>
          </template>

          <template x-if="activeSession">
            <div class="border rounded-3 p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <div class="small text-muted">Rute Aktif</div>
                  <div class="fw-semibold" x-text="activeSession.session.route_name"></div>
                </div>
                <span class="badge text-bg-success" x-text="activeSession.session.status === 'completed' ? 'Selesai' : 'Aktif'"></span>
              </div>

              <div class="patrol-progress mb-3">
                <div class="d-flex justify-content-between small mb-1">
                  <span>Progress</span>
                  <span><span x-text="activeSession.progress.checked"></span>/<span x-text="activeSession.progress.total"></span></span>
                </div>
                <div class="progress" style="height: 10px;">
                  <div class="progress-bar" :style="`width:${activeSession.progress.percent}%;`"></div>
                </div>
              </div>

              <div class="small text-muted mb-2">Checkpoint berikutnya</div>
              <div class="patrol-next-card mb-3" x-show="activeSession.nextCheckpoint">
                <div class="fw-semibold" x-text="activeSession.nextCheckpoint.code + ' - ' + activeSession.nextCheckpoint.name"></div>
                <div class="text-muted small" x-text="'Barcode: ' + activeSession.nextCheckpoint.barcode_value"></div>
                <div class="text-muted small" x-text="'Urutan: ' + activeSession.nextCheckpoint.route_order"></div>
              </div>

              <div class="row g-2">
                <div class="col-6">
                  <div class="patrol-mini-stat">
                    <div class="text-muted small">Lokasi</div>
                    <div class="fw-semibold" x-text="activeSession.nextCheckpoint ? activeSession.nextCheckpoint.area : '-'"></div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="patrol-mini-stat">
                    <div class="text-muted small">Mulai</div>
                    <div class="fw-semibold" x-text="activeSession.session.started_at || '-'"></div>
                  </div>
                </div>
              </div>

              <div class="d-grid mt-3" x-show="sessionRunning()">
                <button type="button" class="btn btn-outline-danger" @click="cancelSession()" :disabled="busy">
                  <span x-show="!busy"><i class="bi bi-x-circle me-1"></i> Batal Sesi</span>
                  <span x-show="busy"><i class="bi bi-hourglass-split me-1"></i> Memproses...</span>
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>

      <div class="card border-0 shadow-sm no-lift mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-semibold mb-0">Scan Barcode</h6>
              <span class="badge text-bg-info" x-text="sessionRunning() ? 'Siap check-in' : 'Mulai sesi dulu'"></span>
          </div>

          <div class="alert alert-warning py-2 small mb-3" x-show="errorMessage" x-text="errorMessage"></div>
          <div class="alert alert-success py-2 small mb-3" x-show="successMessage" x-text="successMessage"></div>

          <div class="mb-3">
            <label class="form-label small text-muted">Barcode checkpoint</label>
            <input
              type="text"
              class="form-control"
              placeholder="Scan barcode di checkpoint"
              x-model.trim="barcode"
              @keydown.enter.prevent="submitScan()"
              :disabled="!sessionRunning() || busy"
              x-ref="barcodeInput">
            <div class="form-text">Gunakan scanner barcode di checkpoint. Input akan otomatis menangkap hasil scan.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small text-muted">Foto bukti</label>
            <input
              type="file"
              class="form-control"
              accept="image/*"
              capture="environment"
              multiple
              @change="handlePhotoChange($event)"
              :disabled="!sessionRunning() || busy"
              x-ref="photoInput">
            <div class="form-text">Foto wajib diambil dari kamera HP dan akan dikompres sebelum upload.</div>
          </div>

          <div class="mb-3" x-show="photoPreviews.length">
            <label class="form-label small text-muted">Preview Foto</label>
            <div class="row g-2">
              <template x-for="(preview, index) in photoPreviews" :key="`preview-${index}`">
                <div class="col-6">
                  <img :src="preview" class="img-fluid rounded-3 border patrol-preview" alt="Preview foto patroli">
                </div>
              </template>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small text-muted">Status</label>
            <select class="form-select" x-model="status" :disabled="!activeSession || busy">
              <option value="ok">Aman</option>
              <option value="not_ok">Temuan</option>
            </select>
          </div>

          <div class="mb-3" x-show="status === 'not_ok'">
            <label class="form-label small text-muted">Catatan Temuan</label>
              <textarea class="form-control" rows="3" x-model.trim="note" placeholder="Tuliskan temuan singkat" :disabled="!sessionRunning() || busy"></textarea>
          </div>

          <div class="mb-3">
            <button type="button" class="btn btn-primary w-100" @click="submitScan()" :disabled="!sessionRunning() || busy">
              <span x-show="!busy"><i class="bi bi-check2-circle me-1"></i> Simpan Check-in</span>
              <span x-show="busy"><i class="bi bi-hourglass-split me-1"></i> Menyimpan...</span>
            </button>
          </div>

          <div class="small text-muted">
            GPS akan diambil otomatis saat submit. Radius checkpoint maksimal 10 meter.
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="row g-3 mt-1" x-show="activeSession">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm no-lift h-100">
        <div class="card-header bg-white border-0">
          <h6 class="mb-0 fw-semibold">Urutan Checkpoint</h6>
        </div>
        <div class="card-body">
          <div class="timeline-list">
            <template x-for="checkpoint in activeSession ? activeSession.checkpoints : []" :key="`timeline-${checkpoint.id}`">
              <div class="timeline-item" :class="markerClass(checkpoint)">
                <div class="timeline-bullet" x-text="checkpoint.route_order"></div>
                <div class="timeline-content">
                  <div class="d-flex justify-content-between align-items-center gap-2">
                    <div>
                      <div class="fw-semibold" x-text="checkpoint.code + ' - ' + checkpoint.name"></div>
                      <div class="text-muted small" x-text="checkpoint.area"></div>
                      <div class="text-primary small" x-text="'Barcode: ' + checkpoint.barcode_value"></div>
                    </div>
                    <span class="badge" :class="checkpoint.checked ? 'text-bg-success' : (activeSession.nextCheckpoint && activeSession.nextCheckpoint.id === checkpoint.id ? 'text-bg-primary' : 'text-bg-secondary')" x-text="checkpoint.checked ? 'Done' : (activeSession.nextCheckpoint && activeSession.nextCheckpoint.id === checkpoint.id ? 'Next' : 'Pending')"></span>
                  </div>
                  <div class="small text-muted mt-1" x-show="checkpoint.log">
                    <span x-text="checkpoint.log.checked_at"></span>
                    <span class="mx-1">|</span>
                    <span x-text="checkpoint.log.status === 'not_ok' ? 'Temuan' : 'Aman'"></span>
                    <span class="mx-1">|</span>
                    <span x-text="checkpoint.log.distance_m + ' meter'"></span>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm no-lift h-100">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-semibold">Log Terbaru</h6>
          <span class="badge text-bg-light border text-dark" x-text="activeSession.logs ? activeSession.logs.length : 0"></span>
        </div>
        <div class="card-body">
          <template x-if="!activeSession.logs || activeSession.logs.length === 0">
            <div class="text-muted small">Belum ada check-in pada sesi ini.</div>
          </template>

          <div class="d-flex flex-column gap-2" x-show="activeSession.logs && activeSession.logs.length">
            <template x-for="log in activeSession.logs.slice().reverse()" :key="`log-${log.id}`">
              <div class="border rounded-3 p-2 patrol-log-item">
                <div class="d-flex justify-content-between gap-2">
                  <div class="fw-semibold small" x-text="log.code + ' - ' + log.name"></div>
                  <span class="badge" :class="log.status === 'not_ok' ? 'text-bg-danger' : 'text-bg-success'" x-text="log.status === 'not_ok' ? 'Temuan' : 'Aman'"></span>
                </div>
                <div class="text-muted small" x-text="log.checked_at"></div>
                <div class="text-muted small" x-text="log.distance_m + ' meter | ' + (log.area || '-')"></div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </section>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/patrol.js?v=<?= filemtime(FCPATH . 'js/patrol.js') ?>"></script>
<?= $this->endSection() ?>
