<?php

namespace App\Controllers;

use Config\Database;

class AuditLogController extends BaseController
{
  protected $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  public function index()
  {
    $logs = $this->db->table('audit_logs al')
      ->select('
                al.action,
                al.description,
                al.created_at,
                u.name AS user_name,
                u.username
            ')
      ->join('users u', 'u.id = al.user_id', 'left')
      ->orderBy('al.created_at', 'DESC')
      ->limit(200)
      ->get()
      ->getResultArray();

    return view('audit_logs/index', [
      'logs'  => $logs,
      'title' => 'Audit Log'
    ]);
  }
}
