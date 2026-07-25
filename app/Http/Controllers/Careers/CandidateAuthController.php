<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Careers\CandidateForgotPasswordRequest;
use App\Http\Requests\Careers\CandidateLoginRequest;
use App\Http\Requests\Careers\CandidateRegisterRequest;
use App\Http\Requests\Careers\CandidateResetPasswordRequest;
use App\Models\Organization;
use App\Services\Recruitment\CandidateAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CandidateAuthController extends Controller
{
    public function __construct(protected CandidateAccountService $accountService) {}

    public function createLogin(): View
    {
        return view('careers.auth.login');
    }

    public function login(CandidateLoginRequest $request, Organization $organization): RedirectResponse
    {
        $request->authenticate($organization);
        $request->session()->regenerate();
        $this->accountService->recordLogin($request->user('candidate'));

        return redirect()->intended(route('careers.dashboard', $organization));
    }

    public function createRegister(): View
    {
        return view('careers.auth.register');
    }

    public function register(CandidateRegisterRequest $request, Organization $organization): RedirectResponse
    {
        $account = $this->accountService->register($organization, $request->validated());
        Auth::guard('candidate')->login($account);
        $request->session()->regenerate();

        return redirect()->route('careers.dashboard', $organization);
    }

    public function logout(Request $request, Organization $organization): RedirectResponse
    {
        Auth::guard('candidate')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('careers.home', $organization);
    }

    public function createForgotPassword(): View
    {
        return view('careers.auth.forgot-password');
    }

    public function forgotPassword(CandidateForgotPasswordRequest $request, Organization $organization): RedirectResponse
    {
        $this->accountService->sendPasswordResetLink($organization, $request->validated('email'));

        return back()->with('status', __('If an account exists, a reset link has been generated.'));
    }

    public function createResetPassword(Request $request, Organization $organization, string $token): View
    {
        return view('careers.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(CandidateResetPasswordRequest $request, Organization $organization): RedirectResponse
    {
        $this->accountService->resetPassword($organization, $request->validated());

        return redirect()->route('careers.login', $organization)
            ->with('status', __('Your password has been reset.'));
    }
}
