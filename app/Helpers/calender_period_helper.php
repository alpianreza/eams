<?php

if (! function_exists('generate_calendar_periods')) {

  /**
   * Generate periode checklist berbasis kalender
   *
   * @param string $frequency daily|weekly|monthly
   * @param int    $year
   * @param int|null $month (wajib untuk daily & weekly)
   * @return array
   */
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

      $firstDay = new DateTime("$year-$month-01");
      $lastDay  = new DateTime($firstDay->format('Y-m-t'));

      $week = 1;
      $cursor = clone $firstDay;

      while ($cursor <= $lastDay && $week <= 4) {

        $weekStart = clone $cursor;
        $weekEnd   = (clone $cursor)->modify('+6 days');

        if ((int)$weekStart->format('m') !== $month) {
          break;
        }

        $periodKey = sprintf(
          '%04d-%02d-W%d',
          $year,
          $month,
          $week
        );

        $periods[] = [
          'period_key' => $periodKey,
          'label'      => date('M Y', strtotime($weekStart->format('Y-m-d')))
            . " • Minggu ke-$week",
          'start'      => $weekStart->format('Y-m-d'),
          'end'        => $weekEnd->format('Y-m-d'),
        ];

        $week++;
        $cursor->modify('+7 days');
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
}
