<?php

namespace App\Services\Marketing;

use App\Models\MarketingAttribution;
use App\Models\MarketingCampaign;
use App\Models\MarketingConversion;
use App\Models\MarketingTouch;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use App\Services\Workspace\CachesWorkspaceHome;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class MarketingWorkspaceHomeService
{
    use CachesWorkspaceHome;

    public function __construct(
        protected TenantContext $tenant,
        protected MarketingProviderService $providers,
        protected MarketingCampaignService $campaigns,
    ) {}

    /** @return array<string, mixed> */
    public function build(User $user): array
    {
        return $this->rememberHome('marketing', $user, fn () => $this->buildUncached($user));
    }

    /** @return array<string, mixed> */
    protected function buildUncached(User $user): array
    {
        $organization = $this->tenant->get();
        $prefs = UserUiPreference::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization?->id)
            ->first();

        $defaultWidgets = [
            'active_campaigns', 'leads_generated', 'cost_per_lead', 'conversion_rate',
            'campaign_roi', 'channel_performance', 'google_ads', 'meta_ads',
            'email_performance', 'landing_pages', 'attribution', 'recent_activity',
        ];
        $widgetLayout = $prefs?->dashboard_layout['marketing'] ?? [
            'widgets' => $defaultWidgets,
            'hidden' => [],
        ];

        return [
            'kpis' => $this->kpis($user, $organization),
            'activeCampaigns' => $this->activeCampaigns($organization),
            'channelPerformance' => $this->channelPerformance($organization),
            'providerSummaries' => $this->providerSummaries($user, $organization),
            'landingPages' => $this->landingPagePerformance($organization),
            'attribution' => $this->attributionOverview($organization),
            'emailPerformance' => $this->emailCampaignPlaceholder(),
            'recentActivity' => $organization
                ? $this->campaigns->recentActivity($organization)
                : collect(),
            'quickActions' => $this->quickActions($user),
            'attention' => $this->attention($user, $organization),
            'widgetLayout' => $widgetLayout,
            'availableWidgets' => $this->availableWidgets(),
        ];
    }

    /** @return list<array{key: string, label: string}> */
    public function availableWidgets(): array
    {
        return [
            ['key' => 'active_campaigns', 'label' => __('Active Campaigns')],
            ['key' => 'leads_generated', 'label' => __('Leads Generated')],
            ['key' => 'cost_per_lead', 'label' => __('Cost Per Lead')],
            ['key' => 'conversion_rate', 'label' => __('Conversion Rate')],
            ['key' => 'campaign_roi', 'label' => __('Campaign ROI')],
            ['key' => 'channel_performance', 'label' => __('Channel Performance')],
            ['key' => 'google_ads', 'label' => __('Google Ads Summary')],
            ['key' => 'meta_ads', 'label' => __('Meta Ads Summary')],
            ['key' => 'email_performance', 'label' => __('Email Campaign Performance')],
            ['key' => 'landing_pages', 'label' => __('Landing Page Performance')],
            ['key' => 'attribution', 'label' => __('Attribution Overview')],
            ['key' => 'recent_activity', 'label' => __('Recent Campaign Activity')],
        ];
    }

    protected function kpis(User $user, ?Organization $organization): array
    {
        if (! $organization) {
            return [];
        }

        $activeCampaigns = Schema::hasTable('marketing_campaigns')
            ? MarketingCampaign::query()->where('organization_id', $organization->id)->where('status', MarketingCampaign::STATUS_ACTIVE)->count()
            : 0;

        $attributedLeads = Schema::hasTable('marketing_attributions')
            ? MarketingAttribution::query()->where('organization_id', $organization->id)->whereNotNull('lead_id')->count()
            : 0;

        $budget = Schema::hasTable('marketing_campaigns')
            ? (float) MarketingCampaign::query()->where('organization_id', $organization->id)->where('status', MarketingCampaign::STATUS_ACTIVE)->sum('budget_amount')
            : 0;

        $conversions = Schema::hasTable('marketing_conversions')
            ? MarketingConversion::query()->where('organization_id', $organization->id)->count()
            : 0;

        $conversionValue = Schema::hasTable('marketing_conversions')
            ? (float) MarketingConversion::query()->where('organization_id', $organization->id)->sum('event_value')
            : 0;

        $cpl = $attributedLeads > 0 && $budget > 0 ? number_format($budget / $attributedLeads, 2) : '—';
        $rate = $attributedLeads > 0 ? round(($conversions / $attributedLeads) * 100, 1).'%' : '—';
        $roi = $budget > 0 ? round((($conversionValue - $budget) / $budget) * 100, 1).'%' : '—';

        return [
            ['label' => __('Active Campaigns'), 'value' => $activeCampaigns, 'hint' => __('Currently running'), 'key' => 'active_campaigns'],
            ['label' => __('Leads Generated'), 'value' => $attributedLeads, 'hint' => __('Attributed'), 'key' => 'leads_generated'],
            ['label' => __('Cost Per Lead'), 'value' => $cpl, 'hint' => __('Active budget / leads'), 'key' => 'cost_per_lead'],
            ['label' => __('Conversion Rate'), 'value' => $rate, 'hint' => __('Conversions / leads'), 'key' => 'conversion_rate'],
            ['label' => __('Campaign ROI'), 'value' => $roi, 'hint' => __('Value vs spend'), 'key' => 'campaign_roi'],
        ];
    }

    protected function activeCampaigns(?Organization $organization): Collection
    {
        if (! $organization || ! Schema::hasTable('marketing_campaigns')) {
            return collect();
        }

        return MarketingCampaign::query()
            ->where('organization_id', $organization->id)
            ->where('status', MarketingCampaign::STATUS_ACTIVE)
            ->latest()
            ->limit(8)
            ->get();
    }

    protected function channelPerformance(?Organization $organization): Collection
    {
        if (! $organization || ! Schema::hasTable('marketing_touches')) {
            return collect();
        }

        return MarketingTouch::query()
            ->whereHas('session.visitor', fn ($q) => $q->where('organization_id', $organization->id))
            ->select('channel', DB::raw('count(*) as total'))
            ->groupBy('channel')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'channel' => $row->channel ?: __('Unknown'),
                'total' => (int) $row->total,
            ]);
    }

    protected function providerSummaries(User $user, ?Organization $organization): array
    {
        if (! $organization || ! $user->hasAnyPermission(['integrations.view', 'integrations.manage', 'marketing.view'])) {
            return [];
        }

        $cards = collect($this->providers->integrationCardsForOrganization($organization));

        return [
            'google_ads' => $this->mapProviderCard($cards->firstWhere('slug', 'google_ads')),
            'meta_ads' => $this->mapProviderCard($cards->firstWhere('slug', 'meta')),
            'all' => $cards->map(fn ($card) => $this->mapProviderCard($card))->filter()->values(),
        ];
    }

    protected function mapProviderCard(?array $card): ?array
    {
        if (! $card) {
            return null;
        }

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

        $healthLabels = [
            'healthy' => __('Healthy'),
            'degraded' => __('Degraded'),
            'unhealthy' => __('Unhealthy'),
            'disconnected' => __('Disconnected'),
        ];

        return array_merge($card, [
            'health' => $health,
            'health_label' => $healthLabels[$health] ?? __('Unknown'),
        ]);
    }

    protected function landingPagePerformance(?Organization $organization): Collection
    {
        if (! $organization || ! Schema::hasTable('marketing_touches')) {
            return collect();
        }

        return MarketingTouch::query()
            ->whereHas('session.visitor', fn ($q) => $q->where('organization_id', $organization->id))
            ->whereNotNull('landing_page')
            ->select('landing_page', DB::raw('count(*) as total'))
            ->groupBy('landing_page')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'page' => $row->landing_page,
                'total' => (int) $row->total,
            ]);
    }

    protected function attributionOverview(?Organization $organization): array
    {
        if (! $organization || ! Schema::hasTable('marketing_attributions')) {
            return ['total' => 0, 'with_lead' => 0, 'model' => config('marketing.attribution.default_model')];
        }

        $total = MarketingAttribution::query()->where('organization_id', $organization->id)->count();
        $withLead = MarketingAttribution::query()->where('organization_id', $organization->id)->whereNotNull('lead_id')->count();

        return [
            'total' => $total,
            'with_lead' => $withLead,
            'model' => config('marketing.attribution.default_model', 'first_touch'),
            'href' => Route::has('marketing.attribution.index') ? route('marketing.attribution.index') : null,
        ];
    }

    protected function emailCampaignPlaceholder(): array
    {
        return [
            'available' => false,
            'message' => __('Email campaign metrics will appear when an email provider is connected.'),
        ];
    }

    protected function attention(User $user, ?Organization $organization): Collection
    {
        $items = collect();
        if (! $organization) {
            return $items;
        }

        $summaries = $this->providerSummaries($user, $organization);
        foreach ($summaries['all'] ?? [] as $card) {
            if (in_array($card['health'] ?? null, ['unhealthy', 'degraded'], true)) {
                $items->push([
                    'title' => __(':name needs attention', ['name' => $card['name']]),
                    'subtitle' => $card['health_label'] ?? '',
                    'href' => Route::has('marketing.providers.index') ? route('marketing.providers.index') : route('integrations.index'),
                    'badge' => __('Provider'),
                ]);
            }
        }

        return $items->take(6);
    }

    protected function quickActions(User $user): array
    {
        $actions = [];

        if ($user->hasAnyPermission(['integrations.manage', 'marketing.manage']) && Route::has('marketing.campaigns.create')) {
            $actions[] = ['label' => __('Create Campaign'), 'href' => route('marketing.campaigns.create'), 'variant' => 'primary'];
        }
        if ($user->hasAnyPermission(['integrations.view', 'integrations.manage', 'marketing.view']) && Route::has('marketing.providers.index')) {
            $actions[] = ['label' => __('Connect Provider'), 'href' => route('marketing.providers.index')];
        }
        if ($user->hasAnyPermission(['integrations.view', 'marketing.view']) && Route::has('marketing.attribution.index')) {
            $actions[] = ['label' => __('View Attribution'), 'href' => route('marketing.attribution.index')];
        }
        if ($user->hasPermission('leads.view') && Route::has('leads.index')) {
            $actions[] = ['label' => __('View Lead Sources'), 'href' => route('leads.index')];
        }

        return $actions;
    }
}
