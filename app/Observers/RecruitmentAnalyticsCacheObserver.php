<?php

namespace App\Observers;

use App\Services\Recruitment\RecruitmentAnalyticsCache;

class RecruitmentAnalyticsCacheObserver
{
    public function __construct(protected RecruitmentAnalyticsCache $cache) {}

    public function created(object $model): void
    {
        $this->cache->bumpForModel($model);
    }

    public function updated(object $model): void
    {
        $this->cache->bumpForModel($model);
    }

    public function deleted(object $model): void
    {
        $this->cache->bumpForModel($model);
    }

    public function restored(object $model): void
    {
        $this->cache->bumpForModel($model);
    }
}
