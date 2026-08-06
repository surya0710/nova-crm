<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Careers\UpdateCandidatePortalSettingsRequest;
use App\Models\CandidateAccount;
use App\Models\Organization;
use App\Services\Recruitment\CareerSiteService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidatePortalSettingsController extends Controller
{
    public function __construct(
        protected CareerSiteService $careerSiteService,
        protected TenantContext $tenant,
    ) {
        $this->middleware('permission:recruitment.portal.settings');
    }

    public function edit(): View
    {
        $organization = Organization::query()->findOrFail($this->tenant->id());
        $settings = $this->careerSiteService->getPortalSettings($organization);

        return view('hrms.recruitment.portal.settings', [
            'settings' => $settings,
            'organization' => $organization,
        ]);
    }

    public function update(UpdateCandidatePortalSettingsRequest $request): RedirectResponse
    {
        $organization = Organization::query()->findOrFail($this->tenant->id());
        $settings = $this->careerSiteService->getPortalSettings($organization);

        $this->careerSiteService->updatePortalSettings(
            $settings,
            $request->validated(),
            $request->user(),
        );

        return back()->with('status', __('Candidate portal settings updated.'));
    }
}
