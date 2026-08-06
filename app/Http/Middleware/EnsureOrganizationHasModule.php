<?php

namespace App\Http\Middleware;

use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Modules\ModuleRegistry;
use App\Services\Navigation\WorkspaceResolver;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationHasModule
{
    public function __construct(
        protected TenantContext $tenant,
        protected ModuleSubscriptionService $modules,
        protected ModuleRegistry $registry,
        protected WorkspaceResolver $workspaces,
    ) {}

    /**
     * @param  string|null  $module  Explicit module key, or null to resolve from the current route.
     */
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        $organization = $this->tenant->get();

        if (! $organization) {
            return $next($request);
        }

        $moduleKey = $module ?: $this->resolveModuleFromRoute($request);

        if ($moduleKey === null || $moduleKey === '') {
            return $next($request);
        }

        $definition = $this->registry->get($moduleKey);
        if ($definition && ($definition['licensable'] ?? true) === false) {
            return $next($request);
        }

        if ($this->modules->moduleAllowed($organization, $moduleKey)) {
            return $next($request);
        }

        abort(403, __('Module not licensed.'));
    }

    protected function resolveModuleFromRoute(Request $request): ?string
    {
        $routeName = $request->route()?->getName();
        if (! $routeName) {
            return null;
        }

        $workspace = $this->workspaces->workspaceFromRoute($routeName);
        if (! $workspace || ! $this->registry->workspaceRequiresLicense($workspace)) {
            return null;
        }

        return $this->registry->moduleForWorkspace($workspace);
    }
}
