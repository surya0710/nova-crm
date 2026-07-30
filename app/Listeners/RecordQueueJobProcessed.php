<?php

namespace App\Listeners;

use App\Services\Queue\QueueTelemetryService;
use Illuminate\Queue\Events\JobProcessed;

class RecordQueueJobProcessed
{
    public function __construct(private readonly QueueTelemetryService $telemetry) {}

    public function handle(JobProcessed $event): void
    {
        $this->telemetry->succeed($event->connectionName, $event->job);
    }
}
