<?php

namespace App\Http\Controllers\Shell;

use App\Http\Controllers\Controller;
use App\Services\Navigation\NavigationService;
use App\Services\Navigation\WorkspaceResolver;
use App\Services\Search\SearchProviderRegistry;
use App\Services\TenantContext;
use App\Services\Theme\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function index(
        Request $request,
        TenantContext $tenant,
        SearchProviderRegistry $registry,
        ThemeService $theme,
        NavigationService $navigation,
        WorkspaceResolver $workspaces,
    ): JsonResponse {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'scope' => ['nullable', 'string', 'max:40'],
        ]);

        $workspace = $workspaces->workspaceFromRoute();
        $defaultScope = $navigation->defaultSearchScopeForWorkspace($workspace ?? 'home');
        $scope = $data['scope'] ?? $defaultScope;

        $query = trim((string) ($data['q'] ?? ''));
        $results = $query === ''
            ? collect()
            : $registry->search($request->user(), $organization, $query, $scope);

        if ($query !== '') {
            $prefs = $theme->preferencesFor($request->user(), $organization);
            $searches = collect($prefs->recent_searches ?? [])
                ->reject(fn ($s) => ($s['q'] ?? null) === $query)
                ->prepend(['q' => $query, 'scope' => $scope, 'at' => now()->toIso8601String()])
                ->take(8)
                ->values()
                ->all();
            $theme->updatePreferences($request->user(), $organization, ['recent_searches' => $searches]);
        }

        $prefs = $theme->preferencesFor($request->user(), $organization);

        return response()->json([
            'results' => $results,
            'scopes' => $registry->scopes(),
            'recent' => $prefs->recent_searches ?? [],
            'default_scope' => $defaultScope,
        ]);
    }
}
