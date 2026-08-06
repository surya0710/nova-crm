<?php

namespace App\Listeners;

use App\Services\Queue\QueueTelemetryService;
use Illuminate\Queue\Events\JobFailed;

class RecordQueueJobFailed
{
    public function __construct(private readonly QueueTelemetryService $telemetry) {}

    public function handle(JobFailed $event): void
    {
        $this->telemetry->fail($event->connectionName, $event->job, $event->exception);
    }
}
