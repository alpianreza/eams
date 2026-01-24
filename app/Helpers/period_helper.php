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
    // ================= DAILY =================
    if ($frequency === 'daily') {
      return date('d F Y', strtotime($periodKey));
    }

    // ================= WEEKLY =================
    if ($frequency === 'weekly') {
      // format: YYYY-MM-Wn
      if (!preg_match('/^(\d{4})-(\d{2})-W([1-4])$/', $periodKey, $m)) {
        return $periodKey;
      }

      $year  = (int) $m[1];
      $month = (int) $m[2];
      $week  = (int) $m[3];

      return sprintf(
        'Minggu ke-%d · %s %d',
        $week,
        date('F', strtotime("$year-$month-01")),
        $year
      );
    }

    // ================= MONTHLY =================
    if ($frequency === 'monthly') {
      return date('F Y', strtotime($periodKey . '-01'));
    }

    return $periodKey;
  }
}
