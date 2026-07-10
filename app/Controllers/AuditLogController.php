<?php

namespace App\Controllers;

use Config\Database;

class AuditLogController extends BaseController
{
  public function index()
  {
    helper('audit');
    $db = Database::connect();

    $builder = $db->table('audit_logs al')
      ->select('
            al.id,
            al.action,
            al.description,
            al.ip_address,
            al.created_at,
            u.name AS user_name,
            u.username
        ')
      ->join('users u', 'u.id = al.user_id', 'left');

    // ── SEARCH ──
    $search = trim((string) $this->request->getGet('q'));
    if ($search !== '') {
      $builder->groupStart()
        ->like('al.description', $search)
        ->orLike('al.action', $search)
        ->orLike('u.name', $search)
        ->orLike('u.username', $search)
        ->groupEnd();
    }

    // ── FILTER ACTION ──
    $action = trim((string) $this->request->getGet('action'));
    if ($action !== '') {
      $builder->where('al.action', $action);
    }

    // ── FILTER USER ──
    $userId = $this->request->getGet('user_id');
    if ($userId !== null && $userId !== '' && $userId !== false) {
      $builder->where('al.user_id', (int) $userId);
    }

    // ── DATE RANGE ──
    $dateFrom = trim((string) $this->request->getGet('date_from'));
    $dateTo   = trim((string) $this->request->getGet('date_to'));

    if ($dateFrom !== '') {
      $builder->where('al.created_at >=', $dateFrom . ' 00:00:00');
    }
    if ($dateTo !== '') {
      $builder->where('al.created_at <=', $dateTo . ' 23:59:59');
    }

    // ── ORDER ──
    $sort     = trim((string) $this->request->getGet('sort')) ?: 'created_at';
    $dir      = strtolower(trim((string) $this->request->getGet('dir'))) === 'asc' ? 'ASC' : 'DESC';

    $sortAllowed = ['created_at', 'action', 'user_name'];
    if (! in_array($sort, $sortAllowed, true)) {
      $sort = 'created_at';
    }

    if ($sort === 'user_name') {
      $builder->orderBy('u.name', $dir);
    } else {
      $builder->orderBy('al.' . $sort, $dir);
    }

    // ── PAGINATION ──
    $perPage = max(10, min(100, (int) ($this->request->getGet('per_page') ?: 25)));
    $page    = max(1, (int) ($this->request->getGet('page') ?: 1));
    $offset  = ($page - 1) * $perPage;

    $total = (clone $builder)->countAllResults(false);
    $logs  = $builder->limit($perPage, $offset)->get()->getResultArray();

    // ── DISTINCT ACTIONS FOR DROPDOWN ──
    $actionList = $db->table('audit_logs')
      ->select('action, COUNT(*) as cnt')
      ->groupBy('action')
      ->orderBy('cnt', 'DESC')
      ->get()
      ->getResultArray();

    // ── USER LIST FOR DROPDOWN ──
    $userList = $db->table('audit_logs al')
      ->select('al.user_id, u.name, u.username')
      ->join('users u', 'u.id = al.user_id', 'left')
      ->where('al.user_id IS NOT NULL')
      ->groupBy('al.user_id')
      ->orderBy('u.name', 'ASC')
      ->get()
      ->getResultArray();

    return view('audit_logs/index', [
      'logs'       => $logs,
      'pager'      => [
        'total'    => $total,
        'per_page' => $perPage,
        'page'     => $page,
      ],
      'actionList' => $actionList,
      'userList'   => $userList,
      'q'          => $search,
      'filterAction' => $action,
      'filterUserId' => $userId,
      'dateFrom'   => $dateFrom,
      'dateTo'     => $dateTo,
      'sort'       => $sort,
      'dir'        => $dir,
      'title'      => 'Audit Log',
    ]);
  }
}
