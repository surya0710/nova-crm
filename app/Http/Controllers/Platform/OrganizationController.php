<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreOrganizationRequest;
use App\Http\Requests\Platform\UpdateOrganizationRequest;
use App\Models\IndustryTemplate;
use App\Models\Organization;
use App\Services\Platform\OrganizationManagementService;
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

    public function edit(Organization $organization): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        return view('platform.organizations.edit', [
            'organization' => $organization,
            'plans' => config('platform.plans'),
            'timezones' => timezone_identifiers_list(),
            'currencies' => config('nova.currencies'),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization, OrganizationManagementService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $service->update($organization, $request->validated(), auth('platform')->user());

        return redirect()
            ->route('platform.organizations.show', $organization)
            ->with('status', __('Organization updated.'));
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
