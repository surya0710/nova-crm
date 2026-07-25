<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Records scheduler liveness for Platform Monitoring (Phase 14.9).
 */
class ScheduleHeartbeatCommand extends Command
{
    protected $signature = 'schedule:heartbeat';

    protected $description = 'Record that the application scheduler is running';

    public function handle(): int
    {
        Cache::forever('platform.scheduler.last_run', now()->toIso8601String());
        $this->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
