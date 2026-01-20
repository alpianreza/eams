<?php

function status_badge($status, $label)
{
  $map = [
    'OK' => 'bg-success',
    'DUE' => 'bg-warning text-dark',
    'OVERDUE' => 'bg-danger',
  ];

  $class = $map[$status] ?? 'bg-secondary';

  return '<span class="badge ' . $class . '">' . esc($label) . '</span>';
}
