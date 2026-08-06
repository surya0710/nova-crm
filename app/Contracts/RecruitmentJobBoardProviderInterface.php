<?php

namespace App\Contracts;

use App\Models\RecruitmentProvider;

/**
 * Job board publishing capability for recruitment providers.
 */
interface RecruitmentJobBoardProviderInterface
{
    /**
     * @param  array<string, mixed>  $opening
     * @return array{ok: bool, external_job_id?: string|null, status?: string|null, message?: string|null, metadata?: array<string, mixed>}
     */
    public function publishOpening(RecruitmentProvider $provider, array $opening): array;

    /**
     * @param  array<string, mixed>  $opening
     * @return array{ok: bool, external_job_id?: string|null, status?: string|null, message?: string|null, metadata?: array<string, mixed>}
     */
    public function updateOpening(RecruitmentProvider $provider, string $externalJobId, array $opening): array;

    /**
     * @return array{ok: bool, status?: string|null, message?: string|null}
     */
    public function closeOpening(RecruitmentProvider $provider, string $externalJobId): array;

    /**
     * @return array{ok: bool, status?: string|null, message?: string|null, metadata?: array<string, mixed>}
     */
    public function syncStatus(RecruitmentProvider $provider, string $externalJobId): array;
}
