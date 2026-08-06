<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(PlatformSecurityService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.security.view');

        return view('platform.security.index', [
            'overview' => $service->overview(),
        ]);
    }

    public function updatePolicies(Request $request, PlatformSecurityService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.security.manage');

        $validated = $request->validate([
            'mfa_required_for_platform' => ['nullable', 'boolean'],
            'mfa_required_for_tenants' => ['nullable', 'boolean'],
            'password_min_length' => ['nullable', 'integer', 'min:8', 'max:128'],
            'password_require_special' => ['nullable', 'boolean'],
            'session_lifetime_minutes' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'max_failed_logins' => ['nullable', 'integer', 'min:1', 'max:50'],
            'ip_allowlist' => ['nullable', 'array'],
            'ip_allowlist.*' => ['string', 'max:45'],
            'trusted_devices_enabled' => ['nullable', 'boolean'],
        ]);

        $service->updatePolicies($validated, auth('platform')->user());

        return back()->with('status', __('Security policies updated.'));
    }
}
