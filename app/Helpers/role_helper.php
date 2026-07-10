<?php

if (! function_exists('normalize_role')) {
  function normalize_role(string $role): string
  {
    return str_replace([' ', '-'], '_', strtolower(trim($role)));
  }
}

if (! function_exists('hasRole')) {
  function hasRole(array $roles): bool
  {
    $sessionRole = normalize_role((string) session('role'));
    $normalizedRoles = array_map('normalize_role', $roles);

    if (in_array($sessionRole, $normalizedRoles, true)) {
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
