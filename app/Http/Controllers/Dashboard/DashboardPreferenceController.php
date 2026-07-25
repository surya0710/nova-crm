<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\SaveDashboardLayoutRequest;
use App\Models\DashboardWidget;
use App\Services\Dashboard\DashboardPreferenceService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardPreferenceController extends Controller
{
    public function show(Request $request, DashboardPreferenceService $preferences, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.view'), 403);

        return response()->json([
            'preferences' => $preferences->preferences($request->user(), $tenant->get()),
        ]);
    }

    public function update(SaveDashboardLayoutRequest $request, DashboardPreferenceService $preferences, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.customize'), 403);

        $preferences->saveLayout($request->user(), $tenant->get(), $request->validated('layout'));

        return response()->json(['status' => 'saved']);
    }

    public function reset(Request $request, DashboardPreferenceService $preferences, TenantContext $tenant): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.customize'), 403);

        $preferences->resetLayout($request->user(), $tenant->get());

        if ($request->expectsJson()) {
            return response()->json(['status' => 'reset']);
        }

        return back()->with('status', 'dashboard-reset');
    }

    public function hide(Request $request, DashboardWidget $widget, DashboardPreferenceService $preferences, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.customize'), 403);

        $preferences->hideWidget($request->user(), $tenant->get(), $widget->id);

        return response()->json(['status' => 'hidden']);
    }

    public function restore(Request $request, DashboardWidget $widget, DashboardPreferenceService $preferences, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.customize'), 403);

        $preferences->restoreWidget($request->user(), $tenant->get(), $widget->id);

        return response()->json(['status' => 'restored']);
    }
}
