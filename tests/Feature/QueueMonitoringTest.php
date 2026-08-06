<?php

namespace Tests\Feature;

use App\Models\QueueJobRun;
use App\Models\QueueWorkerHeartbeat;
use App\Services\Platform\PlatformMonitoringService;
use App\Services\Queue\QueueHealthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueMonitoringTest extends TestCase
{
    use DatabaseTransactions;

    public function test_successful_queue_jobs_are_recorded_without_affecting_execution(): void
    {
        Queue::connection('sync')->push(new QueueTelemetryProbeJob);

        $run = QueueJobRun::query()->sole();

        $this->assertSame(QueueJobRun::STATUS_SUCCEEDED, $run->status);
        $this->assertSame('sync', $run->connection);
        $this->assertSame(1, $run->attempt);
        $this->assertNotNull($run->started_at);
        $this->assertNotNull($run->finished_at);
        $this->assertNotNull($run->duration_ms);
    }

    public function test_database_health_snapshot_reports_depth_runs_and_workers(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        QueueJobRun::query()->create([
            'job_uuid' => 'health-success',
            'connection' => 'database',
            'queue' => 'default',
            'job_name' => QueueTelemetryProbeJob::class,
            'attempt' => 1,
            'status' => QueueJobRun::STATUS_SUCCEEDED,
            'started_at' => now()->subSecond(),
            'finished_at' => now(),
            'duration_ms' => 1000,
        ]);

        QueueWorkerHeartbeat::query()->create([
            'worker_id' => 'test-worker',
            'hostname' => 'test-host',
            'process_id' => 123,
            'connection' => 'database',
            'queue' => 'default',
            'status' => QueueWorkerHeartbeat::STATUS_ACTIVE,
            'started_at' => now()->subMinute(),
            'last_seen_at' => now(),
        ]);

        $snapshot = app(QueueHealthService::class)->snapshot('database');

        $this->assertSame(1, $snapshot['pending']);
        $this->assertSame(1, $snapshot['runs']['processed']);
        $this->assertSame(1000, $snapshot['runs']['p95_duration_ms']);
        $this->assertSame(1, $snapshot['workers']['active']);
        $this->assertSame('healthy', $snapshot['status']);
        $this->assertSame([], $snapshot['errors']);
    }

    public function test_reading_system_health_does_not_forge_a_scheduler_heartbeat(): void
    {
        $heartbeat = now()->subHour()->toIso8601String();
        Cache::forever('platform.scheduler.last_run', $heartbeat);

        $service = app(PlatformMonitoringService::class);

        $this->assertSame('stale', $service->schedulerStatus()['status']);

        $service->systemHealth();

        $this->assertSame($heartbeat, Cache::get('platform.scheduler.last_run'));
    }
}

class QueueTelemetryProbeJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}
