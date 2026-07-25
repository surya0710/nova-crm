<?php

namespace App\Services\Workspace;

use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\Dashboard\DashboardCache;
use App\Services\TenantContext;

/**
 * Shared caching helpers for workspace home aggregators (Phase 14.9).
 */
trait CachesWorkspaceHome
{
    protected function rememberHome(string $workspace, User $user, callable $builder): array
    {
        $organization = app(TenantContext::class)->get();
        if (! $organization) {
            return $builder();
        }

        /** @var DashboardCache $cache */
        $cache = app(DashboardCache::class);

        $payload = $cache->remember(
            'workspace.home.'.$workspace,
            $organization->id,
            $user->id,
            $builder
        );

        // Preferences must stay fresh so Customize updates apply immediately.
        $prefs = UserUiPreference::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->first();

        if (array_key_exists('widgetLayout', $payload)) {
            $payload['widgetLayout'] = $prefs?->dashboard_layout[$workspace]
                ?? $payload['widgetLayout'];
        }

        return $payload;
    }
}
