<?php

namespace App\Services\Marketing;

use App\Models\MarketingAttribution;
use App\Models\MarketingCampaign;
use App\Models\MarketingConversion;
use App\Models\MarketingProviderSyncRun;
use App\Models\MarketingTouch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MarketingCampaignService
{
    /** @return Collection<int, MarketingCampaign> */
    public function list(Organization $organization, ?string $status = null): Collection
    {
        $query = MarketingCampaign::query()
            ->where('organization_id', $organization->id)
            ->with('creator')
            ->latest();

        if ($status && in_array($status, MarketingCampaign::STATUSES, true)) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function create(Organization $organization, User $actor, array $data): MarketingCampaign
    {
        return MarketingCampaign::query()->create([
            'organization_id' => $organization->id,
            'created_by' => $actor->id,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'status' => $data['status'] ?? MarketingCampaign::STATUS_DRAFT,
            'description' => $data['description'] ?? null,
            'budget_amount' => $data['budget_amount'] ?? null,
            'budget_currency' => $data['budget_currency'] ?? ($organization->currency ?? 'USD'),
            'channels' => $data['channels'] ?? [],
            'audience' => $data['audience'] ?? [],
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);
    }

    public function update(MarketingCampaign $campaign, array $data): MarketingCampaign
    {
        $campaign->fill([
            'name' => $data['name'] ?? $campaign->name,
            'slug' => $data['slug'] ?? $campaign->slug,
            'status' => $data['status'] ?? $campaign->status,
            'description' => array_key_exists('description', $data) ? $data['description'] : $campaign->description,
            'budget_amount' => array_key_exists('budget_amount', $data) ? $data['budget_amount'] : $campaign->budget_amount,
            'budget_currency' => $data['budget_currency'] ?? $campaign->budget_currency,
            'channels' => array_key_exists('channels', $data) ? $data['channels'] : $campaign->channels,
            'audience' => array_key_exists('audience', $data) ? $data['audience'] : $campaign->audience,
            'utm_campaign' => array_key_exists('utm_campaign', $data) ? $data['utm_campaign'] : $campaign->utm_campaign,
            'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $campaign->starts_at,
            'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $campaign->ends_at,
        ]);
        $campaign->save();

        return $campaign->refresh();
    }

    /** @return array<string, mixed> */
    public function performance(MarketingCampaign $campaign): array
    {
        $utm = $campaign->utm_campaign ?: $campaign->slug;
        $touches = 0;
        $channels = collect();
        $landingPages = collect();

        if (Schema::hasTable('marketing_touches') && Schema::hasTable('marketing_sessions') && Schema::hasTable('marketing_visitors')) {
            $base = MarketingTouch::query()
                ->where('campaign', $utm)
                ->whereHas('session.visitor', fn ($q) => $q->where('organization_id', $campaign->organization_id));

            $touches = (clone $base)->count();
            $channels = (clone $base)
                ->select('channel', DB::raw('count(*) as total'))
                ->groupBy('channel')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'channel' => $row->channel ?: __('Unknown'),
                    'total' => (int) $row->total,
                ]);
            $landingPages = (clone $base)
                ->select('landing_page', DB::raw('count(*) as total'))
                ->whereNotNull('landing_page')
                ->groupBy('landing_page')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'page' => $row->landing_page,
                    'total' => (int) $row->total,
                ]);
        }

        $attributedLeads = 0;
        if (Schema::hasTable('marketing_attributions')) {
            $attributedLeads = MarketingAttribution::query()
                ->where('organization_id', $campaign->organization_id)
                ->whereNotNull('lead_id')
                ->whereHas('session.touchpoints', fn ($q) => $q->where('campaign', $utm))
                ->count();
        }

        $conversionValue = 0.0;
        $conversionCount = 0;
        if (Schema::hasTable('marketing_conversions')) {
            $conversions = MarketingConversion::query()
                ->where('organization_id', $campaign->organization_id)
                ->whereHas('attribution.session.touchpoints', fn ($q) => $q->where('campaign', $utm))
                ->get(['event_value', 'event_name']);
            $conversionCount = $conversions->count();
            $conversionValue = (float) $conversions->sum('event_value');
        }

        $budget = (float) ($campaign->budget_amount ?? 0);
        $cpl = $attributedLeads > 0 && $budget > 0 ? round($budget / $attributedLeads, 2) : null;
        $roi = $budget > 0 ? round((($conversionValue - $budget) / $budget) * 100, 1) : null;
        $conversionRate = $attributedLeads > 0
            ? round(($conversionCount / $attributedLeads) * 100, 1)
            : null;

        return [
            'touches' => $touches,
            'attributed_leads' => $attributedLeads,
            'conversions' => $conversionCount,
            'conversion_value' => $conversionValue,
            'cost_per_lead' => $cpl,
            'conversion_rate' => $conversionRate,
            'roi' => $roi,
            'channels' => $channels,
            'landing_pages' => $landingPages,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function recentActivity(Organization $organization, int $limit = 10): Collection
    {
        $items = collect();

        if (Schema::hasTable('marketing_campaigns')) {
            MarketingCampaign::query()
                ->where('organization_id', $organization->id)
                ->latest('updated_at')
                ->limit($limit)
                ->get()
                ->each(function (MarketingCampaign $campaign) use ($items) {
                    $items->push([
                        'title' => $campaign->name,
                        'subtitle' => $campaign->statusLabel(),
                        'when' => $campaign->updated_at?->diffForHumans(),
                        'href' => route('marketing.campaigns.show', $campaign),
                        'badge' => __('Campaign'),
                    ]);
                });
        }

        if (Schema::hasTable('marketing_provider_sync_runs')) {
            MarketingProviderSyncRun::query()
                ->where('organization_id', $organization->id)
                ->latest()
                ->limit($limit)
                ->with('provider')
                ->get()
                ->each(function (MarketingProviderSyncRun $run) use ($items) {
                    $items->push([
                        'title' => __(':provider sync', ['provider' => $run->provider?->display_name ?? __('Provider')]),
                        'subtitle' => ucfirst((string) $run->status),
                        'when' => $run->created_at?->diffForHumans(),
                        'href' => route('marketing.providers.index'),
                        'badge' => __('Sync'),
                    ]);
                });
        }

        return $items->sortByDesc('when')->take($limit)->values();
    }
}
