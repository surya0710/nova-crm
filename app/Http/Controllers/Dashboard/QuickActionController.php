<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateOrganizationQuickActionRequest;
use App\Models\DashboardQuickAction;
use App\Services\Dashboard\QuickActionService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickActionController extends Controller
{
    public function index(Request $request, QuickActionService $quickActions, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.view'), 403);

        return response()->json([
            'quick_actions' => $quickActions->available($request->user(), $tenant->get()),
        ]);
    }

    public function updateOrganization(
        UpdateOrganizationQuickActionRequest $request,
        DashboardQuickAction $quickAction,
        QuickActionService $quickActionService,
        TenantContext $tenant,
    ): JsonResponse {
        $record = $quickActionService->updateOrganizationAction(
            $tenant->get(),
            $quickAction,
            $request->validated(),
            $request->user()
        );

        return response()->json(['status' => 'updated', 'id' => $record->id]);
    }
}
