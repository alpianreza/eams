<?php

if (! function_exists('access_menu_catalog')) {
  function access_menu_catalog(): array
  {
    return [
      'home' => [
        'label' => 'Home',
        'group' => 'Umum',
        'path' => '/home',
      ],
      'patrol_daily' => [
        'label' => 'Patrol Harian',
        'group' => 'Security',
        'path' => '/patrol',
      ],
      'patrol_dashboard' => [
        'label' => 'Patrol Dashboard',
        'group' => 'Security',
        'path' => '/patrol/dashboard',
      ],
      'it_center' => [
        'label' => 'IT Center',
        'group' => 'IT Asset',
        'path' => '/it',
      ],
      'dashboard_it' => [
        'label' => 'Dashboard IT',
        'group' => 'IT Asset',
        'path' => '/dashboard-it',
      ],
      'it_assets' => [
        'label' => 'Data Asset IT',
        'group' => 'IT Asset',
        'path' => '/it-assets',
      ],
      'device_control' => [
        'label' => 'Device Control',
        'group' => 'IT Asset',
        'path' => '/it/devices',
      ],
      'employees' => [
        'label' => 'Users IT',
        'group' => 'IT Asset',
        'path' => '/employees',
      ],
      'compliance_dashboard' => [
        'label' => 'Dashboard Compliance',
        'group' => 'Compliance',
        'path' => '/compliance/dashboard',
      ],
      'compliance_progress' => [
        'label' => 'Monitoring Progress',
        'group' => 'Compliance',
        'path' => '/compliance/progress',
      ],
      'compliance_inventory' => [
        'label' => 'Inventory / Asset',
        'group' => 'Compliance',
        'path' => '/compliance/inventory',
      ],
      'checklist_master' => [
        'label' => 'Checklist Master',
        'group' => 'Compliance',
        'path' => '/compliance/checklist/master',
      ],
      'qr_gallery' => [
        'label' => 'QR Gallery',
        'group' => 'Compliance',
        'path' => '/compliance/inventory/qr-center',
      ],
      'holidays' => [
        'label' => 'Holiday',
        'group' => 'Compliance',
        'path' => '/holidays',
      ],
      'compliance_report' => [
        'label' => 'Report',
        'group' => 'Compliance',
        'path' => '/compliance/report',
      ],
      'thermal_imaging' => [
        'label' => 'Thermal Imaging',
        'group' => 'Compliance',
        'path' => '/compliance/thermal-imaging',
      ],
      'ems_reports' => [
        'label' => 'EMS Report',
        'group' => 'Compliance',
        'path' => '/ems-reports',
      ],
      'fdm_data_collection' => [
        'label' => 'FDM Data Collection',
        'group' => 'Compliance',
        'path' => '/fdm-data-collection',
      ],
      'questionnaires' => [
        'label' => 'Kuesioner',
        'group' => 'Compliance',
        'path' => '/compliance/questionnaires',
      ],
      'evidence_center' => [
        'label' => 'Evidence Center',
        'group' => 'Compliance',
        'path' => '/compliance/evidence',
      ],
      'boiler_fuel' => [
        'label' => 'Boiler Fuel',
        'group' => 'Boiler & Utility',
        'path' => '/boiler',
      ],
      'ipal' => [
        'label' => 'IPAL Limbah',
        'group' => 'Boiler & Utility',
        'path' => '/ipal',
      ],
      'pdam_water' => [
        'label' => 'Air PDAM',
        'group' => 'Boiler & Utility',
        'path' => '/pdam-water',
      ],
      'pdam_water_boiler' => [
        'label' => 'Air PDAM Boiler',
        'group' => 'Boiler & Utility',
        'path' => '/pdam-water-boiler',
      ],
      'compliance_print' => [
        'label' => 'Print Center',
        'group' => 'Compliance',
        'path' => '/compliance/print',
      ],
      'users_management' => [
        'label' => 'Manajemen User',
        'group' => 'Admin',
        'path' => '/users',
      ],
      'audit_logs' => [
        'label' => 'Audit Log',
        'group' => 'Admin',
        'path' => '/audit-logs',
      ],
      'backups' => [
        'label' => 'Backup',
        'group' => 'Admin',
        'path' => '/backups',
      ],
    ];
  }
}

if (! function_exists('access_menu_groups')) {
  function access_menu_groups(): array
  {
    $grouped = [];
    foreach (access_menu_catalog() as $key => $item) {
      $group = (string) ($item['group'] ?? 'Lainnya');
      $grouped[$group][$key] = $item;
    }

    return $grouped;
  }
}

if (! function_exists('normalize_page_access')) {
  function normalize_page_access($value): array
  {
    if (is_array($value)) {
      $items = $value;
    } else {
      $raw = trim((string) $value);
      if ($raw === '') {
        return [];
      }

      $decoded = json_decode($raw, true);
      if (! is_array($decoded)) {
        return [];
      }

      $items = $decoded;
    }

    $allowedKeys = array_keys(access_menu_catalog());
    $normalized = [];
    foreach ($items as $key) {
      $key = trim((string) $key);
      if ($key !== '' && in_array($key, $allowedKeys, true)) {
        $normalized[] = $key;
      }
    }

    return array_values(array_unique($normalized));
  }
}

if (! function_exists('session_page_access')) {
  function session_page_access(): array
  {
    return normalize_page_access(session()->get('page_access'));
  }
}

if (! function_exists('hasConfiguredPageAccess')) {
  function hasConfiguredPageAccess(): bool
  {
    $raw = session()->get('page_access');
    return $raw !== null && trim((string) $raw) !== '';
  }
}

if (! function_exists('isReadOnlyAccess')) {
  function isReadOnlyAccess(): bool
  {
    $role = strtolower(trim((string) session()->get('role')));
    $roleKey = str_replace([' ', '-'], '_', $role);
    $permission = strtolower(trim((string) session()->get('permission')));

    if ($roleKey === 'admin') {
      return false;
    }

    return $permission === 'read' || in_array($roleKey, ['read', 'readonly', 'read_only'], true);
  }
}

if (! function_exists('hasWriteAccess')) {
  function hasWriteAccess(): bool
  {
    return ! isReadOnlyAccess();
  }
}

if (! function_exists('canAccessPage')) {
  function canAccessPage(string $pageKey): bool
  {
    $role = (string) session()->get('role');
    if ($role === 'admin') {
      return true;
    }

    if (! hasConfiguredPageAccess()) {
      return true;
    }

    $access = session_page_access();
    return in_array($pageKey, $access, true);
  }
}

if (! function_exists('canAccessAnyPage')) {
  function canAccessAnyPage(array $pageKeys): bool
  {
    foreach ($pageKeys as $pageKey) {
      if (canAccessPage((string) $pageKey)) {
        return true;
      }
    }

    return false;
  }
}

if (! function_exists('canShowMenuPage')) {
  function canShowMenuPage(array $roles, string $pageKey): bool
  {
    if (in_array((string) session()->get('role'), $roles, true)) {
      return true;
    }

    if (! hasConfiguredPageAccess()) {
      return false;
    }

    return canAccessPage($pageKey);
  }
}

if (! function_exists('resolve_page_key_from_path')) {
  function resolve_page_key_from_path(string $path): ?string
  {
    $path = '/' . ltrim(trim($path), '/');

    if ($path === '/' || $path === '') {
      return 'home';
    }

    $map = [
      '/home' => 'home',
      '/patrol/dashboard' => 'patrol_dashboard',
      '/patrol/editor' => 'patrol_dashboard',
      '/patrol/layout/save' => 'patrol_dashboard',
      '/patrol' => 'patrol_daily',
      '/patrol/sessions/start' => 'patrol_daily',
      '/patrol/sessions/scan' => 'patrol_daily',
      '/patrol/sessions/cancel' => 'patrol_daily',
      '/dashboard-it' => 'dashboard_it',
      '/it/devices' => 'device_control',
      '/it-assets' => 'it_assets',
      '/employees' => 'employees',
      '/it' => 'it_center',
      '/compliance/checklist/master' => 'checklist_master',
      '/compliance/inventory/qr-center' => 'qr_gallery',
      '/compliance/inventory' => 'compliance_inventory',
      '/compliance/checklist' => 'compliance_inventory',
      '/compliance/dashboard' => 'compliance_dashboard',
      '/compliance/progress' => 'compliance_progress',
      '/holidays' => 'holidays',
      '/compliance/report' => 'compliance_report',
      '/compliance/thermal-imaging' => 'thermal_imaging',
      '/ems-reports' => 'ems_reports',
      '/fdm-data-collection' => 'fdm_data_collection',
      '/compliance/questionnaires' => 'questionnaires',
      '/compliance/evidence' => 'evidence_center',
      '/boiler' => 'boiler_fuel',
      '/ipal' => 'ipal',
      '/pdam-water' => 'pdam_water',
      '/pdam-water-boiler' => 'pdam_water_boiler',
      '/compliance/print' => 'compliance_print',
      '/users' => 'users_management',
      '/audit-logs' => 'audit_logs',
      '/backups' => 'backups',
    ];

    uksort($map, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

    foreach ($map as $prefix => $pageKey) {
      if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        return $pageKey;
      }
    }

    return null;
  }
}

if (! function_exists('resolve_default_landing_url')) {
  function resolve_default_landing_url(): string
  {
    $catalog = access_menu_catalog();

    if ((string) session()->get('role') === 'admin' || ! hasConfiguredPageAccess()) {
      return base_url('home');
    }

    $access = session_page_access();
    foreach ($access as $pageKey) {
      if (! isset($catalog[$pageKey]['path'])) {
        continue;
      }

      return base_url(ltrim((string) $catalog[$pageKey]['path'], '/'));
    }

    return base_url('home');
  }
}
