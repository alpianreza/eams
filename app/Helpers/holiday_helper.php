<?php

if (! function_exists('is_holiday')) {

  /**
   * Cek apakah tanggal termasuk hari libur
   * (sementara: weekend, nanti bisa extend ke libur nasional)
   */
  function is_holiday(string $date): bool
  {
    $day = date('N', strtotime($date)); // 6=Sabtu, 7=Minggu
    return in_array($day, [6, 7]);
  }
}
