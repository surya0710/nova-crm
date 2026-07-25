<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Careers\UpdateCareerSiteRequest;
use App\Models\Organization;
use App\Services\Recruitment\CareerSiteService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CareerSiteSettingsController extends Controller
{
    public function __construct(
        protected CareerSiteService $careerSiteService,
        protected TenantContext $tenant,
    ) {
        $this->middleware('permission:recruitment.careers.manage');
    }

    public function edit(): View
    {
        $organization = Organization::query()->findOrFail($this->tenant->id());
        $settings = $this->careerSiteService->getSettings($organization);

        return view('hrms.recruitment.careers.settings', [
            'settings' => $settings,
            'organization' => $organization,
        ]);
    }

    public function update(UpdateCareerSiteRequest $request): RedirectResponse
    {
        $organization = Organization::query()->findOrFail($this->tenant->id());
        $settings = $this->careerSiteService->getSettings($organization);

        $this->careerSiteService->updateCareerSite(
            $settings,
            $request->safe()->except(['logo', 'banner']),
            $request->user(),
            $request->file('logo'),
            $request->file('banner'),
        );

        return back()->with('status', __('Careers site settings updated.'));
    }
}
