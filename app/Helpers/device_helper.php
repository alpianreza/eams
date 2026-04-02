<?php

if (!function_exists('device_extra')) {
  function device_extra(array $device): array
  {
    $decoded = json_decode($device['cpu'] ?? '{}', true);
    return is_array($decoded) ? $decoded : [];
  }
}

if (!function_exists('device_hardware')) {
  function device_hardware(array $device): array
  {
    $extra = device_extra($device);
    return $extra['hardware'] ?? [];
  }
}

if (!function_exists('device_ram_total')) {
  function device_ram_total(array $device): int
  {
    $hw = device_hardware($device);
    $total = 0;

    foreach ($hw['ram_slots'] ?? [] as $ram) {
      $total += (int)($ram['size_gb'] ?? 0);
    }

    if ($total <= 0) {
      $total = (int)($device['ram_gb'] ?? 0);
    }

    return $total;
  }
}

if (!function_exists('device_disk_total')) {
  function device_disk_total(array $device): int
  {
    $hw = device_hardware($device);
    $total = 0;

    foreach ($hw['disks'] ?? [] as $disk) {
      $total += (int)($disk['size_gb'] ?? 0);
    }

    if ($total <= 0) {
      $total = (int)($device['storage_gb'] ?? 0);
    }

    return $total;
  }
}

if (!function_exists('device_os_build_number')) {
  function device_os_build_number(array $device): int
  {
    $extra = device_extra($device);
    $rawBuild = trim((string)($extra['os_build'] ?? ''));

    if ($rawBuild === '') {
      return 0;
    }

    $parts = preg_split('/\./', $rawBuild);
    $last = is_array($parts) ? trim((string)end($parts)) : trim((string)$rawBuild);

    return ctype_digit($last) ? (int)$last : 0;
  }
}

if (!function_exists('device_normalize_windows_name')) {
  function device_normalize_windows_name(array $device): string
  {
    $rawName = trim((string)($device['os'] ?? ''));
    $normalized = preg_replace('/^Microsoft\s+/i', '', $rawName) ?? $rawName;
    $normalized = preg_replace('/Windows\s*11\b/i', 'Windows 11', $normalized) ?? $normalized;
    $normalized = preg_replace('/Windows\s*10\b/i', 'Windows 10', $normalized) ?? $normalized;
    $normalized = preg_replace('/Windows\s*8\.1\b/i', 'Windows 8.1', $normalized) ?? $normalized;
    $normalized = preg_replace('/Windows\s*8\b/i', 'Windows 8', $normalized) ?? $normalized;
    $normalized = preg_replace('/Windows\s*7\b/i', 'Windows 7', $normalized) ?? $normalized;
    $normalized = preg_replace('/\s+/', ' ', trim($normalized)) ?? trim($normalized);

    if ($normalized !== '') {
      return $normalized;
    }

    $build = device_os_build_number($device);
    if ($build >= 22000) {
      return 'Windows 11';
    }
    if ($build >= 10240) {
      return 'Windows 10';
    }
    if ($build >= 9600) {
      return 'Windows 8.1';
    }
    if ($build >= 9200) {
      return 'Windows 8';
    }
    if ($build >= 7600) {
      return 'Windows 7';
    }
    if ($build >= 6000) {
      return 'Windows Vista';
    }
    if ($build > 0) {
      return 'Windows XP';
    }

    return '';
  }
}

if (!function_exists('device_os_label')) {
  function device_os_label(array $device): string
  {
    $extra = device_extra($device);
    $label = device_normalize_windows_name($device);
    $edition = trim((string)($extra['os_edition'] ?? ''));

    if ($label === '' && $edition !== '') {
      $label = $edition;
    }

    if ($label === '') {
      return '-';
    }

    $label = preg_replace('/\s+/', ' ', trim($label)) ?? trim($label);

    if ($edition !== '' && stripos($label, $edition) === false) {
      $label = trim($label . ' ' . $edition);
    }

    $osVersion = trim((string)($device['os_version'] ?? ''));
    if ($osVersion !== '' && stripos($label, $osVersion) === false && preg_match('/^Windows$/i', $label)) {
      $label .= ' ' . $osVersion;
    }

    return $label;
  }
}

if (!function_exists('device_os_meta')) {
  function device_os_meta(array $device): string
  {
    $extra = device_extra($device);
    $segments = [];

    $release = strtoupper(trim((string)($extra['os_release'] ?? '')));
    if ($release !== '' && $release !== 'UNKNOWN') {
      $segments[] = $release;
    }

    $build = trim((string)($extra['os_build'] ?? ''));
    if ($build !== '') {
      $segments[] = 'Build ' . $build;
    }

    if (empty($segments)) {
      $version = trim((string)($device['os_version'] ?? ''));
      if ($version !== '' && stripos(device_os_label($device), $version) === false) {
        $segments[] = 'Versi ' . $version;
      }
    }

    return implode(' / ', $segments);
  }
}

if (!function_exists('device_risk_score')) {
  function device_risk_score(array $device): int
  {
    $extra = device_extra($device);
    $score = 100;

    $pendingUpdates = (int)($device['pending_updates'] ?? $extra['pending'] ?? 0);
    if ($pendingUpdates > 20) {
      $score -= 30;
    } elseif ($pendingUpdates >= 5) {
      $score -= 15;
    } elseif ($pendingUpdates >= 1) {
      $score -= 5;
    }

    $activation = strtolower((string)($device['activation_status'] ?? $extra['activation'] ?? ''));
    if (in_array($activation, ['not_activated', 'not activated', 'inactive'], true)) {
      $score -= 25;
    }

    $totalStorage = (float)($device['storage_gb'] ?? 0);
    $freeStorage = (float)($device['storage_free'] ?? $extra['storage_free'] ?? 0);

    if ($totalStorage > 0) {
      $freePercent = ($freeStorage / $totalStorage) * 100;
      if ($freePercent < 10) {
        $score -= 25;
      } elseif ($freePercent < 20) {
        $score -= 10;
      }
    }

    $cpuUsage = (float)($device['cpu_usage'] ?? $extra['cpu_usage'] ?? 0);
    if ($cpuUsage > 90) {
      $score -= 10;
    }

    if (!empty($device['last_seen'])) {
      $hours = (time() - strtotime($device['last_seen'])) / 3600;
      if ($hours > 72) {
        $score -= 25;
      } elseif ($hours > 24) {
        $score -= 10;
      }
    }

    return max($score, 0);
  }
}

if (!function_exists('device_risk_label')) {
  function device_risk_label(int $score): array
  {
    if ($score >= 80) {
      return ['Sehat', 'success'];
    }

    if ($score >= 50) {
      return ['Waspada', 'warning'];
    }

    return ['Kritis', 'danger'];
  }
}

if (!function_exists('device_is_online')) {
  function device_is_online(array $device, int $interval = 600): bool
  {
    if (empty($device['last_seen'])) {
      return false;
    }

    $threshold = max(30, $interval * 2);
    return (time() - strtotime($device['last_seen'])) <= $threshold;
  }
}
