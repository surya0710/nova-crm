<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Identity\UserAccountService;
use App\Services\Navigation\NavigationService;
use App\Services\Platform\OrganizationLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(
        LoginRequest $request,
        OrganizationLifecycleService $lifecycle,
        NavigationService $navigation,
        UserAccountService $accounts,
    ): RedirectResponse {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $organization = $user instanceof User ? $user->organizations()->first() : null;

        // Tenant context before login audit — User has no organization_id column.
        if ($organization) {
            $lifecycle->assertCanLogin($organization);
            $request->session()->put('current_organization_id', $organization->id);
        }

        if ($user instanceof User) {
            $accounts->recordSuccessfulLogin($user);
        }

        if ($organization) {
            $landing = $navigation->resolveLandingUrl($user, $organization);

            return redirect()->intended($landing);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, UserAccountService $accounts): RedirectResponse
    {
        $user = Auth::user();
        if ($user instanceof User) {
            $accounts->recordLogout($user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
