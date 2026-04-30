<?= $this->extend('layouts/main') ?>

<?php
$boot = $boot ?? [];
$viewMode = $viewMode ?? 'dashboard';
?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/assets/css/patrol.css?v=<?= filemtime(FCPATH . 'assets/css/patrol.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<script>
  window.PATROL_DASHBOARD_BOOT = <?= json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<div class="patrol-page" x-cloak x-data="patrolDashboardPage(window.PATROL_DASHBOARD_BOOT)" x-init="init()" @pointermove.window="dragCheckpoint($event); dragLayoutImage($event)" @pointerup.window="stopDrag($event); stopLayoutPan($event)" @pointercancel.window="stopDrag($event); stopLayoutPan($event)">
  <section class="card patrol-hero border-0 shadow-sm no-lift mb-3">
    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div>
        <p class="inventory-kicker mb-1">Patrol Dashboard</p>
        <h4 class="fw-bold mb-2">Dashboard Layout & Monitoring Patroli</h4>
        <p class="text-muted mb-0">
          Klik checkpoint untuk melihat detail, foto check-in, dan histori patroli.
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
        <?php if (($boot['user']['role'] ?? '') === 'admin'): ?>
          <a href="/patrol/editor" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil-square me-1"></i>
            Edit Layout
          </a>
        <?php endif; ?>
        <a href="/patrol" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-arrow-left me-1"></i>
          Kembali ke Patroli
        </a>
      </div>
    </div>
  </section>

  <section class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
      <div class="card patrol-dashboard-box shadow-sm border-0 no-lift h-100">
        <div class="text-muted small text-uppercase">Sesi Hari Ini</div>
        <div class="display-6 fw-bold mb-0" x-text="adminStats.sessions || 0"></div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card patrol-dashboard-box shadow-sm border-0 no-lift h-100">
        <div class="text-muted small text-uppercase">Aktif</div>
        <div class="display-6 fw-bold mb-0 text-primary" x-text="adminStats.active || 0"></div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card patrol-dashboard-box shadow-sm border-0 no-lift h-100">
        <div class="text-muted small text-uppercase">Selesai</div>
        <div class="display-6 fw-bold mb-0 text-success" x-text="adminStats.completed || 0"></div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card patrol-dashboard-box shadow-sm border-0 no-lift h-100">
        <div class="text-muted small text-uppercase">Temuan</div>
        <div class="display-6 fw-bold mb-0 text-danger" x-text="adminStats.issues || 0"></div>
      </div>
    </div>
  </section>

  <section class="card patrol-map-card border-0 shadow-sm no-lift mb-3">
    <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h6 class="mb-0 fw-semibold">Layout Patroli</h6>
        <div class="text-muted small">Klik checkpoint untuk melihat detail dan semua foto check-in.</div>
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
        class="patrol-map patrol-map-dashboard"
        :class="{'has-session': !!activeSession, 'has-layout-image': !!layout.image_url}"
        :style="layoutCanvasStyle()">
        <div class="patrol-layout-image-layer" :style="layoutImageLayerStyle()"></div>
        <div class="patrol-map-grid"></div>
        <template x-for="checkpoint in activeRouteCheckpoints()" :key="`map-${checkpoint.id}`">
          <button
            type="button"
            class="patrol-marker"
            :class="mapMarkerClass(checkpoint)"
            :style="mapMarkerStyle(checkpoint)"
            @click="selectMapCheckpoint(checkpoint.id)"
            :title="`${checkpoint.code} - ${checkpoint.name}`">
            <span x-text="checkpoint.route_order"></span>
            <span class="patrol-marker-photo-badge" x-show="photoCount(checkpoint.id) > 0">
              <i class="bi bi-camera-fill me-1"></i>
              <span x-text="photoLabel(checkpoint.id)"></span>
            </span>
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
            <span class="mx-1 text-muted">|</span>
            <span class="text-success" x-text="photoCount(checkpoint.id) ? `Foto ${photoCount(checkpoint.id)}` : 'Belum ada foto'"></span>
          </span>
        </template>
      </div>
    </div>
  </section>

  <section class="row g-3 mt-1">
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm no-lift h-100">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-semibold">Sesi Patroli Hari Ini</h6>
          <span class="badge text-bg-light border text-dark" x-text="recentSessions.length"></span>
        </div>
        <div class="card-body">
          <template x-if="!recentSessions.length">
            <div class="text-muted small">Belum ada sesi patroli hari ini.</div>
          </template>

          <div class="d-grid gap-2" x-show="recentSessions.length">
            <template x-for="session in recentSessions" :key="`session-${session.id}`">
              <div class="border rounded-3 p-3 patrol-log-item">
                <div class="d-flex justify-content-between align-items-center gap-2">
                  <div>
                    <div class="fw-semibold" x-text="session.route_name"></div>
                    <div class="text-muted small" x-text="session.started_by_name || '-'"></div>
                  </div>
                  <span class="badge" :class="session.status === 'completed' ? 'text-bg-success' : (session.status === 'active' ? 'text-bg-primary' : 'text-bg-secondary')" x-text="session.status"></span>
                </div>
                <div class="small text-muted mt-2">
                  <span x-text="session.patrol_date"></span>
                  <span class="mx-1">|</span>
                  <span x-text="session.checked_count + '/' + session.total_checkpoints"></span>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card border-0 shadow-sm no-lift h-100">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-semibold">Log Temuan & Check-in</h6>
          <span class="badge text-bg-light border text-dark" x-text="recentLogs.length"></span>
        </div>
        <div class="card-body">
          <template x-if="!recentLogs.length">
            <div class="text-muted small">Belum ada log patroli hari ini.</div>
          </template>

          <div class="d-grid gap-2" x-show="recentLogs.length">
            <template x-for="log in recentLogs" :key="`recent-log-${log.id}`">
              <div class="border rounded-3 p-3 patrol-log-item">
                <div class="d-flex justify-content-between gap-2">
                  <div>
                    <div class="fw-semibold" x-text="log.code + ' - ' + log.checkpoint_name"></div>
                    <div class="text-muted small" x-text="log.route_name"></div>
                  </div>
                  <span class="badge" :class="log.status === 'not_ok' ? 'text-bg-danger' : 'text-bg-success'" x-text="log.status === 'not_ok' ? 'Temuan' : 'Aman'"></span>
                </div>
                <div class="small text-muted mt-2">
                  <span x-text="log.checked_at"></span>
                  <span class="mx-1">|</span>
                  <span x-text="(log.distance_m || 0) + ' meter'"></span>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="patrol-modal-backdrop" x-show="selectedCheckpointModalOpen" x-transition.opacity>
    <div class="patrol-modal-panel card border-0 shadow-lg" @click.outside="closeCheckpointModal()">
      <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <div>
          <div class="inventory-kicker mb-1">Checkpoint Detail</div>
          <h5 class="mb-0 fw-bold" x-text="selectedCheckpointDraft ? (selectedCheckpointDraft.code + ' - ' + selectedCheckpointDraft.name) : 'Checkpoint'"></h5>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="closeCheckpointModal()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-lg-5">
            <div class="patrol-mini-stat bg-light h-100">
              <div class="text-muted small">Area</div>
              <div class="fw-semibold" x-text="selectedCheckpointDraft ? (selectedCheckpointDraft.area || '-') : '-'"></div>
              <div class="text-muted small mt-2">Barcode</div>
              <div class="fw-semibold text-primary" x-text="selectedCheckpointDraft ? (selectedCheckpointDraft.barcode_value || '-') : '-'"></div>
              <div class="text-muted small mt-2">GPS</div>
              <div class="fw-semibold" x-text="selectedCheckpointDraft ? `${Number(selectedCheckpointDraft.lat || 0).toFixed(6)}, ${Number(selectedCheckpointDraft.lng || 0).toFixed(6)}` : '-'"></div>
              <div class="text-muted small mt-2">Radius</div>
              <div class="fw-semibold" x-text="selectedCheckpointDraft ? `${selectedCheckpointDraft.radius_m || 10} meter` : '-'"></div>
              <div class="text-muted small mt-2">Posisi Marker</div>
              <div class="fw-semibold" x-text="selectedCheckpointDraft ? `${Number(selectedCheckpointDraft.map_x || 0).toFixed(1)}% / ${Number(selectedCheckpointDraft.map_y || 0).toFixed(1)}%` : '-'"></div>
            </div>
          </div>
          <div class="col-lg-7">
            <template x-if="!selectedCheckpointPhotoLogs.length">
              <div class="text-muted small">Belum ada foto check-in pada checkpoint ini.</div>
            </template>
            <div class="d-grid gap-3" x-show="selectedCheckpointPhotoLogs.length">
              <template x-for="log in selectedCheckpointPhotoLogs" :key="`modal-log-${log.id}`">
                <div class="patrol-mini-stat bg-light">
                  <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <div>
                      <div class="fw-semibold" x-text="log.checked_at"></div>
                      <div class="text-muted small" x-text="(log.status === 'not_ok' ? 'Temuan' : 'Aman') + ' | ' + (log.distance_m || 0) + ' meter'"></div>
                    </div>
                    <span class="badge" :class="log.status === 'not_ok' ? 'text-bg-danger' : 'text-bg-success'" x-text="log.status === 'not_ok' ? 'Temuan' : 'Aman'"></span>
                  </div>
                  <div class="row g-2">
                    <template x-for="(photo, index) in (Array.isArray(log.photos) ? log.photos : [])" :key="`modal-photo-${log.id}-${index}`">
                      <div class="col-6 col-md-4">
                        <a :href="photoUrl(photo.photo_path)" target="_blank" rel="noopener" class="d-block">
                          <img :src="photoUrl(photo.photo_path)" class="img-fluid rounded-3 border patrol-preview" alt="Foto patroli checkpoint">
                        </a>
                      </div>
                    </template>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/patrol.js?v=<?= filemtime(FCPATH . 'js/patrol.js') ?>"></script>
<?= $this->endSection() ?>
