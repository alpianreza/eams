<?php

function it_health_score($device)
{
  $extra = json_decode($device['cpu'] ?? '{}', true);

  $score = 100;

  if (str_contains(($extra['os_edition'] ?? ''), 'Windows 7')) $score -= 40;
  if (($extra['activation'] ?? '') != 'activated') $score -= 30;
  if (($extra['pending'] ?? 0) > 5) $score -= 20;

  if ($score < 0) $score = 0;

  return $score;
}

function it_compliance_badge($device)
{
  $score = it_health_score($device);

  if ($score >= 80) return 'success';
  if ($score >= 50) return 'warning';
  return 'danger';
}
