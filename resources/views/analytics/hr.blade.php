<x-app-layout>
    <x-flash-messages />

    <x-layouts.analytics
        :title="__('HR Analytics')"
        :subtitle="__('Workforce, attendance, and people metrics')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Analytics'), 'href' => route('analytics.home')],
                ['label' => __('HR'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-workspace.widget :title="__('Headcount')" :href="$payload['headcount']['href'] ?? null">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Active') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['headcount']['active'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Total') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['headcount']['total'] ?? '—' }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget :title="__('Attendance trends')" :href="$payload['attendance_trends']['href'] ?? null">
            @if (empty($payload['attendance_trends']['daily']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No attendance data.') }}</p>
            @else
                <ul class="max-h-48 space-y-2 overflow-y-auto text-sm">
                    @foreach ($payload['attendance_trends']['daily'] as $date => $statuses)
                        <li>
                            <p class="text-xs font-medium text-ink-heading">{{ $date }}</p>
                            <div class="mt-1 flex flex-wrap gap-2 text-xs text-ink-muted">
                                @foreach ($statuses as $status => $count)
                                    <span>{{ __(ucfirst($status)) }}: {{ $count }}</span>
                                @endforeach
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Leave trends')" :href="$payload['leave_trends']['href'] ?? null">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Pending') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['leave_trends']['pending'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Approved (30d)') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['leave_trends']['approved_30d'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('On leave today') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['leave_trends']['on_leave_today'] ?? '—' }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget :title="__('Recruitment funnel')" :href="$payload['recruitment_funnel']['href'] ?? null">
            <dl class="grid grid-cols-2 gap-3 text-sm mb-4">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Open roles') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['recruitment_funnel']['open_openings'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Applications') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['recruitment_funnel']['applications'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Pending offers') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['recruitment_funnel']['offers_pending'] ?? '—' }}</dd>
                </div>
            </dl>
            @if (! empty($payload['recruitment_funnel']['stages']))
                <ul class="space-y-2 text-sm">
                    @foreach ($payload['recruitment_funnel']['stages'] as $stage => $count)
                        <li class="flex justify-between gap-2">
                            <span class="text-ink-heading">{{ __(ucfirst(str_replace('_', ' ', $stage))) }}</span>
                            <span class="text-ink-muted">{{ number_format($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Performance distribution')" :href="$payload['performance_distribution']['href'] ?? null">
            @if (empty($payload['performance_distribution']['by_status']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No performance review data.') }}</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($payload['performance_distribution']['by_status'] as $status => $count)
                        <li class="flex justify-between gap-2">
                            <span class="text-ink-heading">{{ __(ucfirst(str_replace('_', ' ', $status))) }}</span>
                            <span class="text-ink-muted">{{ number_format($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Payroll summary')" :href="$payload['payroll_summary']['href'] ?? null">
            @if (empty($payload['payroll_summary']['latest_run']))
                <p class="text-sm text-ink-muted py-4 text-center">{{ __('No payroll runs yet.') }}</p>
            @else
                @php $run = $payload['payroll_summary']['latest_run']; @endphp
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Period') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ $run['period'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Status') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ __(ucfirst(str_replace('_', ' ', $run['status'] ?? ''))) }}</dd>
                    </div>
                </dl>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Attrition')" :href="$payload['attrition']['href'] ?? null">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Exits (12m)') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ $payload['attrition']['trailing_12m_exits'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Rate') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-heading">{{ isset($payload['attrition']['rate_percent']) ? $payload['attrition']['rate_percent'].'%' : '—' }}</dd>
                </div>
            </dl>
        </x-workspace.widget>

        <x-workspace.widget :title="__('Workforce capacity')">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                @foreach ($payload['workforce_capacity'] ?? [] as $key => $value)
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __(ucfirst(str_replace('_', ' ', $key))) }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ $value ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-workspace.widget>
    </x-layouts.analytics>
</x-app-layout>
