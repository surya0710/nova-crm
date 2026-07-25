<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfigurationController extends Controller
{
    public function index(PlatformConfigurationService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.configuration.view');

        return view('platform.configuration.index', [
            'configuration' => $service->all(),
            'groups' => array_keys(config('platform.configuration_defaults', [])),
        ]);
    }

    public function update(Request $request, PlatformConfigurationService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.configuration.manage');

        $validated = $request->validate([
            'group' => ['required', Rule::in(array_merge(
                array_keys(config('platform.configuration_defaults', [])),
                ['email_templates']
            ))],
            'data' => ['required', 'array'],
        ]);

        if ($validated['group'] === 'email_templates') {
            $service->set('configuration', 'email_templates', $validated['data'], auth('platform')->user());
        } else {
            $service->updateGroup($validated['group'], $validated['data'], auth('platform')->user());
        }

        return back()->with('status', __('Configuration updated.'));
    }
}
