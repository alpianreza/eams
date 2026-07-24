<?php

if (! function_exists('hasRole')) {
  function hasRole(array $roles): bool
  {
    if (in_array(session('role'), $roles, true)) {
      return true;
    }

    if (! function_exists('resolve_page_key_from_path') || ! function_exists('hasConfiguredPageAccess')) {
      return false;
    }

    if (! hasConfiguredPageAccess()) {
      return false;
    }

    $request = service('request');
    $path = '/' . ltrim($request->getUri()->getPath(), '/');
    $pageKey = resolve_page_key_from_path($path);

    return $pageKey !== null && canAccessPage($pageKey);
  }
}
