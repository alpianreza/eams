<?php

namespace App\Controllers;

use Config\Database;

class PatrolController extends BaseController
{
  protected $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  private function currentRole(): string
  {
    return (string) (session()->get('role') ?? '');
  }

  private function currentUserId(): int
  {
    return (int) (session()->get('user_id') ?? 0);
  }

  private function canAccess(): bool
  {
    return in_array($this->currentRole(), ['security', 'compliance', 'admin'], true);
  }

  private function canViewDashboard(): bool
  {
    return in_array($this->currentRole(), ['compliance', 'admin'], true);
  }

  private function json(array $payload, int $statusCode = 200)
  {
    return $this->response->setStatusCode($statusCode)->setJSON($payload);
  }

  private function now(): string
  {
    return date('Y-m-d H:i:s');
  }

  private function today(): string
  {
    return date('Y-m-d');
  }

  private function normalizeBarcode(string $value): string
  {
    $value = strtoupper(trim($value));
    return preg_replace('/\s+/', '', $value) ?? '';
  }

  private function loadRoutes(): array
  {
    $rows = $this->db->table('patrol_routes')
      ->where('active', 1)
      ->orderBy('sort_order', 'ASC')
      ->orderBy('name', 'ASC')
      ->get()
      ->getResultArray();

    foreach ($rows as &$row) {
      $row['checkpoints'] = $this->loadRouteCheckpoints((int) ($row['id'] ?? 0));
    }

    return $rows;
  }

  private function loadRouteCheckpoints(int $routeId): array
  {
    return $this->db->table('patrol_route_checkpoints route_cp')
      ->select('route_cp.route_order, cp.id, cp.code, cp.name, cp.area, cp.barcode_value, cp.lat, cp.lng, cp.radius_m, cp.map_x, cp.map_y, cp.active')
      ->join('patrol_checkpoints cp', 'cp.id = route_cp.checkpoint_id')
      ->where('route_cp.route_id', $routeId)
      ->orderBy('route_cp.route_order', 'ASC')
      ->get()
      ->getResultArray();
  }

  private function loadCheckpointCatalog(): array
  {
    return $this->db->table('patrol_checkpoints')
      ->where('active', 1)
      ->orderBy('code', 'ASC')
      ->get()
      ->getResultArray();
  }

  private function loadActiveLayout(): ?array
  {
    return $this->db->table('patrol_layouts')
      ->where('active', 1)
      ->orderBy('id', 'DESC')
      ->get()
      ->getRowArray() ?: null;
  }

  private function layoutImageUrl(?array $layout): ?string
  {
    $imagePath = trim((string) ($layout['image_path'] ?? ''));
    if ($imagePath === '') {
      return null;
    }

    return '/' . ltrim($imagePath, '/');
  }

  private function loadActiveSession(): ?array
  {
    $builder = $this->db->table('patrol_sessions session');
    $builder->select('session.*, route.name as route_name, route.slug as route_slug, user.name as started_by_name, user.role as started_by_role');
    $builder->join('patrol_routes route', 'route.id = session.route_id');
    $builder->join('users user', 'user.id = session.started_by', 'left');
    $builder->where('session.patrol_date', $this->today());
    $builder->where('session.status', 'active');
    $builder->where('session.started_by', $this->currentUserId());

    return $builder->orderBy('session.id', 'DESC')->get()->getRowArray() ?: null;
  }

  private function loadRecentSessions(): array
  {
    $builder = $this->db->table('patrol_sessions session');
    $builder->select('session.*, route.name as route_name, route.slug as route_slug, user.name as started_by_name');
    $builder->join('patrol_routes route', 'route.id = session.route_id');
    $builder->join('users user', 'user.id = session.started_by', 'left');
    $builder->where('session.patrol_date', $this->today());

    if (!$this->canViewDashboard()) {
      $builder->where('session.started_by', $this->currentUserId());
    }

    return $builder->orderBy('session.id', 'DESC')->limit(8)->get()->getResultArray();
  }

  private function loadRecentLogs(): array
  {
    $builder = $this->db->table('patrol_logs log');
    $builder->select('log.*, session.patrol_date, session.status as session_status, route.name as route_name, cp.code, cp.name as checkpoint_name, cp.area');
    $builder->join('patrol_sessions session', 'session.id = log.session_id');
    $builder->join('patrol_routes route', 'route.id = log.route_id');
    $builder->join('patrol_checkpoints cp', 'cp.id = log.checkpoint_id');
    $builder->where('session.patrol_date', $this->today());

    if (!$this->canViewDashboard()) {
      $builder->where('log.checked_by', $this->currentUserId());
    }

    return $builder->orderBy('log.checked_at', 'DESC')->limit(12)->get()->getResultArray();
  }

  private function loadAdminStats(): array
  {
    $today = $this->today();
    $base = $this->db->table('patrol_sessions')->where('patrol_date', $today);

    if (!$this->canViewDashboard()) {
      $base->where('started_by', $this->currentUserId());
    }

    $sessions = (clone $base)->countAllResults();
    $active = (clone $base)->where('status', 'active')->countAllResults();
    $completed = (clone $base)->where('status', 'completed')->countAllResults();

    $logsBuilder = $this->db->table('patrol_logs log');
    $logsBuilder->join('patrol_sessions session', 'session.id = log.session_id');
    $logsBuilder->where('session.patrol_date', $today);
    if (!$this->canViewDashboard()) {
      $logsBuilder->where('log.checked_by', $this->currentUserId());
    }

    $issueCount = (clone $logsBuilder)->where('log.status', 'not_ok')->countAllResults();
    $checkedCount = (clone $logsBuilder)->countAllResults();

    return [
      'sessions' => (int) $sessions,
      'active' => (int) $active,
      'completed' => (int) $completed,
      'checks' => (int) $checkedCount,
      'issues' => (int) $issueCount,
    ];
  }

  private function buildSessionPayload(array $sessionRow): array
  {
    $sessionId = (int) ($sessionRow['id'] ?? 0);
    $routeId = (int) ($sessionRow['route_id'] ?? 0);

    $routeCheckpoints = $this->loadRouteCheckpoints($routeId);
    $logs = $this->db->table('patrol_logs log')
      ->select('log.*, cp.code, cp.name, cp.area, cp.barcode_value, cp.lat, cp.lng, cp.radius_m, cp.map_x, cp.map_y')
      ->join('patrol_checkpoints cp', 'cp.id = log.checkpoint_id')
      ->where('log.session_id', $sessionId)
      ->orderBy('log.checked_at', 'ASC')
      ->get()
      ->getResultArray();

    $photoMap = [];
    $logIds = array_values(array_filter(array_map(static fn ($row) => (int) ($row['id'] ?? 0), $logs)));
    if (!empty($logIds)) {
      $photoRows = $this->db->table('patrol_log_photos')
        ->whereIn('log_id', $logIds)
        ->orderBy('sort_order', 'ASC')
        ->get()
        ->getResultArray();

      foreach ($photoRows as $photoRow) {
        $photoMap[(int) ($photoRow['log_id'] ?? 0)][] = $photoRow;
      }
    }

    $logMap = [];
    foreach ($logs as $index => $log) {
      $log['photos'] = $photoMap[(int) ($log['id'] ?? 0)] ?? [];
      $log['photo_count'] = count($log['photos']);
      $logs[$index] = $log;
      $logMap[(int) ($log['checkpoint_id'] ?? 0)] = $log;
    }

    $checkpoints = [];
    foreach ($routeCheckpoints as $checkpoint) {
      $checkpointId = (int) ($checkpoint['id'] ?? 0);
      $log = $logMap[$checkpointId] ?? null;
      $checkpoints[] = [
        'id' => $checkpointId,
        'code' => (string) ($checkpoint['code'] ?? ''),
        'name' => (string) ($checkpoint['name'] ?? ''),
        'area' => (string) ($checkpoint['area'] ?? ''),
        'barcode_value' => (string) ($checkpoint['barcode_value'] ?? ''),
        'lat' => isset($checkpoint['lat']) ? (float) $checkpoint['lat'] : null,
        'lng' => isset($checkpoint['lng']) ? (float) $checkpoint['lng'] : null,
        'radius_m' => (int) ($checkpoint['radius_m'] ?? 10),
        'map_x' => isset($checkpoint['map_x']) ? (float) $checkpoint['map_x'] : null,
        'map_y' => isset($checkpoint['map_y']) ? (float) $checkpoint['map_y'] : null,
        'route_order' => (int) ($checkpoint['route_order'] ?? 1),
        'checked' => $log !== null,
        'log' => $log,
      ];
    }

    $checkedCount = count($logs);
    $nextCheckpoint = $checkpoints[$checkedCount] ?? null;
    $progress = [
      'checked' => $checkedCount,
      'total' => count($checkpoints),
      'percent' => count($checkpoints) > 0 ? (int) round(($checkedCount / count($checkpoints)) * 100) : 0,
    ];

    return [
      'session' => [
        'id' => $sessionId,
        'route_id' => $routeId,
        'route_name' => (string) ($sessionRow['route_name'] ?? ''),
        'route_slug' => (string) ($sessionRow['route_slug'] ?? ''),
        'patrol_date' => (string) ($sessionRow['patrol_date'] ?? $this->today()),
        'started_at' => (string) ($sessionRow['started_at'] ?? ''),
        'ended_at' => (string) ($sessionRow['ended_at'] ?? ''),
        'status' => (string) ($sessionRow['status'] ?? 'active'),
        'started_by_name' => (string) ($sessionRow['started_by_name'] ?? ''),
      ],
      'checkpoints' => $checkpoints,
      'logs' => $logs,
      'nextCheckpoint' => $nextCheckpoint,
      'progress' => $progress,
    ];
  }

  private function buildLandingBoot(): array
  {
    $routes = $this->loadRoutes();
    $activeSession = $this->loadActiveSession();
    $layout = $this->loadActiveLayout();

    $activeSessionPayload = $activeSession ? $this->buildSessionPayload($activeSession) : null;

    return [
      'today' => $this->today(),
      'user' => [
        'id' => $this->currentUserId(),
        'name' => (string) (session()->get('name') ?? ''),
        'role' => $this->currentRole(),
      ],
      'routes' => $routes,
      'checkpoints' => $this->loadCheckpointCatalog(),
      'layout' => [
        'id' => (int) ($layout['id'] ?? 0),
        'name' => (string) ($layout['name'] ?? 'Layout Utama'),
        'image_path' => (string) ($layout['image_path'] ?? ''),
        'image_url' => $this->layoutImageUrl($layout),
      ],
      'activeSession' => $activeSessionPayload,
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
    ];
  }

  private function buildDashboardBoot(): array
  {
    $layout = $this->loadActiveLayout();
    return [
      'today' => $this->today(),
      'user' => [
        'id' => $this->currentUserId(),
        'name' => (string) (session()->get('name') ?? ''),
        'role' => $this->currentRole(),
      ],
      'routes' => $this->loadRoutes(),
      'checkpoints' => $this->loadCheckpointCatalog(),
      'layout' => [
        'id' => (int) ($layout['id'] ?? 0),
        'name' => (string) ($layout['name'] ?? 'Layout Utama'),
        'image_path' => (string) ($layout['image_path'] ?? ''),
        'image_url' => $this->layoutImageUrl($layout),
      ],
      'adminStats' => $this->loadAdminStats(),
      'recentSessions' => $this->loadRecentSessions(),
      'recentLogs' => $this->loadRecentLogs(),
      'csrfName' => csrf_token(),
      'csrfHash' => csrf_hash(),
    ];
  }

  private function ensurePatrolAccess()
  {
    if ($this->canAccess()) {
      return null;
    }

    return redirect()->to('/home')->with('error', 'Anda tidak memiliki akses ke menu patroli.');
  }

  private function ensureDashboardAccess()
  {
    if ($this->canViewDashboard()) {
      return null;
    }

    return redirect()->to('/patrol')->with('error', 'Dashboard patroli hanya untuk admin dan compliance.');
  }

  public function index()
  {
    if ($redirect = $this->ensurePatrolAccess()) {
      return $redirect;
    }

    return view('patrol/index', [
      'title' => 'Patrol Security',
      'boot' => $this->buildLandingBoot(),
    ]);
  }

  public function dashboard()
  {
    if ($redirect = $this->ensureDashboardAccess()) {
      return $redirect;
    }

    return view('patrol/dashboard', [
      'title' => 'Patrol Dashboard',
      'boot' => $this->buildDashboardBoot(),
    ]);
  }

  public function startSession()
  {
    if ($redirect = $this->ensurePatrolAccess()) {
      return $redirect;
    }

    $routeId = (int) $this->request->getPost('route_id');
    if ($routeId <= 0) {
      return $this->json([
        'ok' => false,
        'message' => 'Pilih rute patroli terlebih dahulu.',
      ], 422);
    }

    $route = $this->db->table('patrol_routes')
      ->where('id', $routeId)
      ->where('active', 1)
      ->get()
      ->getRowArray();

    if (!$route) {
      return $this->json([
        'ok' => false,
        'message' => 'Rute patroli tidak ditemukan.',
      ], 404);
    }

    $existingSession = $this->loadActiveSession();
    if ($existingSession && (int) ($existingSession['route_id'] ?? 0) === $routeId) {
      return $this->json([
        'ok' => true,
        'message' => 'Sesi patroli aktif dilanjutkan.',
        'payload' => $existingSession,
        'csrfHash' => csrf_hash(),
      ]);
    }

    if ($existingSession) {
      return $this->json([
        'ok' => false,
        'message' => 'Masih ada sesi patroli aktif. Selesaikan dulu sebelum memulai rute baru.',
      ], 409);
    }

    $checkpointTotal = $this->db->table('patrol_route_checkpoints')
      ->where('route_id', $routeId)
      ->countAllResults();

    $sessionData = [
      'route_id' => $routeId,
      'patrol_date' => $this->today(),
      'started_by' => $this->currentUserId(),
      'started_at' => $this->now(),
      'status' => 'active',
      'total_checkpoints' => $checkpointTotal,
      'checked_count' => 0,
      'issue_count' => 0,
      'created_at' => $this->now(),
    ];

    $this->db->table('patrol_sessions')->insert($sessionData);
    $sessionId = (int) $this->db->insertID();
    $session = $this->db->table('patrol_sessions session')
      ->select('session.*, route.name as route_name, route.slug as route_slug, user.name as started_by_name')
      ->join('patrol_routes route', 'route.id = session.route_id')
      ->join('users user', 'user.id = session.started_by', 'left')
      ->where('session.id', $sessionId)
      ->get()
      ->getRowArray();

    return $this->json([
      'ok' => true,
      'message' => 'Sesi patroli dimulai.',
      'payload' => $this->buildSessionPayload($session ?? $sessionData),
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function scanCheckpoint()
  {
    if ($redirect = $this->ensurePatrolAccess()) {
      return $redirect;
    }

    $sessionId = (int) $this->request->getPost('session_id');
    $barcode = $this->normalizeBarcode((string) $this->request->getPost('barcode'));
    $status = strtolower(trim((string) $this->request->getPost('status', FILTER_UNSAFE_RAW)));
    $status = in_array($status, ['ok', 'not_ok'], true) ? $status : 'ok';
    $note = trim((string) $this->request->getPost('note'));
    $latitude = $this->request->getPost('latitude');
    $longitude = $this->request->getPost('longitude');

    if ($sessionId <= 0) {
      return $this->json([
        'ok' => false,
        'message' => 'Sesi patroli belum dimulai.',
      ], 422);
    }

    if ($barcode === '') {
      return $this->json([
        'ok' => false,
        'message' => 'Barcode checkpoint wajib di-scan.',
      ], 422);
    }

    $session = $this->db->table('patrol_sessions session')
      ->select('session.*, route.name as route_name, route.slug as route_slug, user.name as started_by_name')
      ->join('patrol_routes route', 'route.id = session.route_id')
      ->join('users user', 'user.id = session.started_by', 'left')
      ->where('session.id', $sessionId)
      ->get()
      ->getRowArray();

    if (!$session) {
      return $this->json([
        'ok' => false,
        'message' => 'Sesi patroli tidak ditemukan.',
      ], 404);
    }

    if ((int) $session['started_by'] !== $this->currentUserId() && !$this->canViewDashboard()) {
      return $this->json([
        'ok' => false,
        'message' => 'Anda tidak boleh mengubah sesi patroli ini.',
      ], 403);
    }

    if (($session['status'] ?? '') !== 'active') {
      return $this->json([
        'ok' => false,
        'message' => 'Sesi patroli ini sudah selesai.',
      ], 409);
    }

    $payload = $this->buildSessionPayload($session);
    $nextCheckpoint = $payload['nextCheckpoint'] ?? null;

    if (!$nextCheckpoint) {
      return $this->json([
        'ok' => false,
        'message' => 'Semua checkpoint sudah selesai dicheck.',
      ], 409);
    }

    $expectedBarcode = $this->normalizeBarcode((string) ($nextCheckpoint['barcode_value'] ?? $nextCheckpoint['code'] ?? ''));
    if ($barcode !== $expectedBarcode) {
      return $this->json([
        'ok' => false,
        'message' => 'Urutan checkpoint belum sesuai. Berikutnya: ' . ($nextCheckpoint['code'] ?? '-'),
        'expected' => $nextCheckpoint,
      ], 422);
    }

    $photos = $this->request->getFileMultiple('photos');
    $photos = is_array($photos) ? $photos : [];
    $photos = array_values(array_filter($photos, static function ($file) {
      return $file && $file->isValid() && !$file->hasMoved();
    }));

    if (empty($photos)) {
      return $this->json([
        'ok' => false,
        'message' => 'Foto bukti wajib diambil dari kamera.',
      ], 422);
    }

    if (trim((string) $latitude) === '' || trim((string) $longitude) === '') {
      return $this->json([
        'ok' => false,
        'message' => 'Lokasi GPS belum terbaca.',
      ], 422);
    }

    $currentLat = (float) $latitude;
    $currentLng = (float) $longitude;
    $targetLat = (float) ($nextCheckpoint['lat'] ?? 0);
    $targetLng = (float) ($nextCheckpoint['lng'] ?? 0);
    $radius = (int) ($nextCheckpoint['radius_m'] ?? 10);
    $distance = $this->distanceMeters($currentLat, $currentLng, $targetLat, $targetLng);

    if ($distance > $radius) {
      return $this->json([
        'ok' => false,
        'message' => sprintf(
          'Anda masih di luar radius checkpoint. Jarak saat ini %.1f meter, radius maksimal %d meter.',
          $distance,
          $radius
        ),
        'distance' => $distance,
      ], 422);
    }

    $photoPaths = [];
    foreach ($photos as $photo) {
      $photoPath = $this->storePatrolPhoto($photo, $session, $nextCheckpoint);
      if ($photoPath !== '') {
        $photoPaths[] = $photoPath;
      }
    }

    if (empty($photoPaths)) {
      return $this->json([
        'ok' => false,
        'message' => 'Gagal menyimpan foto patroli.',
      ], 500);
    }

    $now = $this->now();
    $this->db->table('patrol_logs')->insert([
      'session_id' => $sessionId,
      'route_id' => (int) ($session['route_id'] ?? 0),
      'checkpoint_id' => (int) ($nextCheckpoint['id'] ?? 0),
      'checked_by' => $this->currentUserId(),
      'barcode_value' => $barcode,
      'status' => $status,
      'note' => $note !== '' ? $note : null,
      'latitude' => $currentLat,
      'longitude' => $currentLng,
      'distance_m' => round($distance, 2),
      'photo_path' => $photoPaths[0],
      'checked_at' => $now,
      'created_at' => $now,
    ]);

    $logId = (int) $this->db->insertID();
    foreach ($photoPaths as $index => $path) {
      $this->db->table('patrol_log_photos')->insert([
        'log_id' => $logId,
        'photo_path' => $path,
        'sort_order' => $index + 1,
        'created_at' => $now,
      ]);
    }

    $checkedCount = $this->db->table('patrol_logs')
      ->where('session_id', $sessionId)
      ->countAllResults();

    $issueCount = $this->db->table('patrol_logs')
      ->where('session_id', $sessionId)
      ->where('status', 'not_ok')
      ->countAllResults();

    $sessionData = [
      'checked_count' => $checkedCount,
      'issue_count' => $issueCount,
      'status' => $checkedCount >= (int) ($session['total_checkpoints'] ?? 0) ? 'completed' : 'active',
      'ended_at' => $checkedCount >= (int) ($session['total_checkpoints'] ?? 0) ? $now : null,
      'updated_at' => $now,
    ];

    $this->db->table('patrol_sessions')->where('id', $sessionId)->update($sessionData);

    $updatedSession = $this->db->table('patrol_sessions session')
      ->select('session.*, route.name as route_name, route.slug as route_slug, user.name as started_by_name')
      ->join('patrol_routes route', 'route.id = session.route_id')
      ->join('users user', 'user.id = session.started_by', 'left')
      ->where('session.id', $sessionId)
      ->get()
      ->getRowArray();

    return $this->json([
      'ok' => true,
      'message' => sprintf('Check-in %s berhasil.', $nextCheckpoint['code'] ?? 'checkpoint'),
      'payload' => $this->buildSessionPayload($updatedSession ?: $session),
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function cancelSession()
  {
    if ($redirect = $this->ensurePatrolAccess()) {
      return $redirect;
    }

    $sessionId = (int) $this->request->getPost('session_id');
    if ($sessionId <= 0) {
      return $this->json([
        'ok' => false,
        'message' => 'Sesi patroli tidak ditemukan.',
      ], 422);
    }

    $session = $this->db->table('patrol_sessions')
      ->where('id', $sessionId)
      ->get()
      ->getRowArray();

    if (!$session) {
      return $this->json([
        'ok' => false,
        'message' => 'Sesi patroli tidak ditemukan.',
      ], 404);
    }

    if ((int) $session['started_by'] !== $this->currentUserId() && !$this->canViewDashboard()) {
      return $this->json([
        'ok' => false,
        'message' => 'Anda tidak boleh membatalkan sesi patroli ini.',
      ], 403);
    }

    if (($session['status'] ?? '') !== 'active') {
      return $this->json([
        'ok' => false,
        'message' => 'Sesi patroli sudah tidak aktif.',
      ], 409);
    }

    $this->db->table('patrol_sessions')
      ->where('id', $sessionId)
      ->update([
        'status' => 'canceled',
        'ended_at' => $this->now(),
        'updated_at' => $this->now(),
      ]);

    return $this->json([
      'ok' => true,
      'message' => 'Sesi patroli dibatalkan.',
      'payload' => null,
      'csrfHash' => csrf_hash(),
    ]);
  }

  public function saveLayout()
  {
    if ($redirect = $this->ensureDashboardAccess()) {
      return $redirect;
    }

    $name = trim((string) $this->request->getPost('name'));
    $checkpointsJson = (string) $this->request->getPost('checkpoints_json');
    $checkpoints = json_decode($checkpointsJson, true);

    if (!is_array($checkpoints)) {
      return $this->json([
        'ok' => false,
        'message' => 'Data titik patroli tidak valid.',
      ], 422);
    }

    if ($name === '') {
      $name = 'Layout Utama';
    }

    $layout = $this->loadActiveLayout();
    $imagePath = $layout['image_path'] ?? null;
    $imageFile = $this->request->getFile('layout_image');

    if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
      $directory = FCPATH . 'uploads/patrol/layouts/' . date('Y/m');
      if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
      }

      $extension = strtolower($imageFile->guessExtension() ?: $imageFile->getClientExtension() ?: 'jpg');
      $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
      $fileName = sprintf('layout-%s-%s.%s', date('Ymd-His'), bin2hex(random_bytes(3)), $extension);
      $imageFile->move($directory, $fileName, true);
      $imagePath = 'uploads/patrol/layouts/' . date('Y/m') . '/' . $fileName;
    }

    if ($layout) {
      $this->db->table('patrol_layouts')->where('id', (int) $layout['id'])->update([
        'name' => $name,
        'image_path' => $imagePath,
        'updated_at' => $this->now(),
      ]);
    } else {
      $this->db->table('patrol_layouts')->insert([
        'name' => $name,
        'image_path' => $imagePath,
        'active' => 1,
        'created_at' => $this->now(),
      ]);
    }

    foreach ($checkpoints as $row) {
      $checkpointId = (int) ($row['id'] ?? 0);
      if ($checkpointId <= 0) {
        continue;
      }

      $update = [
        'map_x' => isset($row['map_x']) ? (float) $row['map_x'] : null,
        'map_y' => isset($row['map_y']) ? (float) $row['map_y'] : null,
        'barcode_value' => trim((string) ($row['barcode_value'] ?? '')),
        'name' => trim((string) ($row['name'] ?? '')),
        'area' => trim((string) ($row['area'] ?? '')),
        'lat' => isset($row['lat']) && $row['lat'] !== '' ? (float) $row['lat'] : null,
        'lng' => isset($row['lng']) && $row['lng'] !== '' ? (float) $row['lng'] : null,
        'radius_m' => isset($row['radius_m']) && $row['radius_m'] !== '' ? (int) $row['radius_m'] : null,
        'updated_at' => $this->now(),
      ];

      $this->db->table('patrol_checkpoints')->where('id', $checkpointId)->update($update);
    }

    return $this->json([
      'ok' => true,
      'message' => 'Layout patroli berhasil disimpan.',
      'payload' => [
        'layout' => [
          'id' => (int) ($layout['id'] ?? 0),
          'name' => $name,
          'image_path' => (string) $imagePath,
          'image_url' => $this->layoutImageUrl([
            'image_path' => $imagePath,
          ]),
        ],
        'checkpoints' => $this->loadCheckpointCatalog(),
      ],
      'csrfHash' => csrf_hash(),
    ]);
  }

  private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
  {
    $earthRadius = 6371000;
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);

    $deltaLat = $lat2 - $lat1;
    $deltaLng = $lng2 - $lng1;

    $a = sin($deltaLat / 2) ** 2
      + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
  }

  private function storePatrolPhoto($photo, array $session, array $checkpoint): string
  {
    $directory = FCPATH . 'uploads/patrol/' . date('Y/m');
    if (!is_dir($directory)) {
      mkdir($directory, 0775, true);
    }

    $extension = strtolower($photo->guessExtension() ?: $photo->getClientExtension() ?: 'jpg');
    $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';

    $fileName = sprintf(
      'patrol-%s-%s-%s-%s.%s',
      date('Ymd-His'),
      $this->currentUserId(),
      $checkpoint['code'] ?? 'cp',
      bin2hex(random_bytes(3)),
      $extension
    );

    $photo->move($directory, $fileName, true);

    return 'uploads/patrol/' . date('Y/m') . '/' . $fileName;
  }
}
