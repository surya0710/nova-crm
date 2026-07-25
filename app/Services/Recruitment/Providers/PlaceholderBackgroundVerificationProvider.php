<?php

namespace App\Services\Recruitment\Providers;

use App\Contracts\BackgroundVerificationProviderInterface;
use App\Models\RecruitmentProvider;
use Illuminate\Support\Str;

class PlaceholderBackgroundVerificationProvider extends AbstractRecruitmentProvider implements BackgroundVerificationProviderInterface
{
    public function slug(): string
    {
        return 'placeholder_bgv';
    }

    public function displayName(): string
    {
        return 'Background Verification (Placeholder)';
    }

    public function category(): string
    {
        return 'background_verification';
    }

    public function capabilities(): array
    {
        return ['bgv_submit', 'bgv_status', 'bgv_documents'];
    }

    public function authorize(RecruitmentProvider $provider, array $context = []): array
    {
        return [
            'credentials' => [
                'access_token' => 'placeholder-bgv',
                'token_type' => 'internal',
            ],
            'status' => RecruitmentProvider::STATUS_CONNECTED,
        ];
    }

    public function submitVerification(RecruitmentProvider $provider, array $payload): array
    {
        return [
            'ok' => true,
            'external_verification_id' => 'bgv_'.Str::uuid()->toString(),
            'status' => 'pending',
            'message' => 'Background verification submitted (placeholder).',
        ];
    }

    public function checkStatus(RecruitmentProvider $provider, string $externalVerificationId): array
    {
        return [
            'ok' => true,
            'status' => 'in_progress',
            'message' => 'Background verification in progress (placeholder).',
            'metadata' => ['external_verification_id' => $externalVerificationId],
        ];
    }

    public function uploadDocument(RecruitmentProvider $provider, string $externalVerificationId, array $document): array
    {
        return [
            'ok' => true,
            'document_id' => 'doc_'.Str::uuid()->toString(),
            'message' => 'Document accepted (placeholder).',
        ];
    }
}
