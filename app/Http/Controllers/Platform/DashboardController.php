<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformWorkspaceHomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(PlatformWorkspaceHomeService $home): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.dashboard');

        $payload = $home->build(auth('platform')->user());

        return view('platform.dashboard', $payload);
    }

    public function updateWidgets(Request $request): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.dashboard');

        $validated = $request->validate([
            'widgets' => ['required', 'array'],
            'widgets.*' => ['string', 'in:'.implode(',', config('platform.dashboard_widgets', []))],
        ]);

        auth('platform')->user()->setDashboardLayout($validated['widgets']);

        return back()->with('status', __('Dashboard widgets updated.'));
    }
}
