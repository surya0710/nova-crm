<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\WorkspaceService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function show(Request $request, WorkspaceService $workspace, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('dashboard.view'), 403);

        return response()->json(
            $workspace->build($request->user(), $tenant->get())
        );
    }
}
