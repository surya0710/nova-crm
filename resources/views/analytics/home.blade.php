@php
    $hidden = collect($widgetLayout['hidden'] ?? []);
    $isVisible = fn (string $key) => ! $hidden->contains($key);
    $domainWidgetKeys = [
        'sales' => 'domain_sales',
        'delivery' => 'domain_delivery',
        'people' => 'domain_people',
        'finance' => 'domain_finance',
        'audit' => 'domain_audit',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('Reports Workspace')"
        :subtitle="__('Cross-module insights and executive metrics')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Reports Workspace'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <div
                class="relative flex flex-wrap items-center gap-2"
                x-data="{
                    panelOpen: false,
                    saving: false,
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
                        try {
                            const response = await fetch(@js(route('workspace.dashboard-preferences.update')), {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                },
                                body: JSON.stringify({
                                    workspace: 'analytics',
                                    widgets: this.widgets,
                                    hidden: this.hidden,
                                }),
                            });
                            if (response.ok) {
                                window.location.reload();
                            }
                        } finally {
                            this.saving = false;
                        }
                    },
                }"
            >
                <x-ui.button type="button" variant="secondary" size="sm" x-on:click="panelOpen = !panelOpen" x-bind:aria-expanded="panelOpen.toString()">
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
                    <p class="mt-1 text-xs text-ink-muted">{{ __('Choose which widgets appear on your analytics home.') }}</p>
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
                <x-ui.stat-card :label="__('Analytics')" :value="__('—')" :hint="__('No metrics available for your role')" />
            @endforelse
        </x-slot:kpis>

        <div class="space-y-6">
            @if ($domainCards && collect($domainCards)->filter(fn ($card) => $isVisible($domainWidgetKeys[$card['key']] ?? ''))->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-2">
                    @foreach ($domainCards as $card)
                        @php $widgetKey = $domainWidgetKeys[$card['key']] ?? null; @endphp
                        @if ($widgetKey && $isVisible($widgetKey))
                            <x-workspace.widget
                                :title="$card['label']"
                                :subtitle="__('Domain analytics')"
                                :href="$card['href'] ?? null"
                            >
                                <dl class="grid grid-cols-3 gap-3 text-sm">
                                    @foreach ($card['metrics'] ?? [] as $metric)
                                        <div>
                                            <dt class="text-xs text-ink-muted">{{ $metric['label'] }}</dt>
                                            <dd class="mt-1 font-semibold text-ink-heading">{{ $metric['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </x-workspace.widget>
                        @endif
                    @endforeach
                </div>
            @elseif ($domainCards === [])
                <x-ui.empty-state-preset variant="analytics" class="!py-6" />
            @endif

            @if ($isVisible('recent_activity'))
                <x-workspace.widget
                    :title="__('Recent activity')"
                    :href="auth()->user()->hasPermission('audit.view') && \Illuminate\Support\Facades\Route::has('audit-logs.index') ? route('audit-logs.index') : null"
                >
                    @forelse ($recentActivity as $item)
                        <a href="{{ $item['href'] ?? '#' }}" class="flex items-start justify-between gap-3 py-2.5 border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-heading truncate hover:text-primary-700">{{ $item['title'] }}</p>
                                <p class="text-xs text-ink-muted">{{ $item['subtitle'] ?? '' }}</p>
                            </div>
                            <span class="text-xs text-ink-muted shrink-0">{{ $item['when'] ?? '' }}</span>
                        </a>
                    @empty
                        <x-ui.empty-state-preset variant="analytics" class="!py-6" />
                    @endforelse
                </x-workspace.widget>
            @endif
        </div>

        <x-slot:aside>
            @if ($isVisible('attention'))
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
            @endif
        </x-slot:aside>
    </x-layouts.workspace-home>
</x-app-layout>
