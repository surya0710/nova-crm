<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\PlatformLoginRequest;
use App\Services\Platform\PlatformAuditService;
use App\Services\Platform\PlatformUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('platform.auth.login');
    }

    public function store(
        PlatformLoginRequest $request,
        PlatformUserService $users,
        PlatformAuditService $audit,
    ): RedirectResponse {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::guard('platform')->user();
        $users->recordLogin($user);

        $audit->log('platform.login', $user, null, [], 'Platform login', $request);

        return redirect()->intended(route('platform.dashboard'));
    }

    public function destroy(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $user = Auth::guard('platform')->user();

        if ($user) {
            $audit->log('platform.logout', $user, null, [], 'Platform logout', $request);
        }

        Auth::guard('platform')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}
