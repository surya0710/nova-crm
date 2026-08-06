<x-app-layout>
    <x-flash-messages />

    <x-layouts.analytics
        :title="__('Executive Dashboard')"
        :subtitle="__('Organization-wide performance snapshot')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Analytics'), 'href' => route('analytics.home')],
                ['label' => __('Executive'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-workspace.widget
            :title="__('Revenue')"
            :href="$payload['revenue']['href'] ?? null"
        >
            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Collected') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((float) ($payload['revenue']['collected'] ?? 0), 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Outstanding') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((float) ($payload['revenue']['outstanding'] ?? 0), 0) }}</dd>
                </div>
            </dl>
            @if (($payload['revenue']['monthly'] ?? collect())->isNotEmpty())
                <ul class="mt-4 space-y-1 text-xs text-ink-muted">
                    @foreach ($payload['revenue']['monthly'] as $month)
                        <li class="flex justify-between gap-2">
                            <span>{{ $month['label'] ?? '' }}</span>
                            <span>{{ number_format((float) ($month['total'] ?? 0), 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Sales pipeline')"
            :href="$payload['sales_pipeline']['href'] ?? null"
        >
            <p class="text-2xl font-semibold text-ink-heading">{{ number_format((float) ($payload['sales_pipeline']['open_value'] ?? 0), 0) }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ __('Open pipeline value') }}</p>
            @if (($payload['sales_pipeline']['by_stage'] ?? collect())->isNotEmpty())
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($payload['sales_pipeline']['by_stage'] as $stage => $row)
                        <li class="flex justify-between gap-2">
                            <span class="text-ink-heading">{{ config('pipeline.stages.'.$stage, $stage) }}</span>
                            <span class="text-ink-muted">{{ $row->count ?? 0 }} · {{ number_format((float) ($row->value ?? 0), 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Lead funnel')"
            :href="$payload['lead_funnel']['href'] ?? null"
        >
            <p class="text-2xl font-semibold text-ink-heading">{{ number_format((int) ($payload['lead_funnel']['total'] ?? 0)) }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ __('Total leads by status') }}</p>
            @if (! empty($payload['lead_funnel']['counts']))
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($payload['lead_funnel']['counts'] as $status => $count)
                        <li class="flex justify-between gap-2">
                            <span class="text-ink-heading">{{ config('leads.statuses.'.$status, ucfirst($status)) }}</span>
                            <span class="text-ink-muted">{{ number_format($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Customer growth')"
            :href="$payload['customer_growth']['href'] ?? null"
        >
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Total') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['customer_growth']['total'] ?? 0)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Last 30 days') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['customer_growth']['recent_30d'] ?? 0)) }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Employee growth')"
            :href="$payload['employee_growth']['href'] ?? null"
        >
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Active') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['employee_growth']['active'] ?? 0)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('New joiners (30d)') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['employee_growth']['new_joiners_30d'] ?? 0)) }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Project health')"
            :href="$payload['project_health']['href'] ?? null"
        >
            @php $projectKpis = $payload['project_health']['kpis'] ?? []; @endphp
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Active projects') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $projectKpis['active_projects'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('At risk') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $projectKpis['at_risk_count'] ?? '—' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-xs text-ink-muted">{{ __('Avg completion') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ ($projectKpis['average_completion_percentage'] ?? 0).'%' }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Recruitment metrics')"
            :href="$payload['recruitment_metrics']['href'] ?? null"
        >
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Open roles') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['recruitment_metrics']['open_openings'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Applications') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['recruitment_metrics']['applications'] ?? '—' }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget
            :title="__('Financial KPIs')"
            :href="$payload['financial_kpis']['href'] ?? null"
        >
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Outstanding AR') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((float) ($payload['financial_kpis']['outstanding_ar'] ?? 0), 0) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Outstanding count') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ number_format((int) ($payload['financial_kpis']['outstanding_count'] ?? 0)) }}</dd>
                </div>
            </dl>
        </x-workspace.widget>
    </x-layouts.analytics>
</x-app-layout>
