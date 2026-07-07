<?php

namespace App\Http\Middleware;

use App\Services\Platform\OrganizationLifecycleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationApiAccess
{
    public function __construct(protected OrganizationLifecycleService $lifecycle) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->user()?->organizations()
            ->where('organizations.id', (int) $request->header('X-Organization-Id'))
            ->first();

        if (! $organization && $request->hasSession()) {
            $orgId = $request->session()->get('current_organization_id');
            if ($orgId) {
                $organization = $request->user()?->organizations()
                    ->where('organizations.id', $orgId)
                    ->first();
            }
        }

        $this->lifecycle->assertApiAccess($organization);

        return $next($request);
    }
}
