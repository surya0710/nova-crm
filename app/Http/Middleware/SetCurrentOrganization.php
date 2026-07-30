<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    public function __construct(protected TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenant->clear();

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->is_super_admin) {
            return $next($request);
        }

        $organizationId = null;

        if ($request->hasSession()) {
            $organizationId = $request->session()->get('current_organization_id');
        }

        if (! $organizationId && $request->hasHeader('X-Organization-Id')) {
            $organizationId = (int) $request->header('X-Organization-Id');
        }

        if ($organizationId) {
            $organization = $user->activeOrganizations()
                ->where('organizations.id', $organizationId)
                ->first();

            if ($organization) {
                $this->tenant->set($organization);
                $this->syncSession($request, $organization);

                return $next($request);
            }

            if ($request->hasSession()) {
                $request->session()->forget([
                    'current_organization_id',
                    'current_organization_name',
                    'current_membership',
                ]);
            }
        }

        $defaultOrganization = $user->activeOrganizations()->first();

        if ($defaultOrganization) {
            $this->tenant->set($defaultOrganization);

            if ($request->hasSession()) {
                $this->syncSession($request, $defaultOrganization);
            }
        }

        return $next($request);
    }

    protected function syncSession(Request $request, \App\Models\Organization $organization): void
    {
        $request->session()->put([
            'current_organization_id' => $organization->id,
            'current_organization_name' => $organization->name,
            'current_membership' => [
                'id' => $organization->pivot->id,
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
                'role' => $organization->pivot->role,
                'role_id' => $organization->pivot->role_id,
                'is_owner' => (bool) $organization->pivot->is_owner,
                'is_active' => (bool) $organization->pivot->is_active,
            ],
        ]);
    }
}
