<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingProvidersController extends Controller
{
    public function __construct(protected MarketingProviderService $providers) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'marketing.view', 'marketing.manage', 'integrations.view', 'integrations.manage',
            ]),
            403
        );

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $cards = collect($this->providers->integrationCardsForOrganization($organization))
            ->map(function (array $card) {
                $health = 'unknown';
                if (! empty($card['last_error']) || ($card['status'] ?? null) === 'error') {
                    $health = 'unhealthy';
                } elseif (($card['status'] ?? null) === 'expired') {
                    $health = 'degraded';
                } elseif ($card['connected'] ?? false) {
                    $health = 'healthy';
                } elseif (($card['status'] ?? null) === 'disconnected') {
                    $health = 'disconnected';
                }

                $labels = [
                    'healthy' => __('Healthy'),
                    'degraded' => __('Degraded'),
                    'unhealthy' => __('Unhealthy'),
                    'disconnected' => __('Disconnected'),
                ];

                return array_merge($card, [
                    'health' => $health,
                    'health_label' => $labels[$health] ?? __('Unknown'),
                ]);
            });

        $planned = [
            ['slug' => 'google_analytics', 'name' => 'Google Analytics', 'channel' => 'analytics', 'status' => 'planned'],
            ['slug' => 'google_tag_manager', 'name' => 'Google Tag Manager', 'channel' => 'analytics', 'status' => 'planned'],
            ['slug' => 'email', 'name' => __('Email Providers'), 'channel' => 'email', 'status' => 'planned'],
            ['slug' => 'sms', 'name' => __('SMS Providers'), 'channel' => 'sms', 'status' => 'planned'],
        ];

        return view('marketing.providers.index', [
            'cards' => $cards,
            'planned' => $planned,
            'integrationsHref' => route('integrations.index'),
        ]);
    }
}
