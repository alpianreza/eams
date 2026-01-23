<?php

if (! function_exists('is_period_allowed')) {
  function is_period_allowed(string $frequency, string $periodKey): bool
  {
    $now = new DateTime();

    if ($frequency === 'daily') {
      $date = new DateTime($periodKey);
      return $date >= (clone $now)->modify('-1 day');
    }

    if ($frequency === 'weekly') {
      $date = new DateTime();
      [$year, $week] = explode('-W', $periodKey);
      $date->setISODate($year, $week);

      $limit = (clone $now)->modify('-7 days');
      return $date >= $limit;
    }

    if ($frequency === 'monthly') {
      $date = new DateTime($periodKey . '-01');
      $limit = (clone $now)->modify('-1 month');
      return $date >= $limit;
    }

    return false;
  }
}
