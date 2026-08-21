<?php

namespace App\Http\Controllers\OrganizationSettings;

use App\Http\Controllers\Controller;
use App\Services\Configuration\ConfigurationRecentSettingsService;
use App\Services\Configuration\ConfigurationRegistry;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationSettingsHubController extends Controller
{
    public function __invoke(
        Request $request,
        TenantContext $tenant,
        ConfigurationRegistry $registry,
        ConfigurationRecentSettingsService $recentSettings,
    ): View {
        $organization = $tenant->get();
        abort_unless($organization, 404);
        $this->authorize('viewSettings', $organization);

        $modules = $registry->visibleModules($request->user(), $organization);
        $visibleSections = $registry->visibleSectionsForSearch($request->user(), $organization);

        return view('organization-settings.index', [
            'organization' => $organization,
            'modules' => $modules,
            'recentSettings' => $recentSettings->visible($request->user(), $organization, $visibleSections),
            'futureModules' => $registry->futureModules(),
            'hubBreadcrumbs' => $registry->breadcrumbItems('organization.settings.hub'),
        ]);
    }
}
