<?php

namespace App\Commands;

use App\Libraries\BackupManager;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DailyBackup extends BaseCommand
{
    protected $group = 'System';
    protected $name = 'backup:daily';
    protected $description = 'Membuat backup penuh harian dan membersihkan backup lama otomatis.';

    public function run(array $params)
    {
        $manager = new BackupManager();

        $fileName = $manager->createDailyBackup();
        $deleted = $manager->cleanupOldBackups(BackupManager::RETENTION_DAYS);

        CLI::write('Backup harian berhasil dibuat: ' . $fileName, 'green');
        CLI::write('Folder backup: ' . $manager->backupDirectory(), 'yellow');
        CLI::write('Retensi aktif: ' . BackupManager::RETENTION_DAYS . ' hari', 'yellow');
        CLI::write('Backup lama yang dibersihkan: ' . $deleted, 'yellow');
    }
}
