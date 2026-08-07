<?php

namespace App\Models;

use CodeIgniter\Model;

class AppSettingModel extends Model
{
    protected $table = 'app_settings';
    protected $primaryKey = 'id';
    protected $allowedFields = ['setting_key', 'setting_value', 'is_secret', 'updated_by', 'updated_at'];
    protected $useTimestamps = false;

    public function allAsMap(bool $includeSecrets = false): array
    {
        if (! $this->db->tableExists($this->table)) return [];
        $rows = $this->findAll();
        $settings = [];
        foreach ($rows as $row) {
            if (! $includeSecrets && (int) ($row['is_secret'] ?? 0) === 1) continue;
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        return $settings;
    }

    public function value(string $key, ?string $default = null, bool $includeSecrets = false): ?string
    {
        if (! $this->db->tableExists($this->table)) return $default;
        $row = $this->where('setting_key', $key)->first();
        if (! $row || (! $includeSecrets && (int) ($row['is_secret'] ?? 0) === 1)) return $default;
        return (string) ($row['setting_value'] ?? $default);
    }

    public function put(string $key, ?string $value, bool $secret = false, ?int $userId = null): void
    {
        $payload = ['setting_value' => $value, 'is_secret' => $secret ? 1 : 0, 'updated_by' => $userId, 'updated_at' => date('Y-m-d H:i:s')];
        $existing = $this->where('setting_key', $key)->first();
        if ($existing) $this->update($existing['id'], $payload);
        else $this->insert(array_merge(['setting_key' => $key], $payload));
    }
}
