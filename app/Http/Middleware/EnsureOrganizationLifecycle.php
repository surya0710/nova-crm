<?php

namespace App\Http\Middleware;

use App\Services\Platform\OrganizationLifecycleService;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationLifecycle
{
    public function __construct(
        protected TenantContext $tenant,
        protected OrganizationLifecycleService $lifecycle,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->tenant->get();

        if ($organization) {
            $this->lifecycle->assertApiAccess($organization);
            $this->lifecycle->assertCanMutate($request, $this->tenant);
        }

        return $next($request);
    }
}
