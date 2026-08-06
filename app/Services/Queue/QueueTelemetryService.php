<?php

namespace App\Services\Queue;

use App\Models\QueueJobRun;
use App\Models\QueueWorkerHeartbeat;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class QueueTelemetryService
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function start(string $connection, Job $job): void
    {
        if (! $this->enabled()) {
            return;
        }

        // A daemon must never attribute a new job to the previous job's tenant.
        $this->tenantContext->clear();

        $this->silently(function () use ($connection, $job): void {
            $identity = $this->jobIdentity($connection, $job);
            $now = now();

            $this->database()->table('queue_job_runs')->updateOrInsert(
                [
                    'job_uuid' => $identity['job_uuid'],
                    'attempt' => $identity['attempt'],
                ],
                [
                    'organization_id' => null,
                    'job_id' => $identity['job_id'],
                    'connection' => $connection,
                    'queue' => $job->getQueue() ?: 'default',
                    'job_name' => $identity['job_name'],
                    'status' => QueueJobRun::STATUS_RUNNING,
                    'worker_id' => $this->workerId($connection, $job->getQueue() ?: 'default'),
                    'started_at' => $now,
                    'finished_at' => null,
                    'duration_ms' => null,
                    'exception_class' => null,
                    'exception_message' => null,
                    'metadata' => json_encode(['tags' => $identity['tags']], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        });
    }

    public function succeed(string $connection, Job $job): void
    {
        $this->finish($connection, $job, QueueJobRun::STATUS_SUCCEEDED);
    }

    public function fail(string $connection, Job $job, Throwable $exception): void
    {
        $this->finish($connection, $job, QueueJobRun::STATUS_FAILED, $exception);
    }

    public function heartbeat(string $connection, string $queue): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->silently(function () use ($connection, $queue): void {
            $database = $this->database();
            $now = now();
            $workerId = $this->workerId($connection, $queue);

            $database->table('queue_worker_heartbeats')->insertOrIgnore([
                'worker_id' => $workerId,
                'hostname' => $this->hostname(),
                'process_id' => getmypid() ?: null,
                'connection' => $connection,
                'queue' => $queue,
                'status' => QueueWorkerHeartbeat::STATUS_ACTIVE,
                'started_at' => $now,
                'last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $database->table('queue_worker_heartbeats')
                ->where('worker_id', $workerId)
                ->update([
                    'hostname' => $this->hostname(),
                    'process_id' => getmypid() ?: null,
                    'connection' => $connection,
                    'queue' => $queue,
                    'status' => QueueWorkerHeartbeat::STATUS_ACTIVE,
                    'last_seen_at' => $now,
                    'stopped_at' => null,
                    'updated_at' => $now,
                ]);
        });
    }

    private function finish(
        string $connection,
        Job $job,
        string $status,
        ?Throwable $exception = null,
    ): void {
        if (! $this->enabled()) {
            $this->tenantContext->clear();

            return;
        }

        try {
            $this->silently(function () use ($connection, $job, $status, $exception): void {
                $identity = $this->jobIdentity($connection, $job);
                $database = $this->database();
                $run = $database->table('queue_job_runs')
                    ->where('job_uuid', $identity['job_uuid'])
                    ->where('attempt', $identity['attempt'])
                    ->first(['started_at']);
                $finishedAt = now();
                $startedAt = $run?->started_at ? Carbon::parse($run->started_at) : $finishedAt;

                $database->table('queue_job_runs')->updateOrInsert(
                    [
                        'job_uuid' => $identity['job_uuid'],
                        'attempt' => $identity['attempt'],
                    ],
                    [
                        'organization_id' => $this->tenantContext->id(),
                        'job_id' => $identity['job_id'],
                        'connection' => $connection,
                        'queue' => $job->getQueue() ?: 'default',
                        'job_name' => $identity['job_name'],
                        'status' => $status,
                        'worker_id' => $this->workerId($connection, $job->getQueue() ?: 'default'),
                        'started_at' => $startedAt,
                        'finished_at' => $finishedAt,
                        'duration_ms' => max(0, (int) $startedAt->diffInMilliseconds($finishedAt)),
                        'exception_class' => $exception ? get_class($exception) : null,
                        'exception_message' => $exception ? Str::limit($exception->getMessage(), 2000, '') : null,
                        'metadata' => json_encode(['tags' => $identity['tags']], JSON_THROW_ON_ERROR),
                        'created_at' => $startedAt,
                        'updated_at' => $finishedAt,
                    ],
                );
            });
        } finally {
            $this->tenantContext->clear();
        }
    }

    /**
     * @return array{job_uuid: string, job_id: ?string, job_name: string, attempt: int, tags: list<string>}
     */
    private function jobIdentity(string $connection, Job $job): array
    {
        $payload = $job->payload();
        $jobId = $job->getJobId();
        $attempt = max(1, $job->attempts());
        $jobName = (string) ($payload['displayName'] ?? $job->resolveName());
        $uuid = $payload['uuid'] ?? null;

        if (! is_string($uuid) || $uuid === '') {
            $uuid = hash('sha256', implode('|', [
                $connection,
                (string) $jobId,
                $jobName,
                (string) $attempt,
            ]));
        }

        $tags = array_values(array_filter(
            is_array($payload['tags'] ?? null) ? $payload['tags'] : [],
            fn (mixed $tag): bool => is_string($tag),
        ));

        return [
            'job_uuid' => $uuid,
            'job_id' => $jobId === null ? null : (string) $jobId,
            'job_name' => Str::limit($jobName, 255, ''),
            'attempt' => $attempt,
            'tags' => array_map(fn (string $tag): string => Str::limit($tag, 255, ''), $tags),
        ];
    }

    private function workerId(string $connection, string $queue): string
    {
        return hash('sha256', implode('|', [
            $this->hostname(),
            (string) (getmypid() ?: 0),
            $connection,
            $queue,
        ]));
    }

    private function hostname(): string
    {
        return gethostname() ?: php_uname('n') ?: 'unknown';
    }

    private function database(): ConnectionInterface
    {
        return DB::connection(config('queue-monitoring.database_connection'));
    }

    private function enabled(): bool
    {
        if (! config('queue-monitoring.enabled', true)) {
            return false;
        }

        try {
            return Schema::connection(config('queue-monitoring.database_connection'))
                ->hasTable('queue_job_runs');
        } catch (Throwable) {
            return false;
        }
    }

    private function silently(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable) {
            // Queue telemetry must never change job execution behavior.
        }
    }
}
