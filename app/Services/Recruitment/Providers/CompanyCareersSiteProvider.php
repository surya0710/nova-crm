<?php

namespace App\Services\Recruitment\Providers;

use App\Models\RecruitmentProvider;

/**
 * Internal careers site adapter — maps to already-published openings.
 * Does not duplicate openings; external_job_id is the local opening id.
 */
class CompanyCareersSiteProvider extends AbstractJobBoardProvider
{
    public function slug(): string
    {
        return 'company_careers';
    }

    public function displayName(): string
    {
        return 'Company Careers Site';
    }

    public function authorize(RecruitmentProvider $provider, array $context = []): array
    {
        return [
            'credentials' => [
                'access_token' => 'careers-site-internal',
                'token_type' => 'internal',
                'metadata' => ['mode' => 'internal'],
            ],
            'status' => RecruitmentProvider::STATUS_CONNECTED,
            'metadata' => ['note' => 'Company careers site uses internal publishing — no external OAuth.'],
        ];
    }

    public function publishOpening(RecruitmentProvider $provider, array $opening): array
    {
        $openingId = (string) ($opening['id'] ?? '');

        if ($openingId === '' || ($opening['status'] ?? null) !== 'published') {
            return [
                'ok' => false,
                'message' => 'Only published openings appear on the company careers site.',
            ];
        }

        return [
            'ok' => true,
            'external_job_id' => 'careers_'.$openingId,
            'status' => 'published',
            'message' => 'Opening is listed on the company careers site.',
            'metadata' => ['source' => 'internal'],
        ];
    }
}
