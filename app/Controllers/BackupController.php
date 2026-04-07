<?php

namespace App\Controllers;

use App\Libraries\BackupManager;

class BackupController extends BaseController
{
    protected BackupManager $backupManager;

    public function __construct()
    {
        $this->backupManager = new BackupManager();
    }

    public function index()
    {
        return $this->render('backups/index', [
            'title' => 'Backup Sistem',
            'backups' => $this->buildBackupRows(),
            'backupDirectoryPath' => $this->backupManager->backupDirectory(),
            'usingExternalDrive' => $this->backupManager->usingExternalDrive(),
            'autoBackupStatus' => $this->autoBackupStatus(),
            'retentionDays' => BackupManager::RETENTION_DAYS,
            'defaultScheduleTime' => '01:00',
        ]);
    }

    public function createDatabase()
    {
        try {
            $this->backupManager->createDatabaseBackup();
            return $this->redirectSuccess('Backup database berhasil dibuat.');
        } catch (\Throwable $exception) {
            log_message('error', 'Backup database gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError('Backup database gagal dibuat.');
        }
    }

    public function createFiles()
    {
        try {
            $this->backupManager->createFilesBackup();
            return $this->redirectSuccess('Backup file berhasil dibuat.');
        } catch (\Throwable $exception) {
            log_message('error', 'Backup file gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError('Backup file gagal dibuat.');
        }
    }

    public function createFull()
    {
        try {
            $this->backupManager->createFullBackup();
            return $this->redirectSuccess('Backup penuh berhasil dibuat.');
        } catch (\Throwable $exception) {
            log_message('error', 'Backup penuh gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError('Backup penuh gagal dibuat.');
        }
    }

    public function upload()
    {
        try {
            $file = $this->request->getFile('backup_file');
            if ($file === null) {
                return $this->redirectError('File backup belum dipilih.');
            }

            $storedName = $this->backupManager->uploadBackup($file);
            return $this->redirectSuccess('Backup berhasil diunggah: ' . $storedName);
        } catch (\Throwable $exception) {
            log_message('error', 'Upload backup gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError($exception->getMessage() ?: 'Upload backup gagal.');
        }
    }

    public function download($fileName)
    {
        $safeName = basename(rawurldecode((string) $fileName));
        $filePath = $this->backupManager->resolveBackupFilePath($safeName);

        if (!is_file($filePath)) {
            return $this->redirectError('File backup tidak ditemukan.');
        }

        return $this->response->download($filePath, null)->setFileName($safeName);
    }

    public function delete($fileName)
    {
        try {
            $this->backupManager->deleteBackup((string) $fileName);
            return $this->redirectSuccess('File backup berhasil dihapus.');
        } catch (\Throwable $exception) {
            log_message('error', 'Hapus backup gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError('File backup gagal dihapus.');
        }
    }

    public function restoreDatabase($fileName)
    {
        try {
            $this->backupManager->restoreDatabase((string) $fileName);
            return $this->redirectSuccess('Restore database berhasil dijalankan.');
        } catch (\Throwable $exception) {
            log_message('error', 'Restore database gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError('Restore database gagal dijalankan.');
        }
    }

    public function restoreFiles($fileName)
    {
        try {
            $this->backupManager->restoreFiles((string) $fileName);
            return $this->redirectSuccess('Restore file upload berhasil dijalankan.');
        } catch (\Throwable $exception) {
            log_message('error', 'Restore file gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError('Restore file upload gagal dijalankan.');
        }
    }

    public function restoreFull($fileName)
    {
        try {
            $this->backupManager->restoreFull((string) $fileName);
            return $this->redirectSuccess('Restore penuh berhasil dijalankan.');
        } catch (\Throwable $exception) {
            log_message('error', 'Restore penuh gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError('Restore penuh gagal dijalankan.');
        }
    }

    public function enableAutoBackup()
    {
        try {
            $this->createScheduledTask('01:00');
            return $this->redirectSuccess('Backup otomatis harian berhasil diaktifkan. Jadwal jalan tiap hari pukul 01:00.');
        } catch (\Throwable $exception) {
            log_message('error', 'Aktivasi backup otomatis gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError('Backup otomatis harian gagal diaktifkan.');
        }
    }

    public function disableAutoBackup()
    {
        try {
            $this->deleteScheduledTask();
            return $this->redirectSuccess('Backup otomatis harian berhasil dimatikan.');
        } catch (\Throwable $exception) {
            log_message('error', 'Menonaktifkan backup otomatis gagal: {message}', ['message' => $exception->getMessage()]);
            return $this->redirectError('Backup otomatis harian gagal dimatikan.');
        }
    }

    protected function buildBackupRows(): array
    {
        return array_map(function (array $backup): array {
            $encodedName = rawurlencode($backup['name']);

            $backup['download_url'] = $this->relativePath('backups/download/' . $encodedName);
            $backup['delete_url'] = $this->relativePath('backups/delete/' . $encodedName);
            $backup['restore_database_url'] = $backup['can_restore_database']
                ? $this->relativePath('backups/restore-database/' . $encodedName)
                : null;
            $backup['restore_files_url'] = $backup['can_restore_files']
                ? $this->relativePath('backups/restore-files/' . $encodedName)
                : null;
            $backup['restore_full_url'] = $backup['can_restore_full']
                ? $this->relativePath('backups/restore-full/' . $encodedName)
                : null;

            return $backup;
        }, $this->backupManager->listBackupFiles());
    }

    protected function redirectSuccess(string $message)
    {
        return redirect()->to($this->relativePath('backups'))->with('success', $message);
    }

    protected function redirectError(string $message)
    {
        return redirect()->to($this->relativePath('backups'))->with('error', $message);
    }

    protected function relativePath(string $uri): string
    {
        $path = parse_url(base_url($uri), PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : base_url($uri);
    }

    protected function scheduledTaskName(): string
    {
        return 'EAMS Daily Backup';
    }

    protected function createScheduledTask(string $time): void
    {
        $time = preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '01:00';
        $sparkPath = rtrim((string) ROOTPATH, '\\/') . DIRECTORY_SEPARATOR . 'spark';
        $taskCommand = '"' . PHP_BINARY . '" "' . $sparkPath . '" backup:daily';
        $command = 'schtasks /Create /SC DAILY /ST ' . $time
            . ' /TN "' . $this->scheduledTaskName() . '" /TR ' . escapeshellarg($taskCommand) . ' /F 2>&1';

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException(implode(PHP_EOL, $output));
        }
    }

    protected function deleteScheduledTask(): void
    {
        $command = 'schtasks /Delete /TN "' . $this->scheduledTaskName() . '" /F 2>&1';
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $message = implode(PHP_EOL, $output);
            if (stripos($message, 'cannot find the file specified') !== false) {
                return;
            }

            throw new \RuntimeException($message);
        }
    }

    protected function autoBackupStatus(): array
    {
        $command = 'schtasks /Query /TN "' . $this->scheduledTaskName() . '" /FO LIST /V 2>&1';
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            return [
                'active' => false,
                'task_name' => $this->scheduledTaskName(),
                'message' => 'Backup otomatis harian belum aktif.',
                'next_run' => null,
                'run_as' => null,
                'command' => null,
            ];
        }

        $details = [];
        foreach ($output as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $details[$key] = $value;
        }

        return [
            'active' => true,
            'task_name' => $details['TaskName'] ?? ('\\' . $this->scheduledTaskName()),
            'message' => 'Backup otomatis harian aktif.',
            'next_run' => $details['Next Run Time'] ?? null,
            'run_as' => $details['Run As User'] ?? null,
            'command' => $details['Task To Run'] ?? null,
        ];
    }
}
