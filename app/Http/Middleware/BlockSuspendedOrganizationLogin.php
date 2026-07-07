<?php

namespace App\Http\Middleware;

use App\Services\Platform\OrganizationLifecycleService;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSuspendedOrganizationLogin
{
    public function __construct(
        protected TenantContext $tenant,
        protected OrganizationLifecycleService $lifecycle,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->tenant->get();

        if ($organization) {
            $this->lifecycle->assertCanLogin($organization);
        }

        return $next($request);
    }
}
