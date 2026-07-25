<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Services\Administration\OrganizationModulesService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModulesController extends Controller
{
    public function index(Request $request, TenantContext $tenant, OrganizationModulesService $modules): View
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        return view('administration.modules.index', [
            'organization' => $organization,
            'overview' => $modules->overview($organization),
        ]);
    }

    public function update(Request $request, TenantContext $tenant, OrganizationModulesService $modules): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $validated = $request->validate([
            'feature_toggles' => ['nullable', 'array'],
            'feature_toggles.*' => ['nullable'],
            'workspace_visibility' => ['nullable', 'array'],
            'workspace_visibility.*' => ['nullable'],
            'default_landing_pages' => ['nullable', 'array'],
            'default_landing_pages.*' => ['nullable', 'string', 'max:120'],
        ]);

        $modules->update($organization, $validated, $request->user());

        return redirect()
            ->route('administration.modules.index')
            ->with('status', __('Module preferences updated.'));
    }
}
