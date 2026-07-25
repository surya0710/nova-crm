<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreOrganizationRequest;
use App\Http\Requests\Platform\UpdateOrganizationModulesRequest;
use App\Http\Requests\Platform\UpdateOrganizationQuotasRequest;
use App\Http\Requests\Platform\UpdateOrganizationRequest;
use App\Models\IndustryTemplate;
use App\Models\Organization;
use App\Services\Platform\OrganizationManagementService;
use App\Services\Platform\PlatformLicensingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request, OrganizationManagementService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.view');

        return view('platform.organizations.index', [
            'organizations' => $service->paginate($request->only([
                'search', 'status', 'plan', 'created_from', 'created_to',
            ])),
            'statuses' => config('platform.organization_statuses'),
            'plans' => config('platform.plans'),
            'filters' => $request->only(['search', 'status', 'plan', 'created_from', 'created_to']),
        ]);
    }

    public function create(): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        return view('platform.organizations.create', [
            'plans' => config('platform.plans'),
            'statuses' => config('platform.organization_statuses'),
            'timezones' => timezone_identifiers_list(),
            'currencies' => config('nova.currencies'),
            'templates' => IndustryTemplate::query()
                ->with('currentVersion')
                ->where('status', 'published')
                ->whereNotNull('current_version_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreOrganizationRequest $request, OrganizationManagementService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $organization = $service->create($request->validated(), auth('platform')->user());

        return redirect()
            ->route('platform.organizations.show', $organization)
            ->with('status', __('Organization created.'));
    }

    public function show(Organization $organization, OrganizationManagementService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.view');

        return view('platform.organizations.show', $service->profile($organization));
    }

    public function suspend(Organization $organization, OrganizationManagementService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $service->suspend($organization, auth('platform')->user());

        return back()->with('status', __('Organization suspended.'));
    }

    public function activate(Organization $organization, OrganizationManagementService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $service->activate($organization, auth('platform')->user());

        return back()->with('status', __('Organization activated.'));
    }

    public function archive(Organization $organization, OrganizationManagementService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $service->archive($organization, auth('platform')->user());

        return back()->with('status', __('Organization archived.'));
    }

    public function edit(Request $request, Organization $organization, PlatformLicensingService $licensing): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $tab = $request->string('tab', 'general')->toString();
        $allowedTabs = ['general', 'subscription', 'modules', 'limits', 'billing', 'activity'];
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'general';
        }

        $licensingData = $licensing->organizationLicensing($organization);
        $service = app(OrganizationManagementService::class);
        $profile = $service->profile($organization);

        return view('platform.organizations.edit', [
            'organization' => $organization,
            'tab' => $tab,
            'plans' => config('platform.plans'),
            'timezones' => timezone_identifiers_list(),
            'currencies' => config('nova.currencies'),
            'licensing' => $licensingData,
            'canManageLicensing' => auth('platform')->user()->hasPermission('platform.licensing.manage'),
            'recent_audit' => $profile['recent_audit'] ?? collect(),
            'subscription' => $profile['subscription'] ?? [],
            'usage' => $profile['usage'] ?? [],
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization, OrganizationManagementService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $service->update($organization, $request->validated(), auth('platform')->user());

        return redirect()
            ->route('platform.organizations.edit', ['organization' => $organization, 'tab' => 'general'])
            ->with('status', __('Organization updated.'));
    }

    public function updateModules(
        UpdateOrganizationModulesRequest $request,
        Organization $organization,
        PlatformLicensingService $licensing,
    ): RedirectResponse {
        Gate::forUser(auth('platform')->user())->authorize('platform.licensing.manage');

        $modules = $request->validated('modules') ?? [];
        $licensing->assignModules($organization, $modules, auth('platform')->user());

        return redirect()
            ->route('platform.organizations.edit', ['organization' => $organization, 'tab' => 'modules'])
            ->with('status', __('Modules updated.'));
    }

    public function updateLimits(
        UpdateOrganizationQuotasRequest $request,
        Organization $organization,
        PlatformLicensingService $licensing,
    ): RedirectResponse {
        Gate::forUser(auth('platform')->user())->authorize('platform.licensing.manage');

        $licensing->setQuotas($organization, $request->validated(), auth('platform')->user());

        return redirect()
            ->route('platform.organizations.edit', ['organization' => $organization, 'tab' => 'limits'])
            ->with('status', __('Limits updated.'));
    }

    public function restore(Organization $organization, OrganizationManagementService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $service->restore($organization, auth('platform')->user());

        return back()->with('status', __('Organization restored.'));
    }

    public function destroy(Organization $organization, OrganizationManagementService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $service->delete($organization, auth('platform')->user());

        return redirect()
            ->route('platform.organizations.index')
            ->with('status', __('Organization deleted.'));
    }
}
