<?php

if (! function_exists('hasRole')) {
  function hasRole(array $roles): bool
  {
    return in_array(session('role'), $roles);
  }
}
