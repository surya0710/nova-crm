@php
    $hidden = collect($widgetLayout['hidden'] ?? []);
    $isVisible = fn (string $key) => ! $hidden->contains($key);
    $statusVariant = [
        'draft' => 'neutral',
        'active' => 'success',
        'paused' => 'warning',
        'completed' => 'primary',
    ];
    $healthVariant = fn (?string $health) => match ($health) {
        'healthy' => 'success',
        'degraded' => 'warning',
        'unhealthy' => 'danger',
        'disconnected' => 'neutral',
        default => 'neutral',
    };
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('Marketing')"
        :subtitle="__('Campaigns, attribution, and channel performance')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Marketing'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <div
                class="relative flex flex-wrap items-center gap-2"
                x-data="{
                    panelOpen: false,
                    saving: false,
                    saved: false,
                    widgets: @js(array_values($widgetLayout['widgets'] ?? [])),
                    hidden: @js(array_values($widgetLayout['hidden'] ?? [])),
                    available: @js($availableWidgets),
                    isChecked(key) { return ! this.hidden.includes(key); },
                    toggle(key) {
                        if (this.hidden.includes(key)) {
                            this.hidden = this.hidden.filter(k => k !== key);
                        } else {
                            this.hidden.push(key);
                        }
                    },
                    async saveLayout() {
                        this.saving = true;
                        this.saved = false;
                        try {
                            const response = await fetch(@js(route('workspace.dashboard-preferences.update')), {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                },
                                body: JSON.stringify({
                                    workspace: 'marketing',
                                    widgets: this.widgets,
                                    hidden: this.hidden,
                                }),
                            });
                            if (response.ok) {
                                this.saved = true;
                                window.location.reload();
                            }
                        } finally {
                            this.saving = false;
                        }
                    },
                }"
            >
                <x-ui.button type="button" variant="secondary" size="sm" x-on:click="panelOpen = !panelOpen" aria-expanded="false" x-bind:aria-expanded="panelOpen.toString()">
                    {{ __('Customize') }}
                </x-ui.button>
                <x-workspace.quick-actions :actions="$quickActions" />

                <div
                    x-show="panelOpen"
                    x-cloak
                    x-on:click.outside="panelOpen = false"
                    class="absolute right-4 top-20 z-30 w-full max-w-sm rounded-xl border border-line bg-surface-card p-4 shadow-lg sm:right-8"
                    role="dialog"
                    aria-label="{{ __('Customize dashboard widgets') }}"
                >
                    <h2 class="text-sm font-semibold text-ink-heading">{{ __('Dashboard widgets') }}</h2>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('Choose which widgets appear on your marketing home.') }}</p>
                    <ul class="mt-3 max-h-64 space-y-2 overflow-y-auto">
                        <template x-for="item in available" :key="item.key">
                            <li>
                                <label class="flex items-center gap-2 text-sm text-ink">
                                    <input
                                        type="checkbox"
                                        class="rounded border-line text-primary-600 focus:ring-primary-500"
                                        x-bind:checked="isChecked(item.key)"
                                        x-on:change="toggle(item.key)"
                                    >
                                    <span x-text="item.label"></span>
                                </label>
                            </li>
                        </template>
                    </ul>
                    <div class="mt-4 flex items-center gap-2">
                        <x-ui.button type="button" variant="primary" size="sm" x-on:click="saveLayout()" x-bind:disabled="saving">
                            <span x-show="!saving">{{ __('Save layout') }}</span>
                            <span x-show="saving" x-cloak>{{ __('Saving…') }}</span>
                        </x-ui.button>
                        <x-ui.button type="button" variant="ghost" size="sm" x-on:click="panelOpen = false">{{ __('Close') }}</x-ui.button>
                    </div>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:kpis>
            @forelse ($kpis as $kpi)
                @if (($kpi['key'] ?? null) === null || $isVisible($kpi['key']))
                    <x-ui.stat-card
                        :label="$kpi['label']"
                        :value="$kpi['value']"
                        :hint="$kpi['hint'] ?? null"
                    />
                @endif
            @empty
                <x-ui.stat-card :label="__('Marketing')" :value="__('—')" :hint="__('No metrics available for your role')" />
            @endforelse
        </x-slot:kpis>

        <div class="space-y-6">
            @if ($isVisible('active_campaigns'))
                <x-workspace.widget
                    :title="__('Active campaigns')"
                    :subtitle="__('Currently running')"
                    :href="\Illuminate\Support\Facades\Route::has('marketing.campaigns.index') ? route('marketing.campaigns.index', ['status' => 'active']) : null"
                >
                    @if ($activeCampaigns->isEmpty())
                        <x-ui.empty-state-preset
                            variant="campaigns"
                            :action-href="auth()->user()->hasAnyPermission(['marketing.manage', 'integrations.manage']) && \Illuminate\Support\Facades\Route::has('marketing.campaigns.create') ? route('marketing.campaigns.create') : null"
                            class="!py-6"
                        />
                    @else
                        <ul class="divide-y divide-line -mx-1">
                            @foreach ($activeCampaigns as $campaign)
                                <li class="py-2.5 flex items-center justify-between gap-3">
                                    <a href="{{ route('marketing.campaigns.show', $campaign) }}" class="min-w-0 text-sm font-medium text-ink-heading hover:text-primary-700 truncate">{{ $campaign->name }}</a>
                                    <x-ui.badge :variant="$statusVariant[$campaign->status] ?? 'neutral'">{{ $campaign->statusLabel() }}</x-ui.badge>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-workspace.widget>
            @endif

            @if ($isVisible('channel_performance'))
                <x-workspace.widget
                    :title="__('Channel performance')"
                    :subtitle="__('Touchpoints by channel')"
                    :href="\Illuminate\Support\Facades\Route::has('marketing.attribution.index') ? route('marketing.attribution.index') : null"
                >
                    @if ($channelPerformance->isEmpty())
                        <x-ui.empty-state-preset variant="attribution" class="!py-6" />
                    @else
                        <ul class="space-y-3">
                            @foreach ($channelPerformance as $row)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-ink-heading">{{ $row['channel'] }}</span>
                                    <span class="text-ink-muted">{{ number_format($row['total']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-workspace.widget>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                @if ($isVisible('google_ads'))
                    <x-workspace.widget
                        :title="__('Google Ads summary')"
                        :subtitle="__('Provider connection')"
                        :href="\Illuminate\Support\Facades\Route::has('marketing.providers.index') ? route('marketing.providers.index') : null"
                    >
                        @if (empty($providerSummaries['google_ads']))
                            <x-ui.empty-state-preset variant="providers" class="!py-4" />
                        @else
                            @php $google = $providerSummaries['google_ads']; @endphp
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-ink-heading">{{ $google['name'] ?? __('Google Ads') }}</span>
                                    <x-ui.badge :variant="$healthVariant($google['health'] ?? null)">{{ $google['health_label'] ?? __('Unknown') }}</x-ui.badge>
                                </div>
                                <p class="text-xs text-ink-muted">{{ $google['status_label'] ?? ($google['status'] ?? '—') }}</p>
                            </div>
                        @endif
                    </x-workspace.widget>
                @endif

                @if ($isVisible('meta_ads'))
                    <x-workspace.widget
                        :title="__('Meta Ads summary')"
                        :subtitle="__('Provider connection')"
                        :href="\Illuminate\Support\Facades\Route::has('marketing.providers.index') ? route('marketing.providers.index') : null"
                    >
                        @if (empty($providerSummaries['meta_ads']))
                            <x-ui.empty-state-preset variant="providers" class="!py-4" />
                        @else
                            @php $meta = $providerSummaries['meta_ads']; @endphp
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-ink-heading">{{ $meta['name'] ?? __('Meta Ads') }}</span>
                                    <x-ui.badge :variant="$healthVariant($meta['health'] ?? null)">{{ $meta['health_label'] ?? __('Unknown') }}</x-ui.badge>
                                </div>
                                <p class="text-xs text-ink-muted">{{ $meta['status_label'] ?? ($meta['status'] ?? '—') }}</p>
                            </div>
                        @endif
                    </x-workspace.widget>
                @endif
            </div>

            @if ($isVisible('email_performance'))
                <x-workspace.widget :title="__('Email campaign performance')" :subtitle="__('Email channel metrics')">
                    @if (! ($emailPerformance['available'] ?? false))
                        <p class="text-sm text-ink-muted py-4 text-center">{{ $emailPerformance['message'] ?? __('Email metrics are not available yet.') }}</p>
                    @else
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('Email performance data will appear here.') }}</p>
                    @endif
                </x-workspace.widget>
            @endif

            @if ($isVisible('landing_pages'))
                <x-workspace.widget
                    :title="__('Landing page performance')"
                    :subtitle="__('Top entry pages')"
                    :href="\Illuminate\Support\Facades\Route::has('marketing.attribution.index') ? route('marketing.attribution.index') : null"
                >
                    @if ($landingPages->isEmpty())
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No landing page data yet.') }}</p>
                    @else
                        <ul class="divide-y divide-line -mx-1">
                            @foreach ($landingPages as $row)
                                <li class="py-2.5 flex items-center justify-between gap-3 text-sm">
                                    <span class="min-w-0 truncate font-medium text-ink-heading">{{ $row['page'] }}</span>
                                    <span class="text-ink-muted shrink-0">{{ number_format($row['total']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-workspace.widget>
            @endif

            @if ($isVisible('attribution'))
                <x-workspace.widget
                    :title="__('Attribution overview')"
                    :subtitle="__('Leads linked to marketing touchpoints')"
                    :href="$attribution['href'] ?? null"
                >
                    @if (($attribution['total'] ?? 0) === 0)
                        <x-ui.empty-state-preset variant="attribution" class="!py-6" />
                    @else
                        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-sm">
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Total attributions') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ number_format($attribution['total']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('With lead') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ number_format($attribution['with_lead'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Model') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ __(ucfirst(str_replace('_', ' ', $attribution['model'] ?? 'first_touch'))) }}</dd>
                            </div>
                        </dl>
                    @endif
                </x-workspace.widget>
            @endif

            @if ($isVisible('recent_activity'))
                <x-workspace.widget :title="__('Recent campaign activity')">
                    @forelse ($recentActivity as $item)
                        <a href="{{ $item['href'] ?? '#' }}" class="flex items-start justify-between gap-3 py-2.5 border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-heading truncate hover:text-primary-700">{{ $item['title'] }}</p>
                                <p class="text-xs text-ink-muted">{{ $item['subtitle'] ?? '' }}</p>
                            </div>
                            <span class="text-xs text-ink-muted shrink-0">{{ $item['when'] ?? '' }}</span>
                        </a>
                    @empty
                        <x-ui.empty-state-preset variant="campaigns" class="!py-6" />
                    @endforelse
                </x-workspace.widget>
            @endif
        </div>

        <x-slot:aside>
            <x-workspace.attention-rail :title="__('Needs attention')">
                @forelse ($attention as $item)
                    <x-workspace.attention-item
                        :href="$item['href'] ?? null"
                        :title="$item['title']"
                        :subtitle="$item['subtitle'] ?? null"
                        :badge="$item['badge'] ?? null"
                    />
                @empty
                    {{-- empty slot handled by rail --}}
                @endforelse
            </x-workspace.attention-rail>
        </x-slot:aside>
    </x-layouts.workspace-home>
</x-app-layout>
