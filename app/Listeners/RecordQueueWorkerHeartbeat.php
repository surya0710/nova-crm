<?php

namespace App\Listeners;

use App\Services\Queue\QueueTelemetryService;
use Illuminate\Queue\Events\Looping;

class RecordQueueWorkerHeartbeat
{
    public function __construct(private readonly QueueTelemetryService $telemetry) {}

    public function handle(Looping $event): void
    {
        $this->telemetry->heartbeat($event->connectionName, $event->queue);
    }
}
