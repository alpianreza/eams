<?php

if (!function_exists('generate_period_key')) {
  function generate_period_key(string $frequency, ?string $date = null): string
  {
    $date = $date ?? date('Y-m-d');

    if ($frequency === 'daily') {
      return $date;
    }

    if ($frequency === 'weekly') {
      return date('o-\WW', strtotime($date));
    }

    if ($frequency === 'monthly') {
      return date('Y-m', strtotime($date));
    }

    throw new Exception('Invalid frequency');
  }
}

if (!function_exists('period_label')) {
  function period_label(string $frequency, string $periodKey): string
  {
    if ($frequency === 'daily') {
      return date('d F Y (l)', strtotime($periodKey));
    }

    if ($frequency === 'weekly') {
      // contoh periodKey: 2026-W04
      [$year, $week] = explode('-W', $periodKey);

      $date = new DateTime();
      $date->setISODate((int)$year, (int)$week);

      return 'Minggu ke-' . (int)$week . ' • ' . $date->format('F Y');
    }

    if ($frequency === 'monthly') {
      return date('F Y', strtotime($periodKey . '-01'));
    }

    return $periodKey;
  }
}
