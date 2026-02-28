<?php

function device_hardware($device)
{
  $extra = json_decode($device['cpu'] ?? '{}', true);
  return $extra['hardware'] ?? [];
}

function device_ram_total($device)
{
  $hw = device_hardware($device);
  $total = 0;

  foreach ($hw['ram_slots'] ?? [] as $r) {
    $total += $r['size_gb'] ?? 0;
  }

  return $total;
}

function device_disk_total($device)
{
  $hw = device_hardware($device);
  $total = 0;

  foreach ($hw['disks'] ?? [] as $d) {
    $total += $d['size_gb'] ?? 0;
  }

  return $total;
}

function device_risk_score($d)
{
  $score = 100;

  if (($d['pending_updates'] ?? 0) > 20) $score -= 30;
  elseif (($d['pending_updates'] ?? 0) >= 5) $score -= 15;
  elseif (($d['pending_updates'] ?? 0) >= 1) $score -= 5;

  if (($d['activation_status'] ?? '') === 'not_activated') $score -= 25;

  $total = $d['storage_gb'] ?? 0;
  $free  = $d['storage_free'] ?? 0;

  if ($total > 0) {
    $freePercent = ($free / $total) * 100;
    if ($freePercent < 10) $score -= 25;
    elseif ($freePercent < 20) $score -= 10;
  }

  if (($d['cpu_usage'] ?? 0) > 90) $score -= 10;

  if (!empty($d['last_seen'])) {
    $hours = (time() - strtotime($d['last_seen'])) / 3600;

    if ($hours > 72) $score -= 25;
    elseif ($hours > 24) $score -= 10;
  }

  return max($score, 0);
}

function device_risk_label($score)
{
  if ($score >= 80) return ['Healthy', 'success'];
  if ($score >= 50) return ['Warning', 'warning'];
  return ['Critical', 'danger'];
}

function device_is_online($d, $interval = 300)
{
  if (empty($d['last_seen'])) return false;

  $threshold = $interval * 2;
  return (time() - strtotime($d['last_seen'])) <= $threshold;
}