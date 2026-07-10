<?php

use App\Models\ChecklistLogModel;

if (! function_exists('resolve_period_status')) {

  /**
   * Tentukan status periode checklist
   */
  function resolve_period_status(
    int $inventoryId,
    string $frequency,
    string $periodKey
  ): string {

    $logModel = new ChecklistLogModel();

    // === SUDAH ADA LOG → DONE ===
    $exists = $logModel
      ->where('inventory_id', $inventoryId)
      ->where('period_key', $periodKey)
      ->first();

    if ($exists) {
      return 'done'; // otomatis locked
    }

    $now = new DateTime();

    // === DAILY ===
    if ($frequency === 'daily') {

      if (is_holiday($periodKey)) {
        return 'holiday';
      }

      $date = new DateTime($periodKey);

      if ($date > $now) {
        return 'locked'; // future
      }

      return 'open';
    }

    // === WEEKLY ===
    if ($frequency === 'weekly') {

      [$year, $month, $week] = sscanf($periodKey, '%d-%d-W%d');

      $start = new DateTime();
      $start->setDate($year, $month, 1);
      $start->modify('+' . (($week - 1) * 7) . ' days');

      if ($start > $now) {
        return 'locked';
      }

      return 'open';
    }

    // === MONTHLY ===
    if ($frequency === 'monthly') {

      $date = new DateTime($periodKey . '-01');

      if ($date > $now) {
        return 'locked';
      }

      return 'open';
    }

    return 'locked';
  }
}
