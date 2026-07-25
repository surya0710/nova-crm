<x-app-layout>
    <x-flash-messages />

    <x-layouts.analytics
        :title="__('Project Analytics')"
        :subtitle="__('Delivery health, resources, and forecasts')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Analytics'), 'href' => route('analytics.home')],
                ['label' => __('Projects'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-workspace.widget :title="__('Progress')" :href="$payload['href'] ?? null">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Average completion') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ ($payload['progress']['average_completion_percentage'] ?? 0).'%' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Active projects') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['progress']['active_project_count'] ?? '—' }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget :title="__('Resource utilization')" :href="$payload['resource_utilization']['href'] ?? null">
            @if (! ($payload['resource_utilization']['available'] ?? false))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('Resource allocation data is not available.') }}</p>
            @else
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Average allocation') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ ($payload['resource_utilization']['average_allocation'] ?? 0).'%' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Over-allocated') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['resource_utilization']['over_allocated'] ?? 0 }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Allocated employees') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['resource_utilization']['allocated_employees'] ?? '—' }}</dd>
                    </div>
                </dl>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Budget vs actual')">
            @if (empty($payload['budget_vs_actual']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No budget data.') }}</p>
            @else
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    @foreach ($payload['budget_vs_actual'] as $key => $value)
                        @if (! is_array($value))
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __(ucfirst(str_replace('_', ' ', $key))) }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ is_numeric($value) ? number_format((float) $value, is_float($value) ? 1 : 0) : $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Upcoming milestones')">
            @if (empty($payload['milestones']['upcoming']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No upcoming milestones.') }}</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($payload['milestones']['upcoming'] as $milestone)
                        <li class="flex justify-between gap-2">
                            <span class="min-w-0 truncate text-ink-heading">{{ $milestone['title'] ?? $milestone['name'] ?? __('Milestone') }}</span>
                            <span class="text-ink-muted shrink-0">{{ $milestone['due_date'] ?? '—' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Portfolio health')">
            @php $summary = $payload['portfolio_health']['summary'] ?? []; @endphp
            @if (empty($summary))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No portfolio summary.') }}</p>
            @else
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    @foreach ($summary as $key => $value)
                        @if (! is_array($value))
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __(ucfirst(str_replace('_', ' ', $key))) }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Delivery trends')">
            @if (empty($payload['delivery_trends']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No delivery trend data.') }}</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($payload['delivery_trends'] as $trend)
                        <li class="flex items-center justify-between gap-2">
                            <span class="min-w-0 truncate text-ink-heading">{{ $trend['name'] ?? __('Project') }}</span>
                            @if ($trend['likely_delay'] ?? false)
                                <x-ui.badge variant="warning">{{ __('Likely delay') }}</x-ui.badge>
                            @else
                                <span class="text-xs text-ink-muted">{{ $trend['estimated_completion'] ?? '—' }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Delivery forecast')">
            @if (empty($payload['delivery_forecast']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No forecast data.') }}</p>
            @else
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    @foreach ($payload['delivery_forecast'] as $key => $value)
                        @if (! is_array($value))
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __(ucfirst(str_replace('_', ' ', $key))) }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Risk overview')">
            @if (empty($payload['risk_overview']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No risk overview data.') }}</p>
            @else
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    @foreach ($payload['risk_overview'] as $key => $value)
                        @if (! is_array($value))
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __(ucfirst(str_replace('_', ' ', $key))) }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            @endif
        </x-workspace.widget>
    </x-layouts.analytics>
</x-app-layout>
