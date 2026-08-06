<?php

namespace App\Http\Middleware;

use App\Models\CandidatePortalSetting;
use App\Models\CareerSiteSetting;
use App\Models\Organization;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCareerOrganization
{
    public function __construct(protected TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenant->clear();

        /** @var Organization|null $organization */
        $organization = $request->route('organization');

        if (! $organization instanceof Organization) {
            $organization = Organization::query()
                ->where('slug', $request->route('organization'))
                ->first();
        }

        if (! $organization || ! $organization->is_active) {
            abort(404);
        }

        $this->tenant->set($organization);

        $portalSettings = CandidatePortalSetting::query()
            ->where('organization_id', $organization->id)
            ->first();

        if ($portalSettings && ! $portalSettings->portal_enabled) {
            abort(404);
        }

        $careerSettings = CareerSiteSetting::query()
            ->where('organization_id', $organization->id)
            ->first();

        if ($careerSettings && ! $careerSettings->is_published) {
            $isAdminPreview = $request->user('web')?->hasPermission('recruitment.careers.manage', $organization);
            if (! $isAdminPreview) {
                abort(404);
            }
        }

        $request->attributes->set('career_organization', $organization);
        $request->attributes->set('career_site_settings', $careerSettings);
        $request->attributes->set('candidate_portal_settings', $portalSettings);

        view()->share([
            'careerOrganization' => $organization,
            'careerSiteSettings' => $careerSettings,
            'candidatePortalSettings' => $portalSettings,
        ]);

        return $next($request);
    }
}
