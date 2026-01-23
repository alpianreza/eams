<?php

function is_period_future(string $frequency, string $periodKey): bool
{
  $now = new DateTime('today');

  // ===== DAILY =====
  if ($frequency === 'daily') {
    $date = DateTime::createFromFormat('Y-m-d', $periodKey);
    return $date && $date > $now;
  }

  // ===== WEEKLY (W1–W4 per bulan) =====
  if ($frequency === 'weekly') {
    if (!preg_match('/^(\d{4})-(\d{2})-W([1-4])$/', $periodKey, $m)) {
      return true; // anggap future kalau format salah
    }

    $year  = (int) $m[1];
    $month = (int) $m[2];
    $week  = (int) $m[3];

    $endDay = ($week === 4)
      ? (int) date('t', strtotime("$year-$month-01"))
      : $week * 7;

    $weekEndDate = new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $endDay));

    return $weekEndDate > $now;
  }

  // ===== MONTHLY =====
  if ($frequency === 'monthly') {
    $date = DateTime::createFromFormat('Y-m-d', $periodKey . '-01');
    if (! $date) return true;

    $date->modify('last day of this month');
    return $date > $now;
  }

  return true;
}
