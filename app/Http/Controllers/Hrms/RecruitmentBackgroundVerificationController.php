<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreBackgroundVerificationRequest;
use App\Models\HiringDecision;
use App\Models\RecruitmentBackgroundVerification;
use App\Services\Recruitment\BackgroundVerificationService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecruitmentBackgroundVerificationController extends Controller
{
    public function __construct(protected BackgroundVerificationService $backgroundVerification)
    {
    }

    public function index(TenantContext $tenant): View
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.view', $organization), 403);

        return view('hrms.recruitment.integrations.background-verification', [
            'verifications' => RecruitmentBackgroundVerification::query()
                ->where('organization_id', $organization->id)
                ->with(['candidate', 'provider', 'hiringDecision'])
                ->latest()
                ->paginate(20),
            'decisions' => HiringDecision::query()
                ->where('organization_id', $organization->id)
                ->where('recommendation', 'hire')
                ->with('jobApplication.candidate')
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function store(StoreBackgroundVerificationRequest $request, TenantContext $tenant): RedirectResponse
    {
        $user = $request->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);

        $decision = HiringDecision::query()->findOrFail($request->integer('hiring_decision_id'));
        abort_unless((int) $decision->organization_id === (int) $organization->id, 404);

        $this->backgroundVerification->submit($decision, $user, $request->input('provider_slug'));

        return back()->with('status', 'recruitment-background-verification-submitted');
    }

    public function refresh(RecruitmentBackgroundVerification $verification, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);
        abort_unless((int) $verification->organization_id === (int) $organization->id, 404);

        $this->backgroundVerification->refreshStatus($verification, $user);

        return back()->with('status', 'recruitment-background-verification-refreshed');
    }

    public function cancel(RecruitmentBackgroundVerification $verification, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);
        abort_unless((int) $verification->organization_id === (int) $organization->id, 404);

        $this->backgroundVerification->cancel($verification, $user);

        return back()->with('status', 'recruitment-background-verification-cancelled');
    }
}