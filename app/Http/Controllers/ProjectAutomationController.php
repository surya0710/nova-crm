<?php

namespace App\Http\Controllers;

use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class ProjectAutomationController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
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

        return view('projects.automation.index', [
            'triggers' => $triggers,
            'organization' => $tenant->get(),
            'workflowsUrl' => Route::has('workflows.index') ? route('workflows.index') : null,
        ]);
    }
}
