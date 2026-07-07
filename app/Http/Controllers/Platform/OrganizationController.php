<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
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
}
