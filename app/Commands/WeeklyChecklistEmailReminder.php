<?php

namespace App\Commands;

use App\Libraries\NotificationService;
use App\Models\ChecklistLogModel;
use App\Models\ComplianceInventoryModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class WeeklyChecklistEmailReminder extends BaseCommand
{
    protected $group = 'Compliance';
    protected $name = 'notify:weekly-checklist-email';
    protected $description = 'Kirim reminder checklist pending melalui notifikasi aplikasi dan email.';
    protected $usage = 'notify:weekly-checklist-email [--dry-run] [--date YYYY-MM-DD] [--username USERNAME] [--max-items N]';
    protected $options = ['--dry-run' => 'Simulasi tanpa membuat notifikasi atau mengirim email.', '--date' => 'Tanggal acuan YYYY-MM-DD.', '--username' => 'Batasi ke satu username.', '--max-items' => 'Maksimal item dalam email.'];

    public function run(array $params)
    {
        helper('period');
        $dryRun = $this->flag('dry-run'); $date = $this->option('date', date('Y-m-d')); $username = $this->option('username'); $maxItems = max(1, (int) $this->option('max-items', '15'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { CLI::error('Format --date harus YYYY-MM-DD.'); return; }
        $db = Database::connect(); $builder = $db->table('users')->where('status', 'active'); if ($username !== '') $builder->where('username', $username);
        $users = $builder->get()->getResultArray(); $sent = 0; $skipped = 0;
        foreach ($users as $user) {
            $email = trim((string) ($user['email'] ?? ''));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) { $skipped++; CLI::write('[SKIP EMAIL] ' . ($user['name'] ?? $user['username']), 'yellow'); continue; }
            $pending = $this->pendingForUser((int) $user['id'], $date);
            if ($pending === []) { $skipped++; continue; }
            $message = $this->message((string) ($user['name'] ?? $user['username']), $pending, $maxItems, $date);
            if ($dryRun) { CLI::write('--- ' . $email . ' ---', 'yellow'); CLI::write($message); CLI::newLine(); $sent++; continue; }
            (new NotificationService())->sendToUser((int) $user['id'], ['actor_user_id' => null, 'type' => 'reminder', 'title' => 'Reminder checklist belum selesai', 'message' => $message, 'url' => '/home?show=all', 'entity_type' => 'checklist_reminder', 'dedupe_key' => 'weekly_email_reminder:' . $date . ':' . (int) $user['id'], 'send_email' => true, 'send_whatsapp' => false]);
            $sent++;
        }
        CLI::write('Email reminder diproses: ' . $sent . '; dilewati: ' . $skipped, 'green');
    }

    private function pendingForUser(int $userId, string $date): array
    {
        $inventories = (new ComplianceInventoryModel())->select('compliance_inventory.id, compliance_inventory.asset_code, compliance_inventory.specific_area, asset_item_types.name AS item_name, asset_item_types.checklist_frequency')->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')->where('compliance_inventory.active', 1)->assignedToUser($userId)->findAll();
        $logModel = new ChecklistLogModel(); $pending = [];
        foreach ($inventories as $inventory) {
            $frequency = strtolower((string) ($inventory['checklist_frequency'] ?? ''));
            if (! in_array($frequency, ['daily', 'weekly', 'monthly'], true)) continue;
            $periodKey = generate_period_key($frequency, $date);
            if ($logModel->where('inventory_id', $inventory['id'])->where('period_key', $periodKey)->countAllResults() > 0) continue;
            $pending[] = ['item' => $inventory['item_name'] ?? '-', 'code' => $inventory['asset_code'] ?? '-', 'area' => $inventory['specific_area'] ?? '-', 'period' => period_label($frequency, $periodKey)];
        }
        return $pending;
    }

    private function message(string $name, array $items, int $maxItems, string $date): string
    {
        $lines = ['Halo ' . $name . ',', '', 'Masih ada checklist EAMS yang belum selesai per ' . date('d M Y', strtotime($date)) . ':'];
        foreach (array_slice($items, 0, $maxItems) as $index => $item) $lines[] = ($index + 1) . '. ' . $item['item'] . ' (' . $item['code'] . ') - ' . $item['area'] . ' | ' . $item['period'];
        if (count($items) > $maxItems) $lines[] = '... dan ' . (count($items) - $maxItems) . ' item lainnya.';
        $lines[] = ''; $lines[] = 'Mohon segera lengkapi melalui EAMS.'; return implode("\n", $lines);
    }

    private function flag(string $name): bool { if (CLI::getOption($name) !== null || CLI::getOption('--' . $name) !== null) return true; foreach ($_SERVER['argv'] ?? [] as $arg) if ($arg === '--' . $name) return true; return false; }
    private function option(string $name, string $default = ''): string { $value = CLI::getOption($name) ?? CLI::getOption('--' . $name); if ($value !== null) return trim((string) (is_array($value) ? end($value) : $value)); $prefix = '--' . $name . '='; foreach ($_SERVER['argv'] ?? [] as $arg) if (str_starts_with($arg, $prefix)) return trim(substr($arg, strlen($prefix))); return $default; }
}
