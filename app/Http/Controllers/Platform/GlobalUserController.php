<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Platform\PlatformGlobalUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class GlobalUserController extends Controller
{
    public function index(Request $request, PlatformGlobalUserService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.global_users.view');

        return view('platform.global-users.index', [
            'users' => $service->paginate($request->only(['search', 'organization_id', 'locked'])),
            'filters' => $request->only(['search', 'organization_id', 'locked']),
        ]);
    }

    public function loginHistory(Request $request, PlatformGlobalUserService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.global_users.view');

        return view('platform.global-users.login-history', [
            'logs' => $service->loginHistory($request->only(['search'])),
            'filters' => $request->only(['search']),
        ]);
    }

    public function sessions(Request $request, PlatformGlobalUserService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.global_users.view');

        return view('platform.global-users.sessions', [
            'sessions' => $service->activeSessions($request->only(['search'])),
            'filters' => $request->only(['search']),
        ]);
    }

    public function mfa(Request $request, PlatformGlobalUserService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.global_users.view');

        return view('platform.global-users.mfa', [
            'users' => $service->mfaStatus($request->only(['search', 'mfa'])),
            'filters' => $request->only(['search', 'mfa']),
        ]);
    }

    public function lock(User $user, PlatformGlobalUserService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.global_users.manage');

        $service->lock($user, auth('platform')->user());

        return back()->with('status', __('User locked.'));
    }

    public function unlock(User $user, PlatformGlobalUserService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.global_users.manage');

        $service->unlock($user, auth('platform')->user());

        return back()->with('status', __('User unlocked.'));
    }

    public function passwordReset(User $user, PlatformGlobalUserService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.global_users.manage');

        $status = $service->sendPasswordReset($user, auth('platform')->user());

        return back()->with(
            'status',
            $status === Password::RESET_LINK_SENT
                ? __('Password reset link sent.')
                : __('Unable to send password reset link.')
        );
    }

    public function revokeSession(Request $request, PlatformGlobalUserService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.global_users.manage');

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);

        $service->revokeSession($validated['session_id'], auth('platform')->user());

        return back()->with('status', __('Session revoked.'));
    }
}
