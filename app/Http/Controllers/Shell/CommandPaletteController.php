<?php

namespace App\Http\Controllers\Shell;

use App\Http\Controllers\Controller;
use App\Services\CommandPalette\CommandPaletteRegistry;
use App\Services\TenantContext;
use App\Services\Theme\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandPaletteController extends Controller
{
    public function index(Request $request, TenantContext $tenant, CommandPaletteRegistry $registry, ThemeService $theme): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $query = (string) $request->query('q', '');
        $commands = $registry->commands($request->user(), $organization, $query);
        $prefs = $theme->preferencesFor($request->user(), $organization);

        return response()->json([
            'commands' => $commands,
            'recent' => collect($prefs->recent_commands ?? [])->take(8),
        ]);
    }

    public function record(Request $request, TenantContext $tenant, ThemeService $theme): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $data = $request->validate([
            'id' => ['required', 'string', 'max:120'],
            'label' => ['required', 'string', 'max:120'],
            'href' => ['nullable', 'string', 'max:2048'],
        ]);

        $prefs = $theme->preferencesFor($request->user(), $organization);
        $recent = collect($prefs->recent_commands ?? [])
            ->reject(fn ($c) => ($c['id'] ?? null) === $data['id'])
            ->prepend($data)
            ->take(10)
            ->values()
            ->all();

        $theme->updatePreferences($request->user(), $organization, ['recent_commands' => $recent]);

        return response()->json(['ok' => true]);
    }
}
