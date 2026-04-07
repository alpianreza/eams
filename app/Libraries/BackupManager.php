<?php

namespace App\Libraries;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class BackupManager
{
    public const RETENTION_DAYS = 30;

    public function listBackupFiles(): array
    {
        $directory = $this->backupDirectory();
        $items = [];

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }

            $file = new File($path);
            $zipInspection = $this->inspectZipBackup($path);

            $items[] = [
                'name' => $file->getFilename(),
                'size' => $this->formatBytes((int) $file->getSize()),
                'size_bytes' => (int) $file->getSize(),
                'modified_ts' => (int) $file->getMTime(),
                'modified_at' => date('d M Y H:i', $file->getMTime()),
                'type' => $this->detectBackupType($file->getFilename(), $zipInspection),
                'can_restore_database' => $this->canRestoreDatabase($path, $zipInspection),
                'can_restore_files' => $this->canRestoreFiles($path, $zipInspection),
                'can_restore_full' => $this->canRestoreFull($path, $zipInspection),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            return ($right['modified_ts'] <=> $left['modified_ts']) ?: strcmp($left['name'], $right['name']);
        });

        return $items;
    }

    public function createDatabaseBackup(?string $fileName = null): string
    {
        @set_time_limit(0);

        $fileName = $fileName ?: 'backup-database-' . date('Ymd-His') . '.sql';
        $filePath = $this->backupDirectory() . DIRECTORY_SEPARATOR . $fileName;

        if (file_put_contents($filePath, $this->generateDatabaseDump()) === false) {
            throw new RuntimeException('Gagal menulis file backup database.');
        }

        $this->cleanupOldBackups(self::RETENTION_DAYS);

        return $fileName;
    }

    public function createFilesBackup(?string $fileName = null): string
    {
        @set_time_limit(0);

        $fileName = $fileName ?: 'backup-file-' . date('Ymd-His') . '.zip';
        $filePath = $this->backupDirectory() . DIRECTORY_SEPARATOR . $fileName;

        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuka file ZIP backup.');
        }

        $this->appendProjectDirectories($zip);
        $zip->close();

        $this->cleanupOldBackups(self::RETENTION_DAYS);

        return $fileName;
    }

    public function createFullBackup(?string $fileName = null, string $manifestType = 'full'): string
    {
        @set_time_limit(0);

        $fileName = $fileName ?: 'backup-penuh-' . date('Ymd-His') . '.zip';
        $filePath = $this->backupDirectory() . DIRECTORY_SEPARATOR . $fileName;

        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuka file ZIP backup penuh.');
        }

        $zip->addFromString('database.sql', $this->generateDatabaseDump());
        $zip->addFromString('manifest.json', json_encode([
            'generated_at' => date('Y-m-d H:i:s'),
            'application' => 'EAMS',
            'type' => $manifestType,
            'retention_days' => self::RETENTION_DAYS,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->appendProjectDirectories($zip);
        $zip->close();

        $this->cleanupOldBackups(self::RETENTION_DAYS);

        return $fileName;
    }

    public function createDailyBackup(): string
    {
        return $this->createFullBackup('backup-harian-' . date('Ymd-His') . '.zip', 'daily');
    }

    public function uploadBackup(UploadedFile $file): string
    {
        if (!$file->isValid()) {
            throw new RuntimeException('File backup gagal diunggah.');
        }

        $extension = strtolower((string) $file->getClientExtension());
        if (!in_array($extension, ['sql', 'zip'], true)) {
            throw new RuntimeException('Format file backup harus .sql atau .zip.');
        }

        $originalName = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $file->getClientName());
        $originalName = trim((string) $originalName, '-.');
        if ($originalName === '') {
            $originalName = 'backup-upload.' . $extension;
        }

        $targetName = 'upload-backup-' . date('Ymd-His') . '-' . $originalName;
        $targetName = $this->ensureUniqueFileName($targetName);

        $file->move($this->backupDirectory(), $targetName);
        $this->cleanupOldBackups(self::RETENTION_DAYS);

        return $targetName;
    }

    public function restoreDatabase(string $fileName): void
    {
        @set_time_limit(0);

        $filePath = $this->resolveBackupFilePath($fileName);
        if (!is_file($filePath)) {
            throw new RuntimeException('File backup tidak ditemukan.');
        }

        $this->runDatabaseImport($this->extractDatabaseSql($filePath));
    }

    public function restoreFiles(string $fileName): void
    {
        @set_time_limit(0);

        $filePath = $this->resolveBackupFilePath($fileName);
        if (!is_file($filePath)) {
            throw new RuntimeException('File backup tidak ditemukan.');
        }

        $this->restoreUploadDirectories($filePath);
    }

    public function restoreFull(string $fileName): void
    {
        @set_time_limit(0);

        $filePath = $this->resolveBackupFilePath($fileName);
        if (!is_file($filePath)) {
            throw new RuntimeException('File backup tidak ditemukan.');
        }

        $zipInspection = $this->inspectZipBackup($filePath);
        if (!$this->canRestoreFull($filePath, $zipInspection)) {
            throw new RuntimeException('File backup ini tidak memiliki paket restore penuh.');
        }

        $this->runDatabaseImport($this->extractDatabaseSql($filePath));
        $this->restoreUploadDirectories($filePath);
    }

    public function deleteBackup(string $fileName): void
    {
        $filePath = $this->resolveBackupFilePath($fileName);
        if (!is_file($filePath)) {
            throw new RuntimeException('File backup tidak ditemukan.');
        }

        if (!@unlink($filePath)) {
            throw new RuntimeException('File backup gagal dihapus.');
        }
    }

    public function cleanupOldBackups(int $retentionDays = self::RETENTION_DAYS): int
    {
        $directory = $this->backupDirectory();
        $threshold = strtotime('-' . max(1, $retentionDays) . ' days');
        $removed = 0;

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }

            if (@filemtime($path) === false || filemtime($path) >= $threshold) {
                continue;
            }

            if (@unlink($path)) {
                $removed++;
            }
        }

        return $removed;
    }

    public function backupDirectory(): string
    {
        $directory = $this->preferredBackupDirectory();

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder backup tidak bisa dibuat.');
        }

        return $directory;
    }

    public function usingExternalDrive(): bool
    {
        return str_starts_with(strtoupper($this->backupDirectory()), 'D:\\');
    }

    public function resolveBackupFilePath(string $fileName): string
    {
        $safeName = basename(rawurldecode($fileName));
        $preferred = $this->backupDirectory() . DIRECTORY_SEPARATOR . $safeName;

        if (is_file($preferred)) {
            return $preferred;
        }

        $fallback = rtrim((string) WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($fallback)) {
            return $fallback;
        }

        return $preferred;
    }

    protected function preferredBackupDirectory(): string
    {
        if (DIRECTORY_SEPARATOR === '\\' && is_dir('D:\\')) {
            return 'D:\\EAMS-Backups';
        }

        return rtrim((string) WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'backups';
    }

    protected function ensureUniqueFileName(string $fileName): string
    {
        $directory = $this->backupDirectory();
        $candidate = $fileName;
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $counter = 1;

        while (is_file($directory . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $baseName . '-' . $counter . ($extension !== '' ? '.' . $extension : '');
            $counter++;
        }

        return $candidate;
    }

    protected function inspectZipBackup(string $filePath): array
    {
        $result = [
            'has_database' => false,
            'has_files' => false,
            'is_full' => false,
        ];

        if (strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION)) !== 'zip') {
            return $result;
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return $result;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (!is_string($entry) || $entry === '') {
                continue;
            }

            $entry = str_replace('\\', '/', $entry);
            if (strcasecmp($entry, 'database.sql') === 0) {
                $result['has_database'] = true;
            }

            if (
                str_starts_with($entry, 'public/uploads/') ||
                str_starts_with($entry, 'writable/uploads/')
            ) {
                $result['has_files'] = true;
            }

            if ($result['has_database'] && $result['has_files']) {
                break;
            }
        }

        $zip->close();
        $result['is_full'] = $result['has_database'] && $result['has_files'];

        return $result;
    }

    protected function detectBackupType(string $fileName, array $zipInspection): array
    {
        $name = strtolower($fileName);

        if (str_starts_with($name, 'backup-harian-')) {
            return ['label' => 'Harian', 'class' => 'text-bg-dark'];
        }

        if (str_starts_with($name, 'backup-penuh-') || $zipInspection['is_full']) {
            return ['label' => 'Penuh', 'class' => 'text-bg-primary'];
        }

        if (str_starts_with($name, 'backup-database-')) {
            return ['label' => 'Database', 'class' => 'text-bg-success'];
        }

        if (str_starts_with($name, 'backup-file-')) {
            return ['label' => 'File', 'class' => 'text-bg-info'];
        }

        if (str_starts_with($name, 'upload-backup-')) {
            return ['label' => 'Upload', 'class' => 'text-bg-warning'];
        }

        return ['label' => 'Backup', 'class' => 'text-bg-secondary'];
    }

    protected function canRestoreDatabase(string $filePath, array $zipInspection): bool
    {
        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'sql') {
            return true;
        }

        return $extension === 'zip' && $zipInspection['has_database'];
    }

    protected function canRestoreFiles(string $filePath, array $zipInspection): bool
    {
        return strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION)) === 'zip' && $zipInspection['has_files'];
    }

    protected function canRestoreFull(string $filePath, array $zipInspection): bool
    {
        return strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION)) === 'zip' && $zipInspection['is_full'];
    }

    protected function generateDatabaseDump(): string
    {
        $db = db_connect();
        $databaseName = $db->database ?? 'eams';
        $lines = [];

        $lines[] = '-- EAMS Database Backup';
        $lines[] = '-- Generated at: ' . date('Y-m-d H:i:s');
        $lines[] = '-- Database: ' . $databaseName;
        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = '';

        foreach ($db->listTables() as $table) {
            $tableName = (string) $table;
            $escapedTable = str_replace('`', '``', $tableName);
            $createRow = $db->query('SHOW CREATE TABLE `' . $escapedTable . '`')->getRowArray();

            if (!$createRow) {
                continue;
            }

            $createSql = '';
            foreach ($createRow as $key => $value) {
                if (stripos((string) $key, 'Create Table') !== false) {
                    $createSql = (string) $value;
                    break;
                }
            }

            $lines[] = '-- --------------------------------------------------------';
            $lines[] = '-- Table structure for `' . $tableName . '`';
            $lines[] = '-- --------------------------------------------------------';
            $lines[] = 'DROP TABLE IF EXISTS `' . $tableName . '`;';
            $lines[] = rtrim($createSql, ';') . ';';
            $lines[] = '';

            $rows = $db->table($tableName)->get()->getResultArray();
            if (empty($rows)) {
                continue;
            }

            $columns = array_keys($rows[0]);
            $columnSql = implode(', ', array_map(
                static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`',
                $columns
            ));

            $lines[] = '-- Data for table `' . $tableName . '`';

            foreach ($rows as $row) {
                $values = [];

                foreach ($columns as $column) {
                    $value = $row[$column] ?? null;

                    if ($value === null) {
                        $values[] = 'NULL';
                        continue;
                    }

                    if (is_bool($value)) {
                        $values[] = $value ? '1' : '0';
                        continue;
                    }

                    $values[] = $db->escape($value);
                }

                $lines[] = 'INSERT INTO `' . $tableName . '` (' . $columnSql . ') VALUES (' . implode(', ', $values) . ');';
            }

            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    protected function appendProjectDirectories(ZipArchive $zip): void
    {
        $directories = [
            'public/uploads' => rtrim((string) FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads',
            'writable/uploads' => rtrim((string) WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads',
        ];

        foreach ($directories as $prefix => $directory) {
            $this->addDirectoryToZip($zip, $directory, $prefix);
        }
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $directory, string $zipPrefix): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $zip->addEmptyDir(trim(str_replace('\\', '/', $zipPrefix), '/'));

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $realPath = (string) $item->getRealPath();
            if ($realPath === '') {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($realPath, strlen(rtrim($directory, '\\/')) + 1));
            $zipPath = trim(str_replace('\\', '/', $zipPrefix), '/') . '/' . $relativePath;

            if ($item->isDir()) {
                $zip->addEmptyDir($zipPath);
                continue;
            }

            $zip->addFile($realPath, $zipPath);
        }
    }

    protected function extractDatabaseSql(string $filePath): string
    {
        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'sql') {
            $sql = file_get_contents($filePath);
            if ($sql === false || trim($sql) === '') {
                throw new RuntimeException('Isi backup database kosong.');
            }

            return $sql;
        }

        if ($extension !== 'zip') {
            throw new RuntimeException('Format file backup database tidak didukung.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('File ZIP backup tidak bisa dibuka.');
        }

        $sql = $zip->getFromName('database.sql');
        $zip->close();

        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('File database.sql tidak ditemukan di dalam backup penuh.');
        }

        return $sql;
    }

    protected function runDatabaseImport(string $sql): void
    {
        $db = Database::connect();
        $conn = $db->connID;

        if ($conn instanceof \mysqli) {
            if (!$conn->multi_query($sql)) {
                throw new RuntimeException('Gagal memulai import database: ' . $conn->error);
            }

            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());

            if ($conn->errno) {
                throw new RuntimeException('Import database gagal: ' . $conn->error);
            }

            return;
        }

        $statements = array_values(array_filter(array_map(
            'trim',
            preg_split('/;\s*(?:\r\n|\r|\n|$)/', $sql) ?: []
        )));

        foreach ($statements as $statement) {
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }

            $db->query($statement);
        }
    }

    protected function restoreUploadDirectories(string $filePath): void
    {
        if (strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION)) !== 'zip') {
            throw new RuntimeException('Restore file hanya bisa dijalankan dari backup ZIP.');
        }

        $tempRoot = rtrim((string) WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'backup-restore-' . uniqid('', true);
        if (!is_dir($tempRoot) && !mkdir($tempRoot, 0775, true) && !is_dir($tempRoot)) {
            throw new RuntimeException('Folder sementara restore tidak bisa dibuat.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('File ZIP backup tidak bisa dibuka.');
        }

        if (!$zip->extractTo($tempRoot)) {
            $zip->close();
            throw new RuntimeException('Gagal mengekstrak file backup.');
        }
        $zip->close();

        try {
            $pairs = [
                $tempRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' => rtrim((string) FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads',
                $tempRoot . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'uploads' => rtrim((string) WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads',
            ];

            $restoredAny = false;
            foreach ($pairs as $source => $target) {
                if (!is_dir($source)) {
                    continue;
                }

                $this->mirrorDirectory($source, $target);
                $restoredAny = true;
            }

            if (!$restoredAny) {
                throw new RuntimeException('Folder upload tidak ditemukan di dalam file backup.');
            }
        } finally {
            $this->deleteDirectory($tempRoot);
        }
    }

    protected function mirrorDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
            throw new RuntimeException('Folder tujuan restore tidak bisa dibuat.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $realPath = (string) $item->getRealPath();
            if ($realPath === '') {
                continue;
            }

            $relativePath = substr($realPath, strlen(rtrim($source, '\\/')) + 1);
            $targetPath = rtrim($destination, '\\/') . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
                    throw new RuntimeException('Folder restore tidak bisa dibuat.');
                }
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new RuntimeException('Folder target file restore tidak bisa dibuat.');
            }

            if (!copy($realPath, $targetPath)) {
                throw new RuntimeException('Gagal menyalin file restore: ' . $relativePath);
            }
        }
    }

    protected function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($directory);
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 2) . ' ' . $units[$power];
    }
}
