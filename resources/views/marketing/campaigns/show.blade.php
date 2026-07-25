@php
    $statusVariant = [
        'draft' => 'neutral',
        'active' => 'success',
        'paused' => 'warning',
        'completed' => 'primary',
    ];
    $audience = $campaign->audience ?? [];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$campaign->name"
        :subtitle="$campaign->description ? Str::limit($campaign->description, 120) : null"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Marketing'), 'href' => route('marketing.home')],
                ['label' => __('Campaigns'), 'href' => route('marketing.campaigns.index')],
                ['label' => $campaign->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if (auth()->user()->hasAnyPermission(['marketing.manage', 'integrations.manage']))
                <x-ui.button :href="route('marketing.campaigns.edit', $campaign)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
                <form method="POST" action="{{ route('marketing.campaigns.destroy', $campaign) }}" onsubmit="return confirm('{{ __('Delete this campaign?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endif
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge :variant="$statusVariant[$campaign->status] ?? 'neutral'">{{ $campaign->statusLabel() }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Budget')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Amount')">
                    {{ $campaign->budget_amount ? number_format((float) $campaign->budget_amount, 2).' '.($campaign->budget_currency ?? 'USD') : '—' }}
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Currency')">{{ $campaign->budget_currency ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Channels')">
            @if (! empty($campaign->channels))
                <div class="flex flex-wrap gap-2">
                    @foreach ($campaign->channels as $channel)
                        <x-ui.badge variant="primary">{{ __(ucfirst(str_replace('_', ' ', $channel))) }}</x-ui.badge>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-ink-muted">{{ __('No channels selected.') }}</p>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Performance')">
            <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Touches') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format($performance['touches'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Attributed leads') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format($performance['attributed_leads'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Conversions') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format($performance['conversions'] ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Conversion value') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((float) ($performance['conversion_value'] ?? 0), 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Cost per lead') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ isset($performance['cost_per_lead']) ? number_format($performance['cost_per_lead'], 2) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Conversion rate') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ isset($performance['conversion_rate']) ? $performance['conversion_rate'].'%' : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('ROI') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ isset($performance['roi']) ? $performance['roi'].'%' : '—' }}</dd>
                </div>
            </dl>

            @if (! empty($performance['channels']) && count($performance['channels']))
                <div class="mt-6">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('By channel') }}</h3>
                    <ul class="mt-2 space-y-2">
                        @foreach ($performance['channels'] as $row)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-ink-heading">{{ $row['channel'] }}</span>
                                <span class="text-ink-muted">{{ number_format($row['total']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($performance['landing_pages']) && count($performance['landing_pages']))
                <div class="mt-6">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Landing pages') }}</h3>
                    <ul class="mt-2 space-y-2">
                        @foreach ($performance['landing_pages'] as $row)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="min-w-0 truncate text-ink-heading">{{ $row['page'] }}</span>
                                <span class="text-ink-muted shrink-0">{{ number_format($row['total']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Audience')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Segment')">{{ $audience['segment'] ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Notes')" :span="2">{{ $audience['notes'] ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Attribution')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('UTM campaign')">{{ $campaign->utm_campaign ?? $campaign->slug ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Slug')">{{ $campaign->slug ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Timeline')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Starts')">{{ $campaign->starts_at?->format('M j, Y g:i A') ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Ends')">{{ $campaign->ends_at?->format('M j, Y g:i A') ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Created by')">{{ $campaign->creator?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Created')">{{ $campaign->created_at?->format('M j, Y g:i A') ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-slot:aside>
            @if ($campaign->description)
                <x-entity.section :title="__('Description')">
                    <p class="text-sm text-ink whitespace-pre-wrap">{{ $campaign->description }}</p>
                </x-entity.section>
            @endif

            <x-ui.button :href="route('marketing.campaigns.index')" variant="link" size="sm">← {{ __('Back to campaigns') }}</x-ui.button>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
