<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class PlatformMonitoringService
{
    public function snapshot(): array
    {
        return [
            'queue' => $this->queueStatus(),
            'failed_jobs' => $this->failedJobs(10),
            'scheduler' => $this->schedulerStatus(),
            'cache' => $this->cacheStatus(),
            'redis' => $this->redisStatus(),
            'database' => $this->databaseStatus(),
            'storage' => $this->storageStatus(),
            'logs' => $this->recentLogTail(),
            'system' => $this->systemHealth(),
        ];
    }

    public function queueStatus(): array
    {
        $pending = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
        $failed = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;

        return [
            'driver' => config('queue.default'),
            'pending' => $pending,
            'failed' => $failed,
            'status' => $failed > 0 ? 'degraded' : ($pending > 100 ? 'busy' : 'healthy'),
        ];
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
        $last = Cache::get('platform.scheduler.last_run');

        return [
            'status' => $last ? 'ok' : 'unknown',
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

    public function systemHealth(): array
    {
        Cache::put('platform.scheduler.last_run', now()->toIso8601String(), 86400);

        $parts = [
            $this->queueStatus()['status'],
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
