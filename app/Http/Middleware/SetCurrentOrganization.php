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
            $organization = $user->organizations()
                ->where('organizations.id', $organizationId)
                ->first();

            if ($organization) {
                $this->tenant->set($organization);

                return $next($request);
            }

            if ($request->hasSession()) {
                $request->session()->forget('current_organization_id');
            }
        }

        $defaultOrganization = $user->organizations()->first();

        if ($defaultOrganization) {
            $this->tenant->set($defaultOrganization);

            if ($request->hasSession()) {
                $request->session()->put('current_organization_id', $defaultOrganization->id);
            }
        }

        return $next($request);
    }
}
