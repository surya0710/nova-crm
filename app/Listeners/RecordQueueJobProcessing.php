<?php

namespace App\Listeners;

use App\Services\Queue\QueueTelemetryService;
use Illuminate\Queue\Events\JobProcessing;

class RecordQueueJobProcessing
{
    public function __construct(private readonly QueueTelemetryService $telemetry) {}

    public function handle(JobProcessing $event): void
    {
        $this->telemetry->start($event->connectionName, $event->job);
    }
}
