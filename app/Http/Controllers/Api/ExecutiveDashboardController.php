<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveDashboardService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutiveDashboardController extends Controller
{
    public function __construct(protected ExecutiveDashboardService $executiveService) {}

    public function show(Request $request, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('projects.executive.view'), 403);

        return response()->json([
            'data' => $this->executiveService->forOrganization($tenant->get(), $request->user()),
        ]);
    }
}
