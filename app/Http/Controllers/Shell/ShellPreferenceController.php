<?php

namespace App\Http\Controllers\Shell;

use App\Http\Controllers\Controller;
use App\Services\Navigation\FavoritePagesService;
use App\Services\Navigation\FavoriteWorkspacesService;
use App\Services\Navigation\NavigationContextManager;
use App\Services\Navigation\RecentPagesService;
use App\Services\Navigation\WorkspaceResolver;
use App\Services\TenantContext;
use App\Services\Theme\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShellPreferenceController extends Controller
{
    public function update(Request $request, TenantContext $tenant, ThemeService $theme): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $data = $request->validate([
            'theme' => ['sometimes', 'in:light,dark,system'],
            'density' => ['sometimes', 'in:comfortable,compact'],
            'sidebar_collapsed' => ['sometimes', 'boolean'],
            'last_workspace' => ['sometimes', 'nullable', 'string', 'max:50'],
            'landing_page' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $prefs = $theme->updatePreferences($request->user(), $organization, $data);

        return response()->json([
            'ok' => true,
            'preferences' => [
                'theme' => $prefs->theme,
                'density' => $prefs->density,
                'sidebar_collapsed' => $prefs->sidebar_collapsed,
                'last_workspace' => $prefs->last_workspace,
                'landing_page' => $prefs->landing_page,
            ],
        ]);
    }

    public function switchWorkspace(Request $request, TenantContext $tenant, NavigationContextManager $nav, WorkspaceResolver $workspaces): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $data = $request->validate([
            'workspace' => ['required', 'string', 'max:50'],
        ]);

        $available = $workspaces->availableWorkspaces($request->user(), $organization)->pluck('id');
        abort_unless($available->contains($data['workspace']), 403, __('Module not licensed.'));

        $nav->rememberWorkspace($request->user(), $organization, $data['workspace']);

        $meta = $workspaces->availableWorkspaces($request->user(), $organization)
            ->firstWhere('id', $data['workspace']);

        return response()->json([
            'ok' => true,
            'workspace' => $data['workspace'],
            'href' => $meta['href'] ?? null,
        ]);
    }

    public function toggleFavoriteWorkspace(Request $request, TenantContext $tenant, FavoriteWorkspacesService $favorites): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $data = $request->validate([
            'workspace' => ['required', 'string', 'max:50'],
        ]);

        $list = $favorites->toggle($request->user(), $organization, $data['workspace']);

        return response()->json(['ok' => true, 'favorite_workspaces' => $list->values()->all()]);
    }

    public function toggleFavorite(Request $request, TenantContext $tenant, FavoritePagesService $favorites): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'href' => ['required', 'string', 'max:2048'],
            'icon' => ['nullable', 'string', 'max:40'],
        ]);

        $list = $favorites->toggle($request->user(), $organization, $data);

        return response()->json(['ok' => true, 'favorites' => $list]);
    }

    public function recordRecent(Request $request, TenantContext $tenant, RecentPagesService $recents): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'href' => ['required', 'string', 'max:2048'],
            'type' => ['nullable', 'string', 'max:60'],
        ]);

        $recents->record($request->user(), $organization, $data);

        return response()->json(['ok' => true]);
    }

    public function clearRecents(Request $request, TenantContext $tenant, RecentPagesService $recents): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $recents->clear($request->user(), $organization);

        return response()->json(['ok' => true]);
    }
}
