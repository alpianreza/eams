<?php
if (! function_exists('generate_period_key')) {
  function generate_period_key(string $frequency, ?string $date = null): string
  {
    $date = $date ?? date('Y-m-d');

    if ($frequency === 'daily') {
      return $date; // YYYY-MM-DD
    }

    if ($frequency === 'weekly') {
      $year  = date('Y', strtotime($date));
      $month = date('m', strtotime($date));
      $day   = (int) date('d', strtotime($date));

      if ($day <= 7)       $week = 1;
      elseif ($day <= 14)  $week = 2;
      elseif ($day <= 21)  $week = 3;
      else                 $week = 4;

      return sprintf('%s-%s-W%d', $year, $month, $week);
    }

    if ($frequency === 'monthly') {
      return date('Y-m', strtotime($date));
    }

    throw new Exception('Invalid frequency');
  }
}

if (! function_exists('period_label')) {
  function period_label(string $frequency, string $periodKey): string
  {
    if ($frequency === 'daily') {
      return date('d F Y', strtotime($periodKey));
    }

    if ($frequency === 'weekly') {
      if (! preg_match('/^(\d{4})-(\d{2})-W([1-4])$/', $periodKey, $m)) {
        return $periodKey;
      }

      return sprintf(
        'Minggu %d %s %d',
        (int) $m[3],
        date('F', strtotime("{$m[1]}-{$m[2]}-01")),
        (int) $m[1]
      );
    }

    if ($frequency === 'monthly') {
      return date('F Y', strtotime($periodKey . '-01'));
    }

    return $periodKey;
  }
}

if (! function_exists('diff_months')) {
  function diff_months(string $ym1, string $ym2): int
  {
    [$y1, $m1] = array_map('intval', explode('-', $ym1));
    [$y2, $m2] = array_map('intval', explode('-', $ym2));

    return ($y2 - $y1) * 12 + ($m2 - $m1);
  }
}

if (! function_exists('is_period_editable')) {
  function is_period_editable(string $frequency, string $periodKey, int $graceMonths = 3): bool
  {
    // FUTURE = TIDAK BOLEH
    if (is_period_future($frequency, $periodKey)) {
      return false;
    }

    // DAILY → boleh selama <= hari ini
    if ($frequency === 'daily') {
      return true;
    }

    // WEEKLY / MONTHLY → cek grace 3 bulan
    $nowYM = date('Y-m');
    $periodYM = substr($periodKey, 0, 7);

    return diff_months($periodYM, $nowYM) <= $graceMonths;
  }
}
if (! function_exists('is_period_future')) {
  function is_period_future(string $frequency, string $periodKey): bool
  {
    $today = date('Y-m-d');
    $nowYM = date('Y-m');

    // ================= DAILY =================
    if ($frequency === 'daily') {
      return $periodKey > $today;
    }

    // ================= WEEKLY =================
    if ($frequency === 'weekly') {
      // format: YYYY-MM-Wn
      if (! preg_match('/^(\d{4})-(\d{2})-W([1-4])$/', $periodKey, $m)) {
        return false;
      }

      $periodYM   = "{$m[1]}-{$m[2]}";
      $periodWeek = (int) $m[3];

      // minggu sekarang (reset per bulan)
      $dayNow = (int) date('d');
      if ($dayNow <= 7)       $currentWeek = 1;
      elseif ($dayNow <= 14)  $currentWeek = 2;
      elseif ($dayNow <= 21)  $currentWeek = 3;
      else                    $currentWeek = 4;

      // bulan depan → FUTURE
      if ($periodYM > $nowYM) {
        return true;
      }

      // bulan sama tapi minggu depan → FUTURE
      if ($periodYM === $nowYM && $periodWeek > $currentWeek) {
        return true;
      }

      return false;
    }

    // ================= MONTHLY =================
    if ($frequency === 'monthly') {
      return $periodKey > $nowYM;
    }

    return false;
  }
}

if (! function_exists('resolve_period_status')) {
  function resolve_period_status(int $inventoryId, string $frequency, string $periodKey): string
  {
    $logModel = new \App\Models\ChecklistLogModel();

    // DONE
    $log = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->first();

    if ($log) {
      return 'done';
    }

    // FUTURE
    if (is_period_future($frequency, $periodKey)) {
      return 'future';
    }

    // LATE
    if (is_period_late($frequency, $periodKey)) {
      return 'late';
    }

    // DEFAULT
    return 'pending';
  }
}


if (! function_exists('is_period_late')) {
  function is_period_late(string $frequency, string $periodKey): bool
  {
    $now = new DateTime();

    // ===== DAILY =====
    if ($frequency === 'daily') {
      $date  = new DateTime($periodKey); // YYYY-MM-DD
      $limit = (clone $date)->modify('+21 days');
      return $limit < $now;
    }

    // ===== WEEKLY (🔥 FIX: 4 MINGGU) =====
    if ($frequency === 'weekly') {
      // format: YYYY-MM-Wn
      if (!preg_match('/^(\d{4})-(\d{2})-W([1-4])$/', $periodKey, $m)) {
        return false;
      }

      $year  = (int) $m[1];
      $month = (int) $m[2];
      $week  = (int) $m[3];

      // Tentukan tanggal awal minggu (reset bulanan)
      if ($week === 1)      $day = 1;
      elseif ($week === 2)  $day = 8;
      elseif ($week === 3)  $day = 15;
      else                  $day = 22;

      $start = new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));
      $limit = (clone $start)->modify('+28 days'); // 4 minggu

      return $limit < $now;
    }

    // ===== MONTHLY =====
    if ($frequency === 'monthly') {
      $limit = (new DateTime($periodKey . '-01'))->modify('+3 months');
      return $limit < $now;
    }

    return false;
  }
}
