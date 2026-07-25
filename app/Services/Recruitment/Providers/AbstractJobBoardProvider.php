<?php

namespace App\Services\Recruitment\Providers;

use App\Contracts\RecruitmentJobBoardProviderInterface;
use App\Models\RecruitmentProvider;
use Illuminate\Support\Str;

abstract class AbstractJobBoardProvider extends AbstractRecruitmentProvider implements RecruitmentJobBoardProviderInterface
{
    public function category(): string
    {
        return 'job_board';
    }

    public function capabilities(): array
    {
        return ['job_publish', 'job_update', 'job_close', 'status_sync'];
    }

    public function publishOpening(RecruitmentProvider $provider, array $opening): array
    {
        return [
            'ok' => true,
            'external_job_id' => $this->slug().'_'.Str::uuid()->toString(),
            'status' => 'published',
            'message' => $this->displayName().' listing published (placeholder).',
            'metadata' => ['title' => $opening['title'] ?? null],
        ];
    }

    public function updateOpening(RecruitmentProvider $provider, string $externalJobId, array $opening): array
    {
        return [
            'ok' => true,
            'external_job_id' => $externalJobId,
            'status' => 'updated',
            'message' => $this->displayName().' listing updated (placeholder).',
        ];
    }

    public function closeOpening(RecruitmentProvider $provider, string $externalJobId): array
    {
        return [
            'ok' => true,
            'status' => 'closed',
            'message' => $this->displayName().' listing closed (placeholder).',
        ];
    }

    public function syncStatus(RecruitmentProvider $provider, string $externalJobId): array
    {
        return [
            'ok' => true,
            'status' => 'published',
            'message' => $this->displayName().' status synced (placeholder).',
            'metadata' => ['external_job_id' => $externalJobId],
        ];
    }
}
