<?= $this->extend('layouts/main') ?>

<?php
$boot = $boot ?? [];
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
        <p class="inventory-kicker mb-1">Patrol Layout Editor</p>
        <h4 class="fw-bold mb-2">Edit Layout Patroli</h4>
        <p class="text-muted mb-0">
          Admin bisa mengubah layout gambar, memindahkan titik patroli, dan menyimpan posisi checkpoint.
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
        <a href="/patrol/dashboard" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-arrow-left me-1"></i>
          Kembali ke Dashboard
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

  <section class="row g-3">
    <div class="col-xl-8">
      <div class="card patrol-map-card border-0 shadow-sm no-lift h-100">
        <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <h6 class="mb-0 fw-semibold" x-text="canEditLayout ? 'Editor Layout' : 'Layout Patroli'"></h6>
            <div class="text-muted small" x-text="canEditLayout ? 'Upload gambar layout, lalu geser titik patroli dan lihat foto check-in terakhir di peta.' : 'Lihat titik patroli, barcode, dan foto check-in terakhir di peta.'"></div>
          </div>
          <div class="d-flex flex-wrap gap-2" x-show="canEditLayout">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="resetDraft()" :disabled="busy">
              <i class="bi bi-arrow-counterclockwise me-1"></i>
              Batal Edit
            </button>
            <button type="button" class="btn btn-primary btn-sm" @click="saveLayout()" :disabled="busy">
              <span x-show="!busy"><i class="bi bi-save me-1"></i> Simpan Layout</span>
              <span x-show="busy"><i class="bi bi-hourglass-split me-1"></i> Menyimpan...</span>
            </button>
          </div>
        </div>

        <div class="card-body">
          <div class="alert alert-warning py-2 small mb-3" x-show="errorMessage" x-text="errorMessage"></div>
          <div class="alert alert-success py-2 small mb-3" x-show="successMessage" x-text="successMessage"></div>

          <div class="row g-3 mb-3" x-show="canEditLayout">
            <div class="col-lg-5">
              <label class="form-label small text-muted">Nama Layout</label>
              <input type="text" class="form-control" x-model.trim="layoutName" placeholder="Layout Utama">
            </div>
            <div class="col-lg-7">
              <label class="form-label small text-muted">Ganti Gambar Layout</label>
              <input type="file" class="form-control" accept="image/*" @change="handleLayoutFileChange($event)" :disabled="busy" x-ref="layoutFileInput">
              <div class="form-text">
                Gunakan gambar denah PT. File baru akan mengganti background layout.
                <span class="fw-semibold" x-show="layoutFileName" x-text="'File: ' + layoutFileName"></span>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3" x-show="canEditLayout">
            <div class="col-lg-4">
              <label class="form-label small text-muted">Zoom Gambar</label>
              <div class="d-flex align-items-center gap-2">
                <input type="range" class="form-range" min="1" max="3" step="0.05" x-model.number="layoutScale" @input="syncLayoutTransform()">
                <span class="small fw-semibold text-nowrap" x-text="`${Math.round(layoutScale * 100)}%`"></span>
              </div>
            </div>
            <div class="col-lg-3">
              <label class="form-label small text-muted">Geser X (%)</label>
              <input type="number" class="form-control" min="-80" max="80" step="0.5" x-model.number="layoutOffsetX" @input="syncLayoutTransform()">
            </div>
            <div class="col-lg-3">
              <label class="form-label small text-muted">Geser Y (%)</label>
              <input type="number" class="form-control" min="-80" max="80" step="0.5" x-model.number="layoutOffsetY" @input="syncLayoutTransform()">
            </div>
            <div class="col-lg-2 d-flex align-items-end">
              <button type="button" class="btn btn-outline-secondary btn-sm w-100" @click="resetLayoutImageTransform()">
                <i class="bi bi-aspect-ratio me-1"></i>
                Reset Gambar
              </button>
            </div>
            <div class="col-12">
              <div class="form-text">
                Geser langsung gambar di dalam kanvas untuk menyesuaikan denah dengan titik checkpoint.
              </div>
            </div>
          </div>

          <div
            class="patrol-layout-canvas patrol-map-editor"
            x-ref="layoutCanvas"
            @pointerdown="beginLayoutPan($event)"
            :style="layoutCanvasStyle()">
            <div class="patrol-layout-image-layer" :style="layoutImageLayerStyle()"></div>
            <div class="patrol-map-grid"></div>
            <div class="patrol-layout-overlay"></div>

            <template x-for="checkpoint in checkpointDrafts" :key="`layout-marker-${checkpoint.id}`">
              <button
                type="button"
                class="patrol-layout-marker patrol-marker"
                :class="[mapMarkerClass(checkpoint), !canEditLayout ? 'is-readonly' : '']"
                :style="mapMarkerStyle(checkpoint)"
                @pointerdown.prevent="canEditLayout && beginDrag(checkpoint, $event)"
                @click.stop="selectMapCheckpoint(checkpoint.id)"
                :title="`${checkpoint.code} - ${checkpoint.name}`">
                <span x-text="checkpoint.code"></span>
                <span class="patrol-marker-photo-badge" x-show="photoCount(checkpoint.id) > 0">
                  <i class="bi bi-camera-fill me-1"></i>
                  <span x-text="photoLabel(checkpoint.id)"></span>
                </span>
              </button>
            </template>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <template x-for="checkpoint in checkpointDrafts" :key="`layout-chip-${checkpoint.id}`">
              <button
                type="button"
                class="badge rounded-pill text-bg-light border text-dark patrol-chip patrol-chip-btn"
                :class="selectedCheckpointId === checkpoint.id ? 'is-selected' : ''"
                @click="selectCheckpoint(checkpoint.id)">
                <span class="fw-semibold" x-text="checkpoint.code"></span>
                <span class="mx-1">-</span>
                <span x-text="checkpoint.name"></span>
                <span class="mx-1 text-muted">|</span>
                <span class="text-primary" x-text="checkpoint.barcode_value"></span>
                <span class="mx-1 text-muted">|</span>
                <span class="text-success" x-text="photoCount(checkpoint.id) ? `Foto ${photoCount(checkpoint.id)}` : 'Belum ada foto'"></span>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card border-0 shadow-sm no-lift mb-3 patrol-editor-panel">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
              <h6 class="fw-semibold mb-1">Titik Terpilih</h6>
              <p class="text-muted small mb-0" x-show="canEditLayout">Klik marker untuk mengedit koordinat, barcode, dan radius GPS.</p>
              <p class="text-muted small mb-0" x-show="!canEditLayout">Klik marker untuk melihat detail dan foto checkpoint.</p>
            </div>
            <span class="badge text-bg-primary" x-text="selectedCheckpointDraft ? selectedCheckpointDraft.code : '-'"></span>
          </div>

          <template x-if="selectedCheckpointDraft && canEditLayout">
            <div class="d-grid gap-3">
              <div>
                <label class="form-label small text-muted">Nama Titik</label>
                <input type="text" class="form-control" x-model.trim="selectedCheckpointDraft.name">
              </div>
              <div>
                <label class="form-label small text-muted">Area</label>
                <input type="text" class="form-control" x-model.trim="selectedCheckpointDraft.area">
              </div>
              <div>
                <label class="form-label small text-muted">Barcode</label>
                <input type="text" class="form-control" x-model.trim="selectedCheckpointDraft.barcode_value">
              </div>
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label small text-muted">Lat GPS</label>
                  <input type="number" step="0.000001" class="form-control" x-model.trim="selectedCheckpointDraft.lat">
                </div>
                <div class="col-6">
                  <label class="form-label small text-muted">Lng GPS</label>
                  <input type="number" step="0.000001" class="form-control" x-model.trim="selectedCheckpointDraft.lng">
                </div>
              </div>
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label small text-muted">Radius (m)</label>
                  <input type="number" min="1" step="1" class="form-control" x-model.trim="selectedCheckpointDraft.radius_m">
                </div>
                <div class="col-6">
                  <label class="form-label small text-muted">Koordinat X %</label>
                  <input type="number" min="0" max="100" step="0.1" class="form-control" x-model.trim="selectedCheckpointDraft.map_x">
                </div>
              </div>
              <div>
                <label class="form-label small text-muted">Koordinat Y %</label>
                <input type="number" min="0" max="100" step="0.1" class="form-control" x-model.trim="selectedCheckpointDraft.map_y">
              </div>
              <div class="patrol-mini-stat bg-light">
                <div class="text-muted small">Posisi Marker</div>
                <div class="fw-semibold" x-text="`${selectedCheckpointDraft.map_x ?? 0}% / ${selectedCheckpointDraft.map_y ?? 0}%`"></div>
              </div>

              <div class="patrol-mini-stat bg-light">
                <div class="text-muted small mb-1">Foto Check-in Terakhir</div>
                <template x-if="selectedCheckpointPhotoLog">
                  <div>
                    <div class="fw-semibold" x-text="selectedCheckpointPhotoLog.checked_at"></div>
                    <div class="text-muted small" x-text="selectedCheckpointPhotoLog.status === 'not_ok' ? 'Temuan' : 'Aman'"></div>
                    <div class="row g-2 mt-2" x-show="checkpointPhotos(selectedCheckpointDraft.id).length">
                      <template x-for="(photo, index) in checkpointPhotos(selectedCheckpointDraft.id)" :key="`photo-${selectedCheckpointDraft.id}-${index}`">
                        <div class="col-6">
                          <a :href="photoUrl(photo.photo_path)" target="_blank" rel="noopener" class="d-block">
                            <img :src="photoUrl(photo.photo_path)" class="img-fluid rounded-3 border patrol-preview" alt="Foto patroli checkpoint">
                          </a>
                        </div>
                      </template>
                    </div>
                  </div>
                </template>
                <template x-if="!selectedCheckpointPhotoLog">
                  <div class="text-muted small">Belum ada foto check-in pada titik ini.</div>
                </template>
              </div>
            </div>
          </template>

          <template x-if="selectedCheckpointDraft && !canEditLayout">
            <div class="d-grid gap-3">
              <div class="patrol-mini-stat bg-light">
                <div class="text-muted small">Nama Titik</div>
                <div class="fw-semibold" x-text="selectedCheckpointDraft.name"></div>
              </div>
              <div class="patrol-mini-stat bg-light">
                <div class="text-muted small">Area</div>
                <div class="fw-semibold" x-text="selectedCheckpointDraft.area || '-'"></div>
              </div>
              <div class="patrol-mini-stat bg-light">
                <div class="text-muted small">Barcode</div>
                <div class="fw-semibold" x-text="selectedCheckpointDraft.barcode_value || '-'"></div>
              </div>
              <div class="patrol-mini-stat bg-light">
                <div class="text-muted small">Koordinat GPS</div>
                <div class="fw-semibold" x-text="`${Number(selectedCheckpointDraft.lat || 0).toFixed(6)}, ${Number(selectedCheckpointDraft.lng || 0).toFixed(6)}`"></div>
              </div>
              <div class="patrol-mini-stat bg-light">
                <div class="text-muted small">Radius</div>
                <div class="fw-semibold" x-text="`${selectedCheckpointDraft.radius_m || 10} meter`"></div>
              </div>
              <div class="patrol-mini-stat bg-light">
                <div class="text-muted small">Posisi Marker</div>
                <div class="fw-semibold" x-text="`${selectedCheckpointDraft.map_x ?? 0}% / ${selectedCheckpointDraft.map_y ?? 0}%`"></div>
              </div>
            </div>
          </template>

          <div class="alert alert-light border mt-3 mb-0 small">
            <div class="fw-semibold mb-1">Tips</div>
            <template x-if="canEditLayout">
              <div>Geser marker di denah atau ubah angka X/Y untuk presisi.</div>
            </template>
            <template x-if="!canEditLayout">
              <div>Dashboard ini bersifat baca saja untuk compliance.</div>
            </template>
            <div>Barcode yang tampil di bawah marker dipakai saat scan checkpoint.</div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm no-lift mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-semibold mb-0">Daftar Titik</h6>
            <span class="badge text-bg-light border text-dark" x-text="checkpointDrafts.length"></span>
          </div>

          <div class="d-grid gap-2">
            <template x-for="checkpoint in checkpointDrafts" :key="`checkpoint-list-${checkpoint.id}`">
              <button type="button" class="text-start patrol-checkpoint-card" :class="selectedCheckpointId === checkpoint.id ? 'is-selected' : ''" @click="selectCheckpoint(checkpoint.id)">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div>
                    <div class="fw-semibold" x-text="checkpoint.code + ' - ' + checkpoint.name"></div>
                    <div class="text-muted small" x-text="checkpoint.area || '-'"></div>
                    <div class="text-primary small" x-text="'Barcode: ' + (checkpoint.barcode_value || '-')"></div>
                  </div>
                  <span class="badge text-bg-secondary" x-text="`${Number(checkpoint.map_x || 0).toFixed(1)} / ${Number(checkpoint.map_y || 0).toFixed(1)}`"></span>
                </div>
              </button>
            </template>
          </div>
        </div>
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
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/patrol.js?v=<?= filemtime(FCPATH . 'js/patrol.js') ?>"></script>
<?= $this->endSection() ?>
