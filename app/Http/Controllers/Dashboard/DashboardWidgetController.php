<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateOrganizationWidgetRequest;
use App\Models\DashboardWidget;
use App\Services\Dashboard\DashboardWidgetService;
use App\Services\Dashboard\WidgetDataService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardWidgetController extends Controller
{
    public function index(Request $request, DashboardWidgetService $widgetService, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.view'), 403);

        $organization = $tenant->get();
        $widgets = $widgetService->discover($organization);

        return response()->json([
            'widgets' => $widgets->map(fn (DashboardWidget $w) => [
                'id' => $w->id,
                'widget_key' => $w->widget_key,
                'name' => $w->name,
                'module' => $w->module,
                'is_visible' => $widgetService->validateWidget($w, $request->user(), $organization),
            ]),
        ]);
    }

    public function data(Request $request, string $widgetKey, WidgetDataService $dataService, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.view'), 403);

        return response()->json(
            $dataService->lazyLoad($request->user(), $tenant->get(), $widgetKey)
        );
    }

    public function refresh(Request $request, DashboardWidget $widget, WidgetDataService $dataService, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.view'), 403);

        return response()->json(
            $dataService->refresh($request->user(), $tenant->get(), $widget)
        );
    }

    public function updateOrganization(
        UpdateOrganizationWidgetRequest $request,
        DashboardWidget $widget,
        DashboardWidgetService $widgetService,
        TenantContext $tenant,
    ): JsonResponse {
        $organization = $tenant->get();
        $validated = $request->validated();

        if (array_key_exists('is_enabled', $validated)) {
            $validated['is_enabled']
                ? $widgetService->enable($organization, $widget, $request->user())
                : $widgetService->disable($organization, $widget, $request->user());
        }

        return response()->json(['status' => 'updated']);
    }
}
