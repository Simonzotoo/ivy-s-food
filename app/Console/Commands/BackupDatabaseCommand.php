<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Takes a point-in-time copy of the database and stores it (gzip-compressed)
 * on the private "local" disk under backups/, then deletes backups older
 * than the retention window. Works against both the local SQLite database
 * (a straight file copy) and a production MySQL/MariaDB database (via
 * mysqldump, with credentials passed through a short-lived defaults file
 * rather than a CLI argument so they never appear in the process list).
 *
 * These backups live on the same disk as the application. They are a
 * safety net against a bad migration or accidental data change, not a
 * substitute for an off-server copy - download or sync backups/ elsewhere
 * periodically (or point the "local" disk at a remote filesystem).
 */
#[Signature('backup:database {--keep=14 : Days of backups to retain}')]
#[Description('Back up the database to storage/app/backups and prune old backups')]
class BackupDatabaseCommand extends Command
{
    public function handle(): int
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $timestamp = now()->format('Y-m-d-His');

        try {
            $path = match ($driver) {
                'sqlite' => $this->backupSqlite($connection, $timestamp),
                'mysql' => $this->backupMysql($connection, $timestamp),
                default => throw new \RuntimeException("No backup strategy for database driver [{$driver}]."),
            };
        } catch (Throwable $e) {
            $this->error('Database backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $sizeKb = round(Storage::disk('local')->size($path) / 1024);
        $this->info("Backup written: {$path} ({$sizeKb} KB)");

        $this->prune((int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function backupSqlite(string $connection, string $timestamp): string
    {
        $database = config("database.connections.{$connection}.database");

        if (! is_file($database)) {
            throw new \RuntimeException("SQLite database file not found at [{$database}].");
        }

        $path = "backups/backup-{$timestamp}.sqlite.gz";
        $compressed = gzencode((string) file_get_contents($database), 9);

        Storage::disk('local')->put($path, $compressed);

        return $path;
    }

    private function backupMysql(string $connection, string $timestamp): string
    {
        $config = config("database.connections.{$connection}");
        $path = "backups/backup-{$timestamp}.sql.gz";

        $defaultsFile = tempnam(sys_get_temp_dir(), 'ivy-db-backup-');
        file_put_contents($defaultsFile, sprintf(
            "[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n",
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '3306',
            $config['username'] ?? '',
            $config['password'] ?? ''
        ));
        chmod($defaultsFile, 0600);

        try {
            $process = new Process([
                'mysqldump',
                '--defaults-extra-file='.$defaultsFile,
                '--single-transaction',
                '--skip-lock-tables',
                $config['database'],
            ]);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('mysqldump exited with an error: '.$process->getErrorOutput());
            }

            Storage::disk('local')->put($path, gzencode($process->getOutput(), 9));
        } finally {
            unlink($defaultsFile);
        }

        return $path;
    }

    private function prune(int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays);
        $removed = 0;

        foreach (Storage::disk('local')->files('backups') as $file) {
            $lastModified = Storage::disk('local')->lastModified($file);

            if ($lastModified !== false && $lastModified < $cutoff->timestamp) {
                Storage::disk('local')->delete($file);
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->info("Removed {$removed} backup(s) older than {$keepDays} days.");
        }
    }
}
