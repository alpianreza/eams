<?php

function windows_lifecycle($release)
{
  $map = [
    '21H2' => ['status' => 'eol', 'color' => 'danger'],
    '22H2' => ['status' => 'supported', 'color' => 'success'],
    '23H2' => ['status' => 'supported', 'color' => 'success'],
    '24H2' => ['status' => 'latest', 'color' => 'primary'],
  ];

  return $map[$release] ?? ['status' => 'unknown', 'color' => 'secondary'];
}

function windows_upgrade_recommendation($release)
{
  if ($release == '21H2') return 'Upgrade segera ke 23H2 / 24H2';
  if ($release == '22H2') return 'Disarankan upgrade ke 24H2';
  if ($release == '23H2') return 'Opsional upgrade ke 24H2';
  return null;
}
