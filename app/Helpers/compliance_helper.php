<?php

function checklist_status(?string $lastCheck, string $period): array
{
  if (!$lastCheck) {
    return ['status' => 'OVERDUE', 'label' => 'Belum Pernah'];
  }

  $last = new DateTime($lastCheck);
  $now  = new DateTime();

  switch ($period) {
    case 'daily':
      $due = (clone $last)->modify('+1 day');
      break;
    case 'weekly':
      $due = (clone $last)->modify('+7 days');
      break;
    case 'monthly':
      $due = (clone $last)->modify('+1 month');
      break;
    case 'yearly':
      $due = (clone $last)->modify('+1 year');
      break;
    default:
      return ['status' => 'UNKNOWN', 'label' => '-'];
  }

  if ($now > $due) {
    return ['status' => 'OVERDUE', 'label' => 'Terlambat'];
  }

  if ($now->format('Y-m-d') === $due->format('Y-m-d')) {
    return ['status' => 'DUE', 'label' => 'Hari Ini'];
  }

  return ['status' => 'OK', 'label' => 'Sudah'];
}
