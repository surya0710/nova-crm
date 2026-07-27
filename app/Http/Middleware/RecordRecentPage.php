<?php

namespace App\Http\Middleware;

use App\Services\Navigation\RecentPagesService;
use App\Services\Navigation\WorkspaceResolver;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordRecentPage
{
    public function __construct(
        protected RecentPagesService $recents,
        protected TenantContext $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || ! $request->user() || $request->ajax() || $request->expectsJson()) {
            return $response;
        }

        $organization = $this->tenant->get();
        if (! $organization) {
            return $response;
        }

        $routeName = $request->route()?->getName();
        if (! $routeName || ! $this->shouldRecord($routeName)) {
            return $response;
        }

        $label = $this->resolveLabel($request);
        if ($label === null) {
            return $response;
        }

        $this->recents->record($request->user(), $organization, [
            'label' => __($label),
            'href' => $request->fullUrl(),
            'type' => $this->resolveType($routeName),
        ]);

        return $response;
    }

    protected function shouldRecord(string $routeName): bool
    {
        foreach (config('navigation.recents.skip_patterns', []) as $pattern) {
            if ($this->matchesPattern($routeName, $pattern)) {
                return false;
            }
        }

        foreach (config('navigation.recents.record_patterns', ['*']) as $pattern) {
            if ($this->matchesPattern($routeName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesPattern(string $routeName, string $pattern): bool
    {
        if ($pattern === $routeName) {
            return true;
        }

        if (str_ends_with($pattern, '*')) {
            return str_starts_with($routeName, rtrim($pattern, '*'));
        }

        return false;
    }

    protected function resolveLabel(Request $request): ?string
    {
        $pageTitle = $request->attributes->get('page_title');
        if (is_string($pageTitle) && $pageTitle !== '') {
            return $pageTitle;
        }

        $routeName = $request->route()?->getName();
        $labels = config('navigation.recents.route_labels', []);

        return $labels[$routeName] ?? null;
    }

    protected function resolveType(string $routeName): ?string
    {
        return app(WorkspaceResolver::class)->workspaceFromRoute($routeName);
    }
}
