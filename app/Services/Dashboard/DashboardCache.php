<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    public function remember(string $bucket, int $organizationId, int $userId, callable $callback): mixed
    {
        $version = (int) Cache::get($this->versionKey($organizationId), 0);
        $userVersion = (int) Cache::get($this->userVersionKey($organizationId, $userId), 0);
        $ttl = (int) config('dashboard.cache_ttl', 300);
        $key = sprintf(
            'dashboard.%d.v%d.u%d.v%d.%s',
            $organizationId,
            $version,
            $userId,
            $userVersion,
            $bucket
        );

        return Cache::remember($key, $ttl, $callback);
    }

    public function rememberOrganization(string $bucket, int $organizationId, callable $callback): mixed
    {
        $version = (int) Cache::get($this->versionKey($organizationId), 0);
        $ttl = (int) config('dashboard.cache_ttl', 300);
        $key = sprintf('dashboard.org.%d.v%d.%s', $organizationId, $version, $bucket);

        return Cache::remember($key, $ttl, $callback);
    }

    public function rememberWidget(string $widgetKey, int $organizationId, int $userId, callable $callback): mixed
    {
        return $this->remember('widget.'.$widgetKey, $organizationId, $userId, $callback);
    }

    public function bump(?int $organizationId = null): void
    {
        if (! $organizationId) {
            return;
        }

        $key = $this->versionKey($organizationId);
        $current = (int) Cache::get($key, 0);
        Cache::forever($key, $current + 1);
    }

    public function clearUser(int $organizationId, int $userId): void
    {
        $key = $this->userVersionKey($organizationId, $userId);
        $current = (int) Cache::get($key, 0);
        Cache::forever($key, $current + 1);
    }

    protected function versionKey(int $organizationId): string
    {
        return 'dashboard.version.'.$organizationId;
    }

    protected function userVersionKey(int $organizationId, int $userId): string
    {
        return "dashboard.user-version.{$organizationId}.{$userId}";
    }
}
