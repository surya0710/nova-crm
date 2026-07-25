<?php

namespace App\Services\Recruitment;

use App\Models\Candidate;
use App\Models\HiringDecision;
use App\Models\InterviewRound;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\OfferLetter;
use Illuminate\Support\Facades\Cache;

class RecruitmentAnalyticsCache
{
    public function remember(string $bucket, array $filters, callable $callback): mixed
    {
        $organizationId = app(\App\Services\TenantContext::class)->id() ?? 0;
        $version = (int) Cache::get($this->versionKey($organizationId), 1);
        $ttl = (int) config('hrms.recruitment.analytics.cache_ttl', 120);
        $key = sprintf(
            'recruitment.analytics.%d.v%d.%s.%s',
            $organizationId,
            $version,
            $bucket,
            md5(json_encode($filters))
        );

        return Cache::remember($key, $ttl, $callback);
    }

    public function bump(?int $organizationId = null): void
    {
        $organizationId ??= app(\App\Services\TenantContext::class)->id();
        if (! $organizationId) {
            return;
        }

        $key = $this->versionKey($organizationId);
        if (! Cache::has($key)) {
            Cache::forever($key, 1);

            return;
        }

        Cache::increment($key);
    }

    public function bumpForModel(object $model): void
    {
        if (isset($model->organization_id) && $model->organization_id) {
            $this->bump((int) $model->organization_id);
        }
    }

    protected function versionKey(int $organizationId): string
    {
        return 'recruitment.analytics.version.'.$organizationId;
    }

    /**
     * @return list<class-string>
     */
    public static function observedModels(): array
    {
        return [
            JobRequisition::class,
            JobOpening::class,
            Candidate::class,
            JobApplication::class,
            InterviewRound::class,
            OfferLetter::class,
            HiringDecision::class,
        ];
    }
}
