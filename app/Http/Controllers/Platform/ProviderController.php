<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProviderController extends Controller
{
    public function index(PlatformProviderService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.providers.view');

        return view('platform.providers.index', [
            'summary' => $service->healthSummary(),
        ]);
    }

    public function show(string $provider, PlatformProviderService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.providers.view');

        return view('platform.providers.show', [
            'provider' => $service->inspect($provider),
        ]);
    }

    public function validateProvider(string $provider, PlatformProviderService $service): JsonResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.providers.manage');

        return response()->json($service->validate($provider));
    }

    public function test(string $provider, PlatformProviderService $service): JsonResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.providers.manage');

        return response()->json($service->test($provider));
    }
}
