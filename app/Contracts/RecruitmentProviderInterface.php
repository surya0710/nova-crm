<?php

namespace App\Contracts;

use App\Models\RecruitmentProvider;

/**
 * Provider-agnostic recruitment integration contract (Phase 11.6).
 *
 * Adapters translate external APIs into normalized results. Persistence of
 * provider rows and credentials belongs exclusively to RecruitmentProviderService.
 * Adapters must never write recruitment domain tables.
 */
interface RecruitmentProviderInterface
{
    public function slug(): string;

    public function displayName(): string;

    /**
     * @return list<string>
     */
    public function capabilities(): array;

    public function category(): string;

    /**
     * @param  array<string, mixed>  $context
     * @return array{authorization_url?: string|null, credentials?: array<string, mixed>|null, status?: string|null, metadata?: array<string, mixed>}
     */
    public function authorize(RecruitmentProvider $provider, array $context = []): array;

    /**
     * @return array{access_token?: string|null, refresh_token?: string|null, expires_at?: string|null, token_type?: string|null, scopes?: list<string>|null, metadata?: array<string, mixed>}
     */
    public function refreshCredentials(RecruitmentProvider $provider): array;

    public function revoke(RecruitmentProvider $provider): void;

    /**
     * @param  array<string, mixed>  $options
     * @return array{ok: bool, message?: string|null, metadata?: array<string, mixed>}
     */
    public function synchronize(RecruitmentProvider $provider, array $options = []): array;

    /**
     * @return array{healthy: bool, status?: string|null, message?: string|null, checked_at?: string|null, metadata?: array<string, mixed>}
     */
    public function reportHealth(RecruitmentProvider $provider): array;
}
