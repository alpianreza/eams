<?php

if (!function_exists('audit_log')) {
  function audit_log($action, $description)
  {
    $db = \Config\Database::connect();

    $db->table('audit_logs')->insert([
      'user_id'    => session()->get('user_id'),
      'action'     => $action,
      'description' => $description,
      'created_at' => date('Y-m-d H:i:s')
    ]);
  }
}
