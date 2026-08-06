<?php

namespace App\Services\Platform;

use App\Services\Queue\QueueHealthService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class PlatformMonitoringService
{
    public function __construct(private readonly QueueHealthService $queueHealth) {}

    public function snapshot(): array
    {
        $queue = $this->queueStatus();

        return [
            'queue' => $queue,
            'failed_jobs' => $this->failedJobs(10),
            'scheduler' => $this->schedulerStatus(),
            'cache' => $this->cacheStatus(),
            'redis' => $this->redisStatus(),
            'database' => $this->databaseStatus(),
            'storage' => $this->storageStatus(),
            'logs' => $this->recentLogTail(),
            'system' => $this->systemHealth($queue),
        ];
    }

    public function queueStatus(): array
    {
        return $this->queueHealth->snapshot();
    }

    public function failedJobs(int $limit = 25): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get(['id', 'uuid', 'queue', 'exception', 'failed_at'])
            ->map(fn ($job) => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'queue' => $job->queue,
                'exception' => Str::limit($job->exception ?? '', 200),
                'failed_at' => $job->failed_at,
            ])
            ->all();
    }

    public function schedulerStatus(): array
    {
        try {
            $last = Cache::get('platform.scheduler.last_run');
            $lastRun = is_string($last) && $last !== '' ? Carbon::parse($last) : null;
            $staleAfter = max(1, (int) config('queue-monitoring.scheduler_stale_after_seconds', 180));
            $status = $lastRun === null
                ? 'unknown'
                : ($lastRun->lt(now()->subSeconds($staleAfter)) ? 'stale' : 'ok');
        } catch (Throwable) {
            $last = null;
            $status = 'unknown';
        }

        return [
            'status' => $status,
            'last_run' => $last,
            'note' => __('Updated by schedule:heartbeat every minute when the OS scheduler is configured.'),
        ];
    }

    public function cacheStatus(): array
    {
        $store = config('cache.default');
        $ok = false;

        try {
            Cache::put('platform.monitoring.ping', true, 10);
            $ok = Cache::get('platform.monitoring.ping') === true;
        } catch (Throwable) {
            $ok = false;
        }

        return [
            'driver' => $store,
            'status' => $ok ? 'healthy' : 'unhealthy',
        ];
    }

    public function redisStatus(): array
    {
        if (config('cache.default') !== 'redis' && config('queue.default') !== 'redis') {
            return [
                'status' => 'not_configured',
                'message' => __('Redis is not the active cache or queue driver.'),
            ];
        }

        try {
            Redis::connection()->ping();

            return ['status' => 'healthy'];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function databaseStatus(): array
    {
        try {
            $start = microtime(true);
            DB::select('select 1');
            $ms = round((microtime(true) - $start) * 1000, 1);

            return [
                'status' => 'healthy',
                'driver' => config('database.default'),
                'latency_ms' => $ms,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function storageStatus(): array
    {
        $bytes = (int) DB::table('organizations')->sum('storage_used_bytes');

        return [
            'status' => 'healthy',
            'used_bytes' => $bytes,
            'used_mb' => round($bytes / 1048576, 2),
            'disk' => config('filesystems.default'),
        ];
    }

    public function recentLogTail(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_readable($path)) {
            return [];
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        return array_slice($lines, -30);
    }

    public function systemHealth(?array $queue = null): array
    {
        $parts = [
            ($queue ?? $this->queueStatus())['status'],
            $this->cacheStatus()['status'],
            $this->databaseStatus()['status'],
        ];

        $status = in_array('unhealthy', $parts, true)
            ? 'unhealthy'
            : (in_array('degraded', $parts, true) || in_array('busy', $parts, true) ? 'degraded' : 'healthy');

        return [
            'status' => $status,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'app_env' => config('app.env'),
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
