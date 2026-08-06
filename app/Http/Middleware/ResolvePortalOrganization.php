<?php

namespace App\Http\Middleware;

use App\Models\ClientPortalSetting;
use App\Models\Organization;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePortalOrganization
{
    public function __construct(protected TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenant->clear();

        /** @var Organization|null $organization */
        $organization = $request->route('organization');

        if (! $organization instanceof Organization) {
            $organization = Organization::query()
                ->where('slug', $request->route('organization'))
                ->first();
        }

        if (! $organization || ! $organization->is_active) {
            abort(404);
        }

        $this->tenant->set($organization);

        $settings = ClientPortalSetting::query()
            ->where('organization_id', $organization->id)
            ->first();

        if ($settings && ! $settings->portal_enabled) {
            abort(404);
        }

        $request->attributes->set('portal_organization', $organization);
        $request->attributes->set('portal_settings', $settings);

        view()->share([
            'portalOrganization' => $organization,
            'portalSettings' => $settings,
        ]);

        return $next($request);
    }
}
