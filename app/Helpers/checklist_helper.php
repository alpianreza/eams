<?php
function generate_calendar_periods(
  string $frequency,
  int $year,
  ?int $month = null
): array {

  $periods = [];

  // ================= DAILY =================
  if ($frequency === 'daily') {

    if (! $month) return [];

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    for ($d = 1; $d <= $daysInMonth; $d++) {

      $date = sprintf('%04d-%02d-%02d', $year, $month, $d);

      $periods[] = [
        'period_key' => $date,
        'label'      => date('d M Y', strtotime($date)),
        'date'       => $date,
      ];
    }
  }

  // ================= WEEKLY =================
  if ($frequency === 'weekly') {

    if (! $month) return [];

    $ranges = [
      1 => [1, 7],
      2 => [8, 14],
      3 => [15, 21],
      4 => [22, cal_days_in_month(CAL_GREGORIAN, $month, $year)],
    ];

    foreach ($ranges as $week => [$startDay, $endDay]) {

      $startDate = sprintf('%04d-%02d-%02d', $year, $month, $startDay);
      $endDate   = sprintf('%04d-%02d-%02d', $year, $month, $endDay);

      $periods[] = [
        'period_key' => sprintf('%04d-%02d-W%d', $year, $month, $week),
        'label'      => date('M Y', strtotime($startDate)) . " • Minggu ke-$week",
        'start'      => $startDate,
        'end'        => $endDate,
      ];
    }
  }

  // ================= MONTHLY =================
  if ($frequency === 'monthly') {

    for ($m = 1; $m <= 12; $m++) {

      $periodKey = sprintf('%04d-%02d', $year, $m);

      $periods[] = [
        'period_key' => $periodKey,
        'label'      => date('F Y', strtotime("$year-$m-01")),
        'month'      => $m,
      ];
    }
  }

  return $periods;
}

