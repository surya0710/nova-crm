<?php

namespace App\Contracts;

use App\Models\RecruitmentProvider;

/**
 * Background verification capability. No live vendor integrations yet.
 */
interface BackgroundVerificationProviderInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, external_verification_id?: string|null, status?: string|null, message?: string|null}
     */
    public function submitVerification(RecruitmentProvider $provider, array $payload): array;

    /**
     * @return array{ok: bool, status?: string|null, message?: string|null, metadata?: array<string, mixed>}
     */
    public function checkStatus(RecruitmentProvider $provider, string $externalVerificationId): array;

    /**
     * @param  array{filename: string, contents?: string|null, path?: string|null, mime_type?: string|null}  $document
     * @return array{ok: bool, document_id?: string|null, message?: string|null}
     */
    public function uploadDocument(RecruitmentProvider $provider, string $externalVerificationId, array $document): array;
}
