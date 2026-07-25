<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Services\Identity\UserInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AcceptInvitationController extends Controller
{
    public function __construct(protected UserInvitationService $invitations) {}

    public function show(string $token): View|RedirectResponse
    {
        $invitation = $this->invitations->findAcceptableByToken($token);

        if (! $invitation) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('This invitation link is invalid or has expired. Ask your administrator to resend it.')]);
        }

        $invitation->load(['user', 'organization']);

        return view('auth.accept-invitation', [
            'token' => $token,
            'invitation' => $invitation,
            'user' => $invitation->user,
            'organization' => $invitation->organization,
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        $this->invitations->accept($token, $request->validated('password'));

        return redirect()
            ->route('login')
            ->with('status', __('Your password has been set. You can sign in now.'));
    }
}
