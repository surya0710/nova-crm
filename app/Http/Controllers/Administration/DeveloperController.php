<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class DeveloperController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless(
            $request->user()->hasAnyPermission(['settings.manage', 'api.tokens']),
            403
        );

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $cards = [];

        if (Route::has('api-tokens.index') && $request->user()->hasPermission('api.tokens')) {
            $cards[] = [
                'title' => __('API Tokens'),
                'description' => __('Create and revoke personal access tokens (Laravel Sanctum) for the REST API.'),
                'href' => route('api-tokens.index'),
                'badge' => __('Sanctum'),
            ];
        }

        if (Route::has('integrations.index') && $request->user()->hasAnyPermission(['integrations.view', 'integrations.manage'])) {
            $cards[] = [
                'title' => __('Integrations'),
                'description' => __('Connect marketing and messaging providers for your organization.'),
                'href' => route('integrations.index'),
            ];
        }

        if (Route::has('hrms.recruitment.webhooks.index') && $request->user()->hasAnyPermission(['recruitment.view', 'recruitment.manage', 'settings.manage'])) {
            $cards[] = [
                'title' => __('Recruitment Webhooks'),
                'description' => __('Inspect inbound recruitment webhook deliveries and retries.'),
                'href' => route('hrms.recruitment.webhooks.index'),
            ];
        }

        if (Route::has('marketing.providers.connect')) {
            $cards[] = [
                'title' => __('OAuth Callbacks'),
                'description' => __('Provider OAuth uses Sanctum-backed sessions. Connect flows live under Marketing Providers.'),
                'href' => Route::has('integrations.index') ? route('integrations.index') : null,
                'badge' => __('Docs'),
            ];
        }

        $rateLimits = [
            [
                'label' => __('API lead intake'),
                'value' => __('Configured via RateLimiter api-lead-intake'),
            ],
            [
                'label' => __('Marketing tracking'),
                'value' => __(':count / minute', [
                    'count' => (int) config('marketing.tracking.rate_limit_per_minute', 120),
                ]),
            ],
            [
                'label' => __('Marketing webhooks'),
                'value' => __(':count / minute', [
                    'count' => (int) config('marketing.providers.webhook_rate_limit_per_minute', 120),
                ]),
            ],
        ];

        return view('administration.developer.index', [
            'organization' => $organization,
            'cards' => $cards,
            'rateLimits' => $rateLimits,
        ]);
    }
}
