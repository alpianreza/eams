<?= $this->extend('layouts/main') ?>

<?php
$bootUsers = array_map(static function (array $user): array {
  $pageAccess = normalize_page_access($user['page_access'] ?? '');
  return [
    'id'         => (int) ($user['id'] ?? 0),
    'name'       => (string) ($user['name'] ?? ''),
    'username'   => (string) ($user['username'] ?? ''),
    'wa_number'  => (string) ($user['wa_number'] ?? ''),
    'role'       => (string) ($user['role'] ?? ''),
    'permission' => (string) ($user['permission'] ?? ''),
    'status'     => (string) ($user['status'] ?? ''),
    'photo'      => (string) ($user['photo'] ?? ''),
    'page_access_count' => count($pageAccess),
  ];
}, $users ?? []);

$bootRoles = array_map(static function (array $role): array {
  return [
    'name'  => (string) ($role['name'] ?? ''),
    'label' => (string) ($role['label'] ?? ''),
  ];
}, $roles ?? []);
?>

<?= $this->section('content') ?>

<script>
  window.USER_BOOT = <?= json_encode([
    'users' => $bootUsers,
    'roles' => $bootRoles,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<div class="users-page" x-data="userManagement(window.USER_BOOT)">
  <section class="card border-0 shadow-sm no-lift mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="inventory-kicker mb-1">Manajemen User</p>
        <h5 class="mb-1 fw-bold">Kelola user, role, halaman, dan mode akses</h5>
        <p class="text-muted mb-0">
          Cari user lebih cepat, tambah role baru dari halaman ini, dan jaga struktur akses tetap rapi.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <a href="<?= base_url('users/create') ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-person-plus"></i>
          Tambah User
        </a>
        <a href="#role-manager" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-tags"></i>
          Tambah Role
        </a>
      </div>
    </div>
  </section>

  <section class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm no-lift h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Total User</div>
          <div class="fs-3 fw-bold" x-text="users.length"></div>
          <div class="text-muted small">Semua user yang tersimpan di sistem</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm no-lift h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Role Aktif</div>
          <div class="fs-3 fw-bold" x-text="roles.length"></div>
          <div class="text-muted small">Role master yang tersedia untuk user</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm no-lift h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">User Aktif</div>
          <div class="fs-3 fw-bold" x-text="activeUsersCount()"></div>
          <div class="text-muted small">User dengan status aktif</div>
        </div>
      </div>
    </div>
  </section>

  <section class="row g-3">
    <div class="col-xl-8">
      <div class="card border-0 shadow-sm no-lift h-100">
        <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <h6 class="mb-0 fw-semibold">Daftar User</h6>
            <div class="text-muted small">Filter data secara cepat dengan Alpine.</div>
          </div>

          <div class="d-flex flex-wrap gap-2 align-items-center">
            <input
              type="search"
              class="form-control form-control-sm"
              placeholder="Cari nama, username, role..."
              x-model.debounce.250ms="query">

            <select class="form-select form-select-sm" x-model="roleFilter" style="min-width: 160px;">
              <option value="">Semua Role</option>
              <template x-for="role in roles" :key="role.name">
                <option :value="role.name" x-text="role.label"></option>
              </template>
            </select>

            <select class="form-select form-select-sm" x-model="statusFilter" style="min-width: 150px;">
              <option value="">Semua Status</option>
              <option value="active">Aktif</option>
              <option value="inactive">Nonaktif</option>
            </select>
          </div>
        </div>

        <div class="card-body pt-0">
          <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0 users-table">
              <thead class="table-dark">
                <tr>
                  <th style="width:3.5%">No</th>
                  <th style="width:15%">Nama</th>
                  <th style="width:10%">Username</th>
                  <th class="d-none d-md-table-cell" style="width:14%">WhatsApp</th>
                  <th style="width:10%">Role</th>
                  <th style="width:10%">Permission</th>
                  <th style="width:8%">Hal.</th>
                  <th style="width:7%">Status</th>
                  <th style="width:15%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="(user, index) in filteredUsers()" :key="user.id">
                  <tr>
                    <td x-text="index + 1"></td>
                    <td>
                      <div class="fw-semibold" x-text="user.name"></div>
                    </td>
                    <td x-text="user.username"></td>
                    <td class="d-none d-md-table-cell">
                      <span x-show="user.wa_number" class="text-success fw-semibold" x-text="user.wa_number"></span>
                      <span x-show="!user.wa_number" class="text-danger">-</span>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border" x-text="displayRole(user.role)"></span>
                    </td>
                    <td>
                      <span class="badge" :class="user.permission === 'write' ? 'bg-primary' : 'bg-secondary'" x-text="user.permission"></span>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark border">
                        <span x-text="user.page_access_count"></span>
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-success" x-show="user.status === 'active'">Aktif</span>
                      <span class="badge bg-secondary" x-show="user.status !== 'active'">Off</span>
                    </td>
                    <td>
                      <div class="d-flex gap-1">
                        <a class="btn btn-sm btn-warning" :href="`/users/edit/${user.id}`">Edit</a>

                        <form method="post" :action="user.status === 'active' ? `/users/deactivate/${user.id}` : `/users/activate/${user.id}`">
                          <?= csrf_field() ?>
                          <button type="submit"
                            class="btn btn-sm"
                            :class="user.status === 'active' ? 'btn-danger' : 'btn-success'"
                            x-text="user.status === 'active' ? 'Nonaktifkan' : 'Aktifkan'"></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                </template>
                <tr x-show="filteredUsers().length === 0">
                  <td colspan="9" class="text-center text-muted py-4">
                    Tidak ada user yang cocok dengan filter.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card border-0 shadow-sm no-lift mb-3">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">Role Manager</h6>

          <div class="mb-3">
            <label class="form-label small text-muted">Cari Role</label>
            <input type="search" class="form-control form-control-sm" placeholder="Filter role..." x-model.debounce.200ms="roleQuery">
          </div>

          <div class="d-flex flex-wrap gap-2 mb-3">
            <template x-for="role in filteredRoles()" :key="role.name">
              <span class="badge bg-light text-dark border" x-text="role.label"></span>
            </template>
          </div>

          <form method="post" action="<?= base_url('users/roles/store') ?>" class="border-top pt-3" id="role-manager">
            <?= csrf_field() ?>
            <label class="form-label">Tambah Role Baru</label>
            <input
              type="text"
              name="name"
              class="form-control mb-2"
              placeholder="contoh: security_supervisor"
              required>
            <small class="text-muted d-block mb-3">Role baru akan tersimpan dan langsung muncul di form user.</small>
            <button class="btn btn-primary w-100">
              <i class="bi bi-plus-lg"></i>
              Simpan Role
            </button>
          </form>
        </div>
      </div>

      <div class="card border-0 shadow-sm no-lift">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">Panduan Cepat</h6>
          <ul class="small text-muted mb-0 ps-3">
            <li>Tambah role dari panel kanan.</li>
            <li>Gunakan role baru di form tambah/edit user.</li>
            <li>Role menentukan batas dasar, centang halaman menentukan menu yang tampil.</li>
            <li>Read Only bisa buka halaman, tapi aksi simpan/edit tetap ditahan.</li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  .users-page .users-table {
    table-layout: fixed;
  }
  .users-page .users-table th {
    font-size: 0.72rem;
    letter-spacing: 0;
  }
  .users-page .users-table td {
    font-size: 0.8rem;
    white-space: normal;
  }
  .users-page .users-table td:last-child {
    white-space: nowrap;
  }
  .users-page .users-table .badge {
    font-size: 0.68rem;
    font-weight: 500;
  }
  .users-page .users-table .btn-sm {
    font-size: 0.72rem;
    padding: 0.2rem 0.4rem;
  }
  @media (max-width: 1200px) {
    .users-page .users-table th:nth-child(4),
    .users-page .users-table td:nth-child(4) {
      display: none;
    }
  }
  @media (max-width: 768px) {
    .users-page .users-table th,
    .users-page .users-table td {
      font-size: 0.7rem;
      padding: 0.35rem 0.25rem;
    }
    .users-page .users-table th:first-child,
    .users-page .users-table th:nth-child(4),
    .users-page .users-table th:nth-child(7),
    .users-page .users-table td:first-child,
    .users-page .users-table td:nth-child(4),
    .users-page .users-table td:nth-child(7) {
      display: none;
    }
    .users-page .users-table .btn-sm {
      font-size: 0.6rem;
      padding: 0.12rem 0.3rem;
    }
    .users-page .users-table .badge {
      font-size: 0.55rem;
    }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/users-management.js?v=' . filemtime(FCPATH . 'js/users-management.js')) ?>"></script>
<?= $this->endSection() ?>
