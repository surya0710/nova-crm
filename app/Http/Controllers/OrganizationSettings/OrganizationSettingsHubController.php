<?php

namespace App\Http\Controllers\OrganizationSettings;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationSettingsHubController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);
        $this->authorize('viewSettings', $organization);

        $user = $request->user();
        $sections = collect(config('organization_settings.sections', []))
            ->filter(function (array $section) use ($user, $organization) {
                $permission = $section['permission'] ?? null;
                $fallback = $section['fallback_permission'] ?? null;

                if ($permission && $user->hasPermission($permission, $organization)) {
                    return true;
                }

                if ($fallback && $user->hasPermission($fallback, $organization)) {
                    return true;
                }

                return $permission === null;
            })
            ->groupBy(fn (array $section) => $section['group'] ?? 'organization');

        return view('organization-settings.index', [
            'organization' => $organization,
            'groupedSections' => $sections,
            'groups' => config('organization_settings.groups', []),
            'futureModules' => config('organization_settings.future_modules', []),
        ]);
    }
}
