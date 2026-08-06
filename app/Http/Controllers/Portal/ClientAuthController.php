<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\ClientAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientAuthController extends Controller
{
    public function __construct(protected ClientAccessService $access) {}

    public function createLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request, Organization $organization): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $ok = Auth::guard('client')->attempt([
            'email' => strtolower(trim($credentials['email'])),
            'password' => $credentials['password'],
            'organization_id' => $organization->id,
            'is_active' => true,
        ], $request->boolean('remember'));

        if (! $ok) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();
        $this->access->recordLogin($request->user('client'));

        return redirect()->intended(route('portal.dashboard', $organization));
    }

    public function logout(Request $request, Organization $organization): RedirectResponse
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login', $organization);
    }
}
