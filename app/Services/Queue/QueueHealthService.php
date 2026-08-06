<?php

namespace App\Services\Queue;

use App\Models\QueueJobRun;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QueueHealthService
{
    public function snapshot(?string $connectionName = null): array
    {
        $connectionName ??= (string) config('queue.default');
        $connection = (array) config("queue.connections.{$connectionName}", []);
        $driver = (string) ($connection['driver'] ?? $connectionName);
        $queue = (string) ($connection['queue'] ?? 'default');
        $errors = [];

        $pending = $this->pendingCount($driver, $connection, $queue, $errors);
        $failed = $this->failedCount($errors);
        $runs = $this->runMetrics($errors);
        $workers = $this->workerMetrics($connectionName, $queue, $errors);

        return [
            'connection' => $connectionName,
            'driver' => $driver,
            'queue' => $queue,
            'pending' => $pending,
            'failed' => $failed,
            'runs' => $runs,
            'workers' => $workers,
            'status' => $this->status($driver, $pending, $failed, $workers),
            'checked_at' => now()->toIso8601String(),
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $connection
     * @param  list<string>  $errors
     */
    private function pendingCount(string $driver, array $connection, string $queue, array &$errors): ?int
    {
        try {
            return match ($driver) {
                'sync', 'null' => 0,
                'database' => $this->databasePendingCount($connection, $queue),
                'redis' => $this->redisPendingCount($connection, $queue),
                default => null,
            };
        } catch (Throwable) {
            $errors[] = 'pending_count_unavailable';

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function databasePendingCount(array $connection, string $queue): int
    {
        $database = DB::connection($connection['connection'] ?? null);
        $table = (string) ($connection['table'] ?? 'jobs');

        if (! Schema::connection($database->getName())->hasTable($table)) {
            return 0;
        }

        return (int) $database->table($table)->where('queue', $queue)->count();
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function redisPendingCount(array $connection, string $queue): int
    {
        $redis = Redis::connection((string) ($connection['connection'] ?? 'default'));
        $key = 'queues:'.$queue;

        return (int) $redis->llen($key)
            + (int) $redis->zcard($key.':delayed')
            + (int) $redis->zcard($key.':reserved');
    }

    /**
     * @param  list<string>  $errors
     */
    private function failedCount(array &$errors): int
    {
        try {
            $connection = config('queue.failed.database') ?: null;
            $table = (string) config('queue.failed.table', 'failed_jobs');
            $database = DB::connection($connection);

            if (! Schema::connection($database->getName())->hasTable($table)) {
                return 0;
            }

            return (int) $database->table($table)->count();
        } catch (Throwable) {
            $errors[] = 'failed_count_unavailable';

            return 0;
        }
    }

    /**
     * @param  list<string>  $errors
     */
    private function runMetrics(array &$errors): array
    {
        $windowMinutes = max(1, (int) config('queue-monitoring.health_window_minutes', 60));
        $empty = [
            'window_minutes' => $windowMinutes,
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'failure_rate' => 0.0,
            'average_duration_ms' => null,
            'p95_duration_ms' => null,
        ];

        try {
            $database = $this->monitoringDatabase();

            if (! Schema::connection($database->getName())->hasTable('queue_job_runs')) {
                return $empty;
            }

            $runs = $database->table('queue_job_runs')
                ->whereIn('status', [QueueJobRun::STATUS_SUCCEEDED, QueueJobRun::STATUS_FAILED])
                ->where('finished_at', '>=', now()->subMinutes($windowMinutes))
                ->get(['status', 'duration_ms']);

            if ($runs->isEmpty()) {
                return $empty;
            }

            $durations = $runs->pluck('duration_ms')
                ->filter(fn (mixed $duration): bool => $duration !== null)
                ->map(fn (mixed $duration): int => (int) $duration)
                ->sort()
                ->values();
            $failed = $runs->where('status', QueueJobRun::STATUS_FAILED)->count();

            return [
                'window_minutes' => $windowMinutes,
                'processed' => $runs->count(),
                'succeeded' => $runs->where('status', QueueJobRun::STATUS_SUCCEEDED)->count(),
                'failed' => $failed,
                'failure_rate' => round($failed / $runs->count(), 4),
                'average_duration_ms' => $durations->isEmpty() ? null : round($durations->average(), 1),
                'p95_duration_ms' => $this->percentile($durations, 0.95),
            ];
        } catch (Throwable) {
            $errors[] = 'run_metrics_unavailable';

            return $empty;
        }
    }

    /**
     * @param  list<string>  $errors
     */
    private function workerMetrics(string $connection, string $queue, array &$errors): array
    {
        $staleAfter = max(1, (int) config('queue-monitoring.worker_stale_after_seconds', 90));
        $empty = ['active' => 0, 'stale' => 0, 'stale_after_seconds' => $staleAfter];

        try {
            $database = $this->monitoringDatabase();

            if (! Schema::connection($database->getName())->hasTable('queue_worker_heartbeats')) {
                return $empty;
            }

            $heartbeats = $database->table('queue_worker_heartbeats')
                ->where('connection', $connection)
                ->where('queue', $queue)
                ->where('status', 'active')
                ->get(['last_seen_at']);
            $staleBefore = now()->subSeconds($staleAfter);
            $active = $heartbeats->filter(
                fn (object $heartbeat): bool => Carbon::parse($heartbeat->last_seen_at)->gte($staleBefore),
            )->count();

            return [
                'active' => $active,
                'stale' => $heartbeats->count() - $active,
                'stale_after_seconds' => $staleAfter,
            ];
        } catch (Throwable) {
            $errors[] = 'worker_metrics_unavailable';

            return $empty;
        }
    }

    private function status(string $driver, ?int $pending, int $failed, array $workers): string
    {
        if ($failed >= max(1, (int) config('queue-monitoring.degraded_failed_threshold', 1))) {
            return 'degraded';
        }

        if (
            ! in_array($driver, ['sync', 'null'], true)
            && ($pending ?? 0) > 0
            && $workers['active'] === 0
        ) {
            return 'degraded';
        }

        if (($pending ?? 0) > (int) config('queue-monitoring.busy_pending_threshold', 100)) {
            return 'busy';
        }

        return 'healthy';
    }

    private function percentile(Collection $values, float $percentile): ?int
    {
        if ($values->isEmpty()) {
            return null;
        }

        $index = (int) ceil($percentile * $values->count()) - 1;

        return (int) $values->get(max(0, $index));
    }

    private function monitoringDatabase(): ConnectionInterface
    {
        return DB::connection(config('queue-monitoring.database_connection'));
    }
}
