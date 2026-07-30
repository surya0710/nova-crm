<?php

use App\Services\Bulk\BulkOperationsService;
use App\Services\Export\ExportPlatformService;
use App\Services\Identity\BulkEmployeeUserProvisioningService;
use App\Services\Import\ImportPlatformService;
use App\Services\Marketing\Providers\MetaWebhookProcessor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('operations:fail-stale-queue-work {--minutes=120}', function (
    ImportPlatformService $imports,
    ExportPlatformService $exports,
    BulkOperationsService $bulk,
    BulkEmployeeUserProvisioningService $provisioning,
) {
    $before = now()->subMinutes(max(1, (int) $this->option('minutes')));
    $counts = [
        'imports' => $imports->failStale($before),
        'exports' => $exports->failStale($before),
        'bulk' => $bulk->failStale($before),
        'provisioning' => $provisioning->failStale($before),
    ];

    $this->info('Finalized '.array_sum($counts).' stale queue-owned records.');
})->purpose('Finalize stale domain work left by interrupted queue jobs');

Artisan::command('operations:reconcile-queue-state', function (
    BulkOperationsService $bulk,
    BulkEmployeeUserProvisioningService $provisioning,
) {
    $reconciled = $bulk->reconcileCounters() + $provisioning->reconcileCounters();
    $this->info("Reconciled {$reconciled} queue-owned counters.");
})->purpose('Reconcile persisted counters for queue-owned domain work');

Artisan::command('marketing:process-meta-webhooks {--limit=100}', function (MetaWebhookProcessor $processor) {
    $recovered = $processor->recoverStaleProcessing(now()->subMinutes(15));
    $summary = $processor->processPending('meta', max(1, (int) $this->option('limit')));

    $this->info(sprintf(
        'Recovered %d stale events; processed %d Meta webhook events (%d failed).',
        $recovered,
        $summary['events'],
        $summary['failed'],
    ));
})->purpose('Automatically process pending Meta webhook deliveries');

Schedule::command('recruitment:process-integration-retries')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('projects:generate-recurring-tasks')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('schedule:heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('operations:fail-stale-queue-work')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('operations:reconcile-queue-state')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('marketing:process-meta-webhooks')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->withoutOverlapping();

Schedule::command('queue:prune-batches --hours=168 --unfinished=168 --cancelled=168')
    ->daily()
    ->withoutOverlapping();
