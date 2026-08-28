<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The test suite runs against an in-memory SQLite connection (no real
        // file on disk), but the command backs up a real database file - point
        // the sqlite connection at a temporary file that looks like one.
        $path = tempnam(sys_get_temp_dir(), 'ivy-test-db-').'.sqlite';
        file_put_contents($path, "SQLite format 3\0".str_repeat('x', 100));
        config(['database.connections.sqlite.database' => $path]);
    }

    public function test_backup_command_writes_a_restorable_gzip_copy_of_the_database(): void
    {
        Storage::fake('local');

        $this->artisan('backup:database')->assertExitCode(0);

        $files = Storage::disk('local')->files('backups');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.sqlite.gz', $files[0]);

        $decompressed = gzdecode(Storage::disk('local')->get($files[0]));
        $this->assertStringStartsWith("SQLite format 3\0", $decompressed);
    }

    public function test_backup_command_prunes_backups_older_than_the_retention_window(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('backups/backup-old.sqlite.gz', 'old-content');
        touch(Storage::disk('local')->path('backups/backup-old.sqlite.gz'), now()->subDays(30)->timestamp);

        $this->artisan('backup:database', ['--keep' => 14])->assertExitCode(0);

        Storage::disk('local')->assertMissing('backups/backup-old.sqlite.gz');
        $this->assertCount(1, Storage::disk('local')->files('backups'));
    }
}
