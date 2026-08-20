<?php

namespace App\Console\Commands;

use App\Services\BroadcastService;
use Illuminate\Console\Command;

class RunScheduledBroadcasts extends Command
{
    protected $signature = 'broadcasts:run-scheduled';

    protected $description = 'Process due local/test broadcasts without sending external messages.';

    public function handle(BroadcastService $service): int
    {
        $count = $service->runScheduled();
        $this->info($count.' scheduled broadcasts processed.');

        return self::SUCCESS;
    }
}
