<?php

if (!function_exists('page')) {
  function page(string $title, ?string $backUrl = null)
  {
    $renderer = service('renderer');

    $data = [
      'title' => $title,
      'backUrl' => $backUrl ? base_url($backUrl) : null,
    ];

    $renderer->setData($data);
  }
}
