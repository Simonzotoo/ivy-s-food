<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Retention policy: an import's audit record (ImportBatch/ImportError rows) is kept
 * forever, but the raw uploaded file itself is only needed while a review/commit is
 * pending. Once a batch reaches a terminal state and is older than 30 days, the stored
 * file is deleted and file_path is cleared - the batch row and its counts remain for audit.
 */
#[Signature('imports:prune {--days=30}')]
#[Description('Delete stored import files older than the retention window for completed imports')]
class ImportsPruneCommand extends Command
{
    private const TERMINAL_STATUSES = [
        'applied', 'applied_with_errors', 'rolled_back', 'rolled_back_partial',
        'rollback_not_supported', 'error', 'commit_blocked',
    ];

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $pruned = 0;

        ImportBatch::query()
            ->whereNotNull('file_path')
            ->whereIn('status', self::TERMINAL_STATUSES)
            ->where('updated_at', '<', $cutoff)
            ->each(function (ImportBatch $batch) use (&$pruned): void {
                if ($batch->file_disk && $batch->file_path && Storage::disk($batch->file_disk)->exists($batch->file_path)) {
                    Storage::disk($batch->file_disk)->delete($batch->file_path);
                }
                $batch->update(['file_path' => null]);
                $pruned++;
            });

        $this->info("Pruned stored files for {$pruned} import(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
