<x-app-layout>
    <x-flash-messages />

    <x-layouts.analytics
        :title="__('CRM Analytics')"
        :subtitle="__('Pipeline, leads, and revenue insights')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Analytics'), 'href' => route('analytics.home')],
                ['label' => __('CRM'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-workspace.widget
            :title="__('Lead sources')"
            :href="$payload['lead_sources']['href'] ?? null"
        >
            @if (empty($payload['lead_sources']['distribution']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No lead source data.') }}</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($payload['lead_sources']['distribution'] as $source => $count)
                        <li class="flex justify-between gap-2">
                            <span class="text-ink-heading">{{ $source }}</span>
                            <span class="text-ink-muted">{{ number_format($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Pipeline conversion')"
            :href="$payload['pipeline_conversion']['href'] ?? null"
        >
            @if (empty($payload['pipeline_conversion']['stages']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No pipeline data.') }}</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($payload['pipeline_conversion']['stages'] as $row)
                        <li class="flex justify-between gap-2">
                            <span class="text-ink-heading">{{ config('pipeline.stages.'.$row['stage'], $row['stage']) }}</span>
                            <span class="text-ink-muted">{{ $row['count'] }} · {{ number_format($row['value'], 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Sales performance')"
            :href="$payload['sales_performance']['href'] ?? null"
        >
            <dl class="grid grid-cols-2 gap-3 text-sm mb-4">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Conversion rate') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">
                        {{ isset($payload['sales_performance']['conversion_rate']) ? $payload['sales_performance']['conversion_rate'].'%' : '—' }}
                    </dd>
                </div>
            </dl>
            @if (($payload['sales_performance']['top_performers'] ?? collect())->isNotEmpty())
                <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Top performers') }}</h3>
                <ul class="mt-2 space-y-2 text-sm">
                    @foreach ($payload['sales_performance']['top_performers'] as $performer)
                        <li class="flex justify-between gap-2">
                            <span class="text-ink-heading">{{ $performer['name'] ?? __('Unknown') }}</span>
                            <span class="text-ink-muted">{{ $performer['count'] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Customer acquisition')"
            :href="$payload['customer_acquisition']['href'] ?? null"
        >
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Total customers') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['customer_acquisition']['total'] ?? 0)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('With lead') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['customer_acquisition']['with_lead'] ?? 0)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Recent (30d)') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['customer_acquisition']['recent_30d'] ?? 0)) }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget :title="__('Revenue forecast')">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Weighted value') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((float) ($payload['revenue_forecast']['weighted_value'] ?? 0), 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Open pipeline') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((float) ($payload['revenue_forecast']['open_pipeline_value'] ?? 0), 0) }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Win / loss analysis')"
            :href="$payload['win_loss']['href'] ?? null"
        >
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Won') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['win_loss']['won'] ?? 0)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Lost') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['win_loss']['lost'] ?? 0)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Win rate') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ isset($payload['win_loss']['win_rate']) ? $payload['win_loss']['win_rate'].'%' : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Won value') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((float) ($payload['win_loss']['won_value'] ?? 0), 0) }}</dd>
                </div>
            </dl>
        </x-workspace.widget>
    </x-layouts.analytics>
</x-app-layout>
