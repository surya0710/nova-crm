<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Platform\PlatformLicensingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LicensingController extends Controller
{
    public function index(PlatformLicensingService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.licensing.view');

        return view('platform.licensing.index', $service->index());
    }

    public function updatePlan(Request $request, PlatformLicensingService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.licensing.manage');

        $validated = $request->validate([
            'slug' => ['required', Rule::in(array_keys(config('platform.plan_definitions', [])))],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'modules' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
            'limits.users' => ['nullable', 'integer', 'min:0'],
            'limits.storage_mb' => ['nullable', 'integer', 'min:0'],
            'limits.api_requests_per_day' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
        ]);

        $slug = $validated['slug'];
        unset($validated['slug']);

        $service->updatePlanDefinition($slug, $validated, auth('platform')->user());

        return back()->with('status', __('Plan definition updated.'));
    }

    public function assignModules(Request $request, Organization $organization, PlatformLicensingService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.licensing.manage');

        $validated = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*' => ['string', 'max:100'],
        ]);

        $service->assignModules($organization, $validated['modules'], auth('platform')->user());

        return back()->with('status', __('Modules assigned.'));
    }

    public function setQuotas(Request $request, Organization $organization, PlatformLicensingService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.licensing.manage');

        $validated = $request->validate([
            'users' => ['nullable', 'integer', 'min:0'],
            'storage_mb' => ['nullable', 'integer', 'min:0'],
            'api_requests_per_day' => ['nullable', 'integer', 'min:0'],
        ]);

        $service->setQuotas($organization, $validated, auth('platform')->user());

        return back()->with('status', __('Quotas updated.'));
    }
}
