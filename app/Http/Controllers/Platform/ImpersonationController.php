<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Platform\PlatformImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ImpersonationController extends Controller
{
    public function start(
        Organization $organization,
        PlatformImpersonationService $impersonation,
    ): RedirectResponse {
        Gate::forUser(auth('platform')->user())->authorize('platform.impersonate');

        $token = $impersonation->createToken(auth('platform')->user(), $organization);

        return redirect()->route('impersonation.accept', ['token' => $token]);
    }

    public function accept(string $token, Request $request, PlatformImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->acceptToken($token, $request);

        return redirect()->route('dashboard');
    }

    public function stop(Request $request, PlatformImpersonationService $impersonation): RedirectResponse
    {
        if (! $impersonation->isActive($request)) {
            return redirect()->route('dashboard');
        }

        $impersonation->stop($request);

        return redirect()->route('platform.login');
    }
}
