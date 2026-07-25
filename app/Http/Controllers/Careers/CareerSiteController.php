<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Recruitment\CareerSiteService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerSiteController extends Controller
{
    public function __construct(protected CareerSiteService $careerSiteService) {}

    public function index(Request $request, Organization $organization): View
    {
        $data = $this->careerSiteService->landingPageData($organization, $request->only([
            'search', 'department_id', 'location', 'employment_type',
        ]));

        return view('careers.home', array_merge($data, [
            'organization' => $organization,
            'settings' => $request->attributes->get('career_site_settings'),
        ]));
    }
}
