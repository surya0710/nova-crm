<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformCommandPaletteService;
use App\Services\Platform\PlatformSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShellController extends Controller
{
    public function commands(Request $request, PlatformCommandPaletteService $palette): JsonResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.dashboard');

        $query = (string) $request->query('q', '');

        return response()->json([
            'commands' => $palette->commands(auth('platform')->user(), $query),
            'recent' => $palette->recent(auth('platform')->user()),
        ]);
    }

    public function search(Request $request, PlatformSearchService $search): JsonResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.dashboard');

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'scope' => ['nullable', 'string', 'max:40'],
        ]);

        $query = trim((string) ($data['q'] ?? ''));
        $scope = $data['scope'] ?? 'all';
        $user = auth('platform')->user();

        return response()->json([
            'results' => $search->search($user, $query, $scope),
            'scopes' => $search->scopes($user),
            'recent' => $search->recent($user),
        ]);
    }
}
