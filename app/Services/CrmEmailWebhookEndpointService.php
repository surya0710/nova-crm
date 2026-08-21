<?php

namespace App\Services;

use App\Models\CrmEmailWebhookEndpoint;
use App\Models\Organization;
use Illuminate\Support\Str;

class CrmEmailWebhookEndpointService
{
    public function ensure(Organization $organization, ?string $provider = null): ?CrmEmailWebhookEndpoint
    {
        $provider ??= app(OrganizationMailConfig::class)->for($organization)->provider();

        if (! $this->providerSupportsTracking($provider)) {
            return null;
        }

        $endpoint = CrmEmailWebhookEndpoint::withoutGlobalScopes()
            ->firstOrNew([
                'organization_id' => $organization->id,
                'provider' => $provider,
            ]);

        if (! $endpoint->exists) {
            $endpoint->token = Str::lower((string) Str::ulid()).Str::lower(Str::random(16));
            $endpoint->signing_secret = Str::random(40);
            $endpoint->is_active = true;
            $endpoint->save();
        } elseif (! $endpoint->is_active) {
            $endpoint->is_active = true;
            $endpoint->save();
        }

        if (! filled($endpoint->getAttributes()['signing_secret'] ?? null)) {
            $endpoint->signing_secret = Str::random(40);
            $endpoint->save();
        }

        return $endpoint->fresh();
    }

    public function findActive(Organization $organization, ?string $provider = null): ?CrmEmailWebhookEndpoint
    {
        $provider ??= app(OrganizationMailConfig::class)->for($organization)->provider();

        return CrmEmailWebhookEndpoint::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }

    public function findByToken(string $provider, string $token): ?CrmEmailWebhookEndpoint
    {
        return CrmEmailWebhookEndpoint::withoutGlobalScopes()
            ->where('provider', $provider)
            ->where('token', $token)
            ->where('is_active', true)
            ->first();
    }

    public function providerSupportsTracking(string $provider): bool
    {
        return (bool) (config('organization_mail.providers.'.$provider.'.delivery_tracking') ?? false);
    }
}
