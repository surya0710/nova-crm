<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Models\JobOpening;
use App\Models\Organization;
use App\Services\Recruitment\CareerSiteService;
use App\Services\Recruitment\SavedJobService;
use Illuminate\View\View;

class CareerJobController extends Controller
{
    public function __construct(
        protected CareerSiteService $careerSiteService,
        protected SavedJobService $savedJobService,
    ) {}

    public function show(Organization $organization, JobOpening $job_opening): View
    {
        $opening = $this->careerSiteService->publishedOpening($organization, $job_opening);
        $isSaved = auth('candidate')->check()
            ? $this->savedJobService->isSaved(auth('candidate')->user(), $opening)
            : false;

        return view('careers.jobs.show', [
            'organization' => $organization,
            'opening' => $opening,
            'settings' => request()->attributes->get('career_site_settings'),
            'portalSettings' => request()->attributes->get('candidate_portal_settings'),
            'isSaved' => $isSaved,
        ]);
    }
}
