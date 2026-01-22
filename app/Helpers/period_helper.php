<?php

function get_period_key(string $frequency, ?string $date = null): string
{
  $dt = new DateTime($date ?? 'now');

  switch ($frequency) {

    case 'daily':
      return $dt->format('Y-m-d');

    case 'weekly':
      // minggu ke-n DALAM BULAN
      $weekOfMonth = ceil($dt->format('j') / 7); // tanggal / 7
      return $dt->format('Y-m') . '-W' . $weekOfMonth;

    case 'monthly':
      return $dt->format('Y-m');

    default:
      throw new InvalidArgumentException('Invalid frequency');
  }
}
