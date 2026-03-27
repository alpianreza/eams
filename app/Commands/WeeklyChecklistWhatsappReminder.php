<?php

namespace App\Commands;

use App\Models\ChecklistLogModel;
use App\Models\ComplianceInventoryModel;
use App\Services\WhatsAppService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class WeeklyChecklistWhatsappReminder extends BaseCommand
{
    protected $group = 'Compliance';
    protected $name = 'notify:weekly-checklist';
    protected $description = 'Kirim notifikasi WhatsApp mingguan untuk user yang masih punya checklist pending.';
    protected $usage = 'notify:weekly-checklist [--dry-run] [--date YYYY-MM-DD] [--username USERNAME] [--max-items N]';
    protected $options = [
        '--dry-run' => 'Tampilkan hasil simulasi tanpa kirim WhatsApp.',
        '--date' => 'Tanggal acuan periode. Format: YYYY-MM-DD. Default: hari ini.',
        '--username' => 'Filter 1 user berdasarkan username.',
        '--max-items' => 'Maksimal item pending yang ditulis ke pesan. Default: 15.',
    ];

    public function run(array $params)
    {
        helper('period');

        $dryRun = $this->cliFlag('dry-run');
        $runDate = $this->cliOption('date', date('Y-m-d'));
        $usernameFilter = $this->cliOption('username', '');
        $maxItems = (int) $this->cliOption('max-items', '15');
        $maxItems = $maxItems > 0 ? $maxItems : 15;

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $runDate)) {
            CLI::error('Format --date tidak valid. Gunakan YYYY-MM-DD.');
            return;
        }

        $wa = new WhatsAppService();
        if (! $dryRun && ! $wa->canSend()) {
            CLI::error('WhatsApp belum siap kirim. Cek .env: whatsapp.enabled=true dan whatsapp.fonnteToken terisi.');
            return;
        }

        $db = Database::connect();
        $usersBuilder = $db->table('users')
            ->where('status', 'active');

        if ($usernameFilter !== '') {
            $usersBuilder->where('username', $usernameFilter);
        }

        $users = $usersBuilder->get()->getResultArray();
        if (empty($users)) {
            CLI::write('Tidak ada user aktif yang diproses.');
            return;
        }

        $namePhoneMap = $this->parseNamePhoneMap(config('WhatsApp')->namePhoneMap);
        $inventoryModel = new ComplianceInventoryModel();
        $logModel = new ChecklistLogModel();
        $publicUrl = env('app.publicURL') ?: config('App')->baseURL;
        $appUrl = rtrim((string) $publicUrl, '/');

        $sent = 0;
        $failed = 0;
        $skippedNoPhone = 0;
        $skippedNoPending = 0;

        foreach ($users as $user) {
            $pendingItems = $this->collectPendingItemsForUser(
                (string) ($user['name'] ?? ''),
                $runDate,
                $inventoryModel,
                $logModel
            );

            if (empty($pendingItems)) {

                CLI::write("[SKIP NO TASK] {$user['name']}", 'yellow');

                // DEBUG TAMBAHAN
                CLI::write("   -> Tidak ada pending checklist");

                $skippedNoPending++;
                continue;
            }

            $phone = $this->resolveWhatsAppNumber($user, $namePhoneMap);
            $displayName = (string) ($user['name'] ?? $user['username'] ?? 'User');
            $message = $this->buildReminderMessage($displayName, $pendingItems, $maxItems, $appUrl, $runDate);

            if ($phone === null) {
                if ($dryRun) {
                    CLI::write("---- [DRY-RUN] {$displayName} (no-phone) ----");
                    CLI::write($message);
                    CLI::write('');
                    $sent++;
                    $skippedNoPhone++;
                    continue;
                }

                $skippedNoPhone++;
                CLI::write("[SKIP] {$displayName}: nomor WA belum tersedia.");
                continue;
            }

            if ($dryRun) {
                CLI::write("---- [DRY-RUN] {$displayName} ({$phone}) ----");
                CLI::write($message);
                CLI::write('');
                $sent++;
                continue;
            }

            $result = $wa->sendMessage($phone, $message);
            if ($result['success']) {
                $sent++;
                CLI::write("[OK] {$displayName} ({$phone})");
            } else {
                $failed++;
                CLI::error("[FAILED] {$displayName} ({$phone}) => {$result['response']}");
            }
        }

        CLI::newLine();
        CLI::write('=== Ringkasan Weekly WhatsApp Reminder ===', 'yellow');
        CLI::write('Tanggal acuan : ' . $runDate);
        CLI::write('Mode          : ' . ($dryRun ? 'DRY-RUN' : 'LIVE'));
        CLI::write('Terkirim      : ' . $sent);
        CLI::write('Gagal         : ' . $failed);
        CLI::write('Skip no phone : ' . $skippedNoPhone);
        CLI::write('Skip no task  : ' . $skippedNoPending);
    }

    private function cliFlag(string $name): bool
    {
        if (CLI::getOption($name) !== null || CLI::getOption('--' . $name) !== null) {
            return true;
        }

        $needle = '--' . $name;
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if ($arg === $needle || str_starts_with($arg, $needle . '=')) {
                return true;
            }
        }

        return false;
    }

    private function cliOption(string $name, string $default = ''): string
    {
        $value = CLI::getOption($name);
        if ($value === null) {
            $value = CLI::getOption('--' . $name);
        }

        if ($value === null) {
            $needle = '--' . $name . '=';
            foreach ($_SERVER['argv'] ?? [] as $arg) {
                if (str_starts_with($arg, $needle)) {
                    return trim(substr($arg, strlen($needle)));
                }
            }

            return $default;
        }

        if (is_array($value)) {
            $value = end($value);
        }

        return trim((string) $value);
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    private function collectPendingItemsForUser(
        string $userName,
        string $runDate,
        ComplianceInventoryModel $inventoryModel,
        ChecklistLogModel $logModel
    ): array {
        $needle = $this->buildPicSearchKey($userName);
        if ($needle === '') {
            return [];
        }

        $inventories = $inventoryModel
            ->select('
        compliance_inventory.id,
        compliance_inventory.asset_code,
        compliance_inventory.specific_area,
        compliance_inventory.pic,
        asset_item_types.name AS item_name,
        asset_item_types.checklist_frequency
    ')
            ->join('asset_item_types', 'asset_item_types.id = compliance_inventory.item_type_id')
            ->where('compliance_inventory.active', 1)
            ->findAll();

        // 🔥 FILTER PIC MANUAL (PARSING)
        $filteredInventories = [];

        $userName = strtolower(trim($userName));

        // bersihin simbol
        $userName = preg_replace('/[^a-z0-9\s]/', '', $userName);
        $userWords = explode(' ', $userName);

        foreach ($inventories as $inv) {

            if (empty($inv['pic'])) continue;

            $pics = preg_split('/[-,\n]+/', $inv['pic']);

            $match = false;

            foreach ($pics as $pic) {
                $pic = strtolower(trim($pic));
                $pic = preg_replace('/[^a-z0-9\s]/', '', $pic);

                if ($pic === '') continue;

                $picWords = explode(' ', $pic);

                // 🔥 MINIMAL 1 KATA HARUS MATCH
                foreach ($userWords as $word) {
                    if (in_array($word, $picWords)) {
                        $match = true;
                        break 2; // keluar 2 loop
                    }
                }
            }

            if ($match) {
                $filteredInventories[] = $inv;
            }
        }

        // 🔥 override hasil
        $inventories = $filteredInventories;

        $pending = [];

        foreach ($inventories as $inv) {
            $frequency = strtolower((string) ($inv['checklist_frequency'] ?? ''));
            if (! in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
                continue;
            }

            $periodKey = generate_period_key($frequency, $runDate);
            $exists = $logModel
                ->where('inventory_id', $inv['id'])
                ->where('period_key', $periodKey)
                ->countAllResults();

            if ((int) $exists > 0) {
                continue;
            }

            $pending[] = [
                'item_name' => (string) ($inv['item_name'] ?? '-'),
                'asset_code' => (string) ($inv['asset_code'] ?? '-'),
                'area' => (string) ($inv['specific_area'] ?? '-'),
                'frequency' => ucfirst($frequency),
                'period_label' => period_label($frequency, $periodKey),
            ];
        }

        return $pending;
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, string> $namePhoneMap
     */
    private function resolveWhatsAppNumber(array $user, array $namePhoneMap): ?string
    {
        $phoneFields = [
            'wa_number',
            'whatsapp_number',
            'phone',
            'phone_number',
            'mobile',
            'mobile_number',
            'no_hp',
            'no_telp',
            'telp',
        ];

        foreach ($phoneFields as $field) {
            if (! array_key_exists($field, $user)) {
                continue;
            }

            $normalized = $this->normalizePhone((string) $user[$field]);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        $name = (string) ($user['name'] ?? '');
        $nameKey = $this->normalizeName($name);
        if ($nameKey !== '' && isset($namePhoneMap[$nameKey])) {
            return $namePhoneMap[$nameKey];
        }

        $firstTwo = $this->buildPicSearchKey($name);
        $firstTwoKey = $this->normalizeName($firstTwo);
        if ($firstTwoKey !== '' && isset($namePhoneMap[$firstTwoKey])) {
            return $namePhoneMap[$firstTwoKey];
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function parseNamePhoneMap(string $raw): array
    {
        $map = [];
        if (trim($raw) === '') {
            return $map;
        }

        $pairs = preg_split('/[\r\n,;]+/', $raw) ?: [];
        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if ($pair === '' || strpos($pair, ':') === false) {
                continue;
            }

            [$name, $phone] = array_map('trim', explode(':', $pair, 2));
            if ($name === '' || $phone === '') {
                continue;
            }

            $normalizedPhone = $this->normalizePhone($phone);
            $normalizedName = $this->normalizeName($name);
            if ($normalizedPhone === null || $normalizedName === '') {
                continue;
            }

            $map[$normalizedName] = $normalizedPhone;
        }

        return $map;
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        if (! str_starts_with($digits, '62')) {
            return null;
        }

        $length = strlen($digits);
        if ($length < 10 || $length > 16) {
            return null;
        }

        return $digits;
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        return $name;
    }

    private function buildPicSearchKey(string $name): string
    {
        $name = preg_replace('/\s+/', ' ', trim($name)) ?? '';
        if ($name === '') {
            return '';
        }

        $parts = explode(' ', $name);
        return trim(implode(' ', array_slice($parts, 0, 2)));
    }

    /**
     * @param array<int, array<string, string|int>> $pendingItems
     */
    private function buildReminderMessage(
        string $name,
        array $pendingItems,
        int $maxItems,
        string $appUrl,
        string $runDate
    ): string {
        $dateLabel = date('d M Y', strtotime($runDate));
        $total = count($pendingItems);
        $shownItems = array_slice($pendingItems, 0, $maxItems);

        $lines = [];
        $lines[] = "Halo {$name},";
        $lines[] = '';
        $lines[] = "Reminder checklist mingguan EAMS ({$dateLabel}).";
        $lines[] = 'Item yang belum di-checklist:';

        foreach ($shownItems as $idx => $item) {
            $num = $idx + 1;
            $itemName = (string) ($item['item_name'] ?? '-');
            $assetCode = (string) ($item['asset_code'] ?? '-');
            $area = (string) ($item['area'] ?? '-');
            $periodLabel = (string) ($item['period_label'] ?? '-');
            $frequency = (string) ($item['frequency'] ?? '-');

            $lines[] = "{$num}. {$itemName} ({$assetCode}) - {$area} | {$frequency} {$periodLabel}";
        }

        if ($total > $maxItems) {
            $remaining = $total - $maxItems;
            $lines[] = "... dan {$remaining} item lainnya.";
        }

        $lines[] = '';
        $lines[] = 'Mohon segera lengkapi checklist di: ' . $appUrl . '/home';
        $lines[] = 'Terima kasih.';

        return implode("\n", $lines);
    }
}
