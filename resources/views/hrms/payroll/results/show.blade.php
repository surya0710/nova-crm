<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Payroll Result')"
        :subtitle="$result->employee?->full_name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Results'), 'href' => route('hrms.payroll.results.index')],
                ['label' => $result->employee?->full_name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.payroll.runs.show', $result->payroll_run_id)" variant="secondary" size="sm">{{ __('Back to run') }}</x-ui.button>
        </x-slot:actions>

        <x-entity.section :title="__('Summary')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Period')">{{ $result->payrollRun?->period?->name }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Gross')">{{ number_format((float) $result->gross_salary, 2) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Deductions')">{{ number_format((float) $result->total_deductions, 2) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Net')">{{ number_format((float) $result->net_salary, 2) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Working Days')">{{ $result->working_days }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Payable Days')">{{ $result->payable_days }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Overtime (min)')">{{ $result->overtime_minutes }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Overtime Amount')">{{ number_format((float) $result->overtime_amount, 2) }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Calculation Metadata')">
            <p class="mb-1 text-sm text-ink-muted">{{ __('Calculation Hash') }}</p>
            <p class="break-all font-mono text-xs text-ink-heading">{{ $result->calculation_hash }}</p>
            <p class="mb-1 mt-3 text-sm text-ink-muted">{{ __('Version') }}</p>
            <p class="text-sm text-ink-heading">{{ $result->version }} · {{ $result->snapshot['engine_version'] ?? '—' }}</p>
        </x-entity.section>

        <div class="grid gap-6 md:grid-cols-2">
            <x-entity.section :title="__('Earnings')">
                @if (empty($result->snapshot['earnings']))
                    <p class="text-sm text-ink-muted">{{ __('None') }}</p>
                @else
                    <div class="space-y-2 text-sm">
                        @foreach (($result->snapshot['earnings'] ?? []) as $line)
                            <div class="flex justify-between border-b border-line pb-2 last:border-0">
                                <span class="text-ink-muted">{{ $line['code'] ?? '—' }}</span>
                                <span class="text-ink-heading">{{ number_format((float) ($line['amount'] ?? 0), 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-entity.section>

            <x-entity.section :title="__('Deductions')">
                @forelse (($result->snapshot['deductions'] ?? []) as $line)
                    <div class="flex justify-between border-b border-line pb-2 text-sm last:border-0">
                        <span class="text-ink-muted">{{ $line['code'] ?? '—' }} ({{ $line['status'] ?? '' }})</span>
                        <span class="text-ink-heading">{{ number_format((float) ($line['amount'] ?? 0), 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('No deductions') }}</p>
                @endforelse
            </x-entity.section>
        </div>
    </x-layouts.entity-detail>
</x-app-layout>
