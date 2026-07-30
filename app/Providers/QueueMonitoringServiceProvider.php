<?php

namespace App\Providers;

use App\Listeners\RecordQueueJobFailed;
use App\Listeners\RecordQueueJobProcessed;
use App\Listeners\RecordQueueJobProcessing;
use App\Listeners\RecordQueueWorkerHeartbeat;
use App\Services\Queue\QueueHealthService;
use App\Services\Queue\QueueTelemetryService;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class QueueMonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QueueTelemetryService::class);
        $this->app->singleton(QueueHealthService::class);
    }

    public function boot(): void
    {
        Event::listen(JobProcessing::class, RecordQueueJobProcessing::class);
        Event::listen(JobProcessed::class, RecordQueueJobProcessed::class);
        Event::listen(JobFailed::class, RecordQueueJobFailed::class);
        Event::listen(Looping::class, RecordQueueWorkerHeartbeat::class);
    }
}
