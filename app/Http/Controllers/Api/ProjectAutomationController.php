<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ProjectAutomationController extends Controller
{
    public function index(Request $request, TenantContext $tenant): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('projects.automation.view'), 403);

        $triggers = collect(config('workflows.triggers', []))
            ->filter(function ($definition, $key) {
                $key = is_string($key) ? $key : ($definition['key'] ?? '');

                return str_starts_with((string) $key, 'project.')
                    || str_starts_with((string) $key, 'task.')
                    || str_starts_with((string) $key, 'comment.')
                    || str_starts_with((string) $key, 'notification.');
            })
            ->map(function ($definition, $key) {
                if (is_string($definition)) {
                    return [
                        'key' => $key,
                        'label' => $definition,
                    ];
                }

                return [
                    'key' => $definition['key'] ?? $key,
                    'label' => $definition['label'] ?? $definition['name'] ?? $key,
                    'description' => $definition['description'] ?? null,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'triggers' => $triggers,
                'workflows_url' => Route::has('workflows.index') ? route('workflows.index') : null,
                'organization_id' => $tenant->id(),
            ],
        ]);
    }
}
