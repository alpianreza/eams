<?php

if (! function_exists('build_query_params')) {
  /**
   * Returns current query params minus the ones to override.
   */
  function build_query_params(array $override = []): array
  {
    $request = service('request');
    $params  = $request->getGet(array_keys($request->getGet()));

    if (! is_array($params)) {
      $params = [];
    }

    foreach ($override as $key => $value) {
      $params[$key] = $value;
    }

    return $params;
  }
}

if (! function_exists('build_sort_url')) {
  function build_sort_url(string $field): string
  {
    $request = service('request');
    $current = strtolower(trim((string) $request->getGet('sort')));
    $dir     = strtolower(trim((string) $request->getGet('dir')));

    $newDir = ($current === $field && $dir === 'asc') ? 'desc' : 'asc';

    $params = build_query_params(['sort' => $field, 'dir' => $newDir, 'page' => '1']);

    return site_url('audit-logs') . '?' . http_build_query($params);
  }
}

if (! function_exists('sort_icon')) {
  function sort_icon(string $field, string $currentSort, string $currentDir): string
  {
    if ($currentSort !== $field) {
      return '<i class="bi bi-arrow-down-up text-muted" style="font-size:0.65rem"></i>';
    }

    $icon = $currentDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down';
    return '<i class="bi ' . $icon . '" style="font-size:0.65rem"></i>';
  }
}

if (! function_exists('build_page_url')) {
  function build_page_url(int $page): string
  {
    $params = build_query_params(['page' => (string) $page]);
    return site_url('audit-logs') . '?' . http_build_query($params);
  }
}

if (!function_exists('audit_log')) {
  function audit_log($action, $description)
  {
    $request = service('request');

    $db = \Config\Database::connect();

    $db->table('audit_logs')->insert([
      'user_id'    => session()->get('user_id'),
      'action'     => $action,
      'description' => $description,
      'ip_address'  => $request->getIPAddress(),
      'user_agent'  => $request->getUserAgent()?->getAgentString(),
      'created_at'  => date('Y-m-d H:i:s')
    ]);
  }
}
