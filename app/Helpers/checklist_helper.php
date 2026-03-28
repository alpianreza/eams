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

if (! function_exists('is_weekend_offday')) {
  function is_weekend_offday(string $date): bool
  {
    $dayOfWeek = (int) date('w', strtotime($date)); // 0 = Sunday, 6 = Saturday

    if ($dayOfWeek === 0) {
      return true;
    }

    if ($dayOfWeek === 6 && $date >= '2026-04-01') {
      return true;
    }

    return false;
  }
}

if (! function_exists('is_date_offday')) {
  function is_date_offday(string $date, array $holidayDates = []): bool
  {
    if (is_weekend_offday($date)) {
      return true;
    }

    return in_array($date, $holidayDates, true);
  }
}

if (! function_exists('holiday_dates_between')) {
  function holiday_dates_between(string $startDate, string $endDate): array
  {
    $rows = (new \App\Models\HolidayModel())
      ->select('holiday_date')
      ->where('holiday_date >=', $startDate)
      ->where('holiday_date <=', $endDate)
      ->findAll();

    return array_column($rows, 'holiday_date');
  }
}

