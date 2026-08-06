<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\WorkspaceService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecentActivitiesController extends Controller
{
    public function index(Request $request, WorkspaceService $workspace, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.view'), 403);

        $data = $workspace->build($request->user(), $tenant->get());

        return response()->json($data['recent_activities'] ?? []);
    }
}
