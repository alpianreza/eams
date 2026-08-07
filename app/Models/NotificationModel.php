<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'actor_user_id', 'type', 'title', 'message', 'url', 'entity_type', 'entity_id', 'dedupe_key', 'read_at', 'email_status', 'whatsapp_status', 'created_at'];
    protected $useTimestamps = false;

    public function unreadForUser(int $userId, int $limit = 8): array
    {
        if (! $this->db->tableExists($this->table)) return [];
        return $this->where('user_id', $userId)->where('read_at', null)->orderBy('created_at', 'DESC')->findAll($limit);
    }

    public function unreadCount(int $userId): int
    {
        if (! $this->db->tableExists($this->table)) return 0;
        return $this->where('user_id', $userId)->where('read_at', null)->countAllResults();
    }
}
