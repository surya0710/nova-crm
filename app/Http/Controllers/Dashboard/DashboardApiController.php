<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function index(Request $request, DashboardService $dashboard, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.view'), 403);

        return response()->json(
            $dashboard->build($request->user(), $tenant->get())
        );
    }
}
