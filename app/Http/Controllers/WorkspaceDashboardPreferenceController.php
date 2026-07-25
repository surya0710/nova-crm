<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserUiPreference;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceDashboardPreferenceController extends Controller
{
    public function update(Request $request, TenantContext $tenant): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $data = $request->validate([
            'workspace' => ['required', 'in:marketing,analytics'],
            'widgets' => ['nullable', 'array'],
            'widgets.*' => ['string', 'max:80'],
            'hidden' => ['nullable', 'array'],
            'hidden.*' => ['string', 'max:80'],
        ]);

        $prefs = UserUiPreference::query()->firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'organization_id' => $organization->id,
            ],
            [
                'theme' => 'system',
                'density' => 'comfortable',
            ]
        );

        $layout = is_array($prefs->dashboard_layout) ? $prefs->dashboard_layout : [];
        $layout[$data['workspace']] = [
            'widgets' => array_values($data['widgets'] ?? []),
            'hidden' => array_values($data['hidden'] ?? []),
        ];
        $prefs->dashboard_layout = $layout;
        $prefs->save();

        app(\App\Services\Dashboard\DashboardCache::class)->clearUser($organization->id, $request->user()->id);

        return response()->json([
            'ok' => true,
            'layout' => $layout[$data['workspace']],
        ]);
    }
}
