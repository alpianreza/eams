<?php

namespace App\Controllers;

use Config\Database;

class ComplianceRankingController extends BaseController
{
  public function index()
  {
    $db = Database::connect();

    $ym = trim((string) $this->request->getGet('ym'));
    if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
      $ym = date('Y-m');
    }

    [$year, $month] = explode('-', $ym);

    $monthStart = $ym . '-01';
    $monthEnd   = date('Y-m-t', strtotime($monthStart));

    // ── RAW DATA PERIOD KEY ──
    $logs = $db->table('checklist_logs cl')
      ->select('
        cl.checked_by,
        cl.period_key,
        cl.check_date,
        cl.status,
        cl.inventory_id,
        cl.time_slot,
        cl.id,
        ai.checklist_frequency
      ')
      ->join('compliance_inventory ci', 'ci.id = cl.inventory_id', 'left')
      ->join('asset_item_types ai', 'ai.id = ci.item_type_id', 'left')
      ->where('cl.check_date >=', $monthStart)
      ->where('cl.check_date <=', $monthEnd)
      ->where('cl.checked_by IS NOT NULL')
      ->where('cl.checked_by !=', '')
      ->orderBy('cl.checked_by', 'ASC')
      ->get()
      ->getResultArray();

    // ── GROUP: checked_by → inventory → period → slot ──
    $userData = [];

    foreach ($logs as $log) {
      $user = trim((string) $log['checked_by']);
      if ($user === '') {
        continue;
      }

      $inventoryId = (int) $log['inventory_id'];
      $periodKey   = (string) $log['period_key'];
      $slot        = (string) ($log['time_slot'] ?? '');
      $checkDate   = (string) $log['check_date'];
      $status      = (string) $log['status'];
      $frequency   = (string) ($log['checklist_frequency'] ?? 'monthly');

      if (! isset($userData[$user])) {
        $userData[$user] = [
          'total'       => 0,
          'ontime'      => 0,
          'late'        => 0,
          'not_ok'      => 0,
          'na'          => 0,
          'ok'          => 0,
          'inventories' => [],
          'last_date'   => null,
        ];
      }

      // Hitung total = 1 per (inventory, periode, slot)
      $invKey = $inventoryId . '|' . $periodKey;
      if ($slot !== '') {
        $invKey .= '|' . $slot;
      }

      if (! in_array($invKey, $userData[$user]['inventories'], true)) {
        $userData[$user]['inventories'][] = $invKey;
        $userData[$user]['total']++;

        // Ontime check
        if ($frequency === 'daily') {
          // daily: check_date sama dengan period_key atau sehari sebelum
          if ($checkDate <= $periodKey) {
            $userData[$user]['ontime']++;
          } else {
            $userData[$user]['late']++;
          }
        } elseif ($frequency === 'weekly') {
          // weekly: key format YYYY-MM-W1..W4
          // check before or within the week's end (7 days from week start)
          $weekEnd = $this->weekEndDate($periodKey);
          if ($weekEnd !== null && $checkDate <= $weekEnd) {
            $userData[$user]['ontime']++;
          } else {
            $userData[$user]['late']++;
          }
        } else {
          // monthly: check_date <= last day of month
          if ($checkDate <= $monthEnd) {
            $userData[$user]['ontime']++;
          } else {
            $userData[$user]['late']++;
          }
        }
      }

      if ($status === 'not_ok') {
        $userData[$user]['not_ok']++;
      } elseif ($status === 'ok') {
        $userData[$user]['ok']++;
      } elseif ($status === 'na') {
        $userData[$user]['na']++;
      }

      if ($checkDate > ($userData[$user]['last_date'] ?? '')) {
        $userData[$user]['last_date'] = $checkDate;
      }
    }

    // ── SCORE ──
    $rankings = [];
    foreach ($userData as $user => $d) {
      $score = 0;
      $score += $d['ontime'] * 10;    // +10 tiap on-time
      $score += $d['late'] * 3;        // +3 tiap late (tetap dihitung tp kecil)

      if ($d['total'] > 0) {
        $ontimePct = round(($d['ontime'] / $d['total']) * 100, 1);
      } else {
        $ontimePct = 0;
      }

      $rankings[] = [
        'user'       => $user,
        'total'      => $d['total'],
        'ontime'     => $d['ontime'],
        'late'       => $d['late'],
        'ok_count'   => $d['ok'],
        'not_ok'     => $d['not_ok'],
        'na'         => $d['na'],
        'ontime_pct' => $ontimePct,
        'score'      => max(0, $score),
        'last_date'  => $d['last_date'],
      ];
    }

    usort($rankings, function ($a, $b) {
      if ($b['score'] !== $a['score']) {
        return $b['score'] <=> $a['score'];
      }
      if ($b['ontime_pct'] !== $a['ontime_pct']) {
        return $b['ontime_pct'] <=> $a['ontime_pct'];
      }
      return $b['total'] <=> $a['total'];
    });

    // ── PREV / NEXT ──
    $prevYM = date('Y-m', strtotime($ym . '-01 -1 month'));
    $nextYM = date('Y-m', strtotime($ym . '-01 +1 month'));
    $canNext = $nextYM <= date('Y-m');

    return view('compliance/ranking/index', [
      'title'    => 'Ranking Checklist User',
      'ym'       => $ym,
      'monthLabel' => date('F Y', strtotime($monthStart)),
      'rankings' => $rankings,
      'prevYM'   => $prevYM,
      'nextYM'   => $nextYM,
      'canNext'  => $canNext,
    ]);
  }

  /**
   * Menentukan tanggal akhir minggu dari period_key weekly.
   * Format: YYYY-MM-Wn → return YYYY-MM-DD (hari terakhir minggu tsb)
   */
  private function weekEndDate(string $periodKey): ?string
  {
    if (! preg_match('/^(\d{4})-(\d{2})-W([1-4])$/', $periodKey, $m)) {
      return null;
    }

    $year  = (int) $m[1];
    $month = (int) $m[2];
    $week  = (int) $m[3];

    if ($week === 1)      $day = 1;
    elseif ($week === 2)  $day = 8;
    elseif ($week === 3)  $day = 15;
    else                  $day = 22;

    $start = new \DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));

    return $start->modify('+6 days')->format('Y-m-d');
  }
}
