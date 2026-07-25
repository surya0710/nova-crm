<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Services\Administration\OrganizationSecurityService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(Request $request, TenantContext $tenant, OrganizationSecurityService $security): View
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        return view('administration.security.index', [
            'organization' => $organization,
            'policies' => $security->policies($organization),
            'overview' => $security->overview($organization),
            'loginHistory' => $security->loginHistory($organization),
        ]);
    }

    public function updatePolicies(Request $request, TenantContext $tenant, OrganizationSecurityService $security): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $validated = $request->validate([
            'password_min_length' => ['required', 'integer', 'min:6', 'max:128'],
            'password_require_special' => ['sometimes', 'boolean'],
            'password_require_number' => ['sometimes', 'boolean'],
            'password_require_uppercase' => ['sometimes', 'boolean'],
            'mfa_required' => ['sometimes', 'boolean'],
            'session_lifetime_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'max_concurrent_sessions' => ['required', 'integer', 'min:1', 'max:50'],
            'trusted_devices_enabled' => ['sometimes', 'boolean'],
            'api_token_expiry_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $security->updatePolicies($organization, [
            'password_min_length' => $validated['password_min_length'],
            'password_require_special' => $request->boolean('password_require_special'),
            'password_require_number' => $request->boolean('password_require_number'),
            'password_require_uppercase' => $request->boolean('password_require_uppercase'),
            'mfa_required' => $request->boolean('mfa_required'),
            'session_lifetime_minutes' => $validated['session_lifetime_minutes'],
            'max_concurrent_sessions' => $validated['max_concurrent_sessions'],
            'trusted_devices_enabled' => $request->boolean('trusted_devices_enabled'),
            'api_token_expiry_days' => $validated['api_token_expiry_days'],
        ], $request->user());

        return redirect()
            ->route('administration.security.index')
            ->with('status', __('Security policies updated.'));
    }
}
