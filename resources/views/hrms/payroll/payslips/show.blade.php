<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Payslip')"
        :subtitle="$payslip->payslip_number"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Payslips'), 'href' => route('hrms.payroll.payslips.index')],
                ['label' => $payslip->payslip_number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('download', $payslip)
                <x-ui.button :href="route('hrms.payroll.payslips.download', $payslip)" variant="primary" size="sm">{{ __('Download PDF') }}</x-ui.button>
            @endcan
            @can('email', $payslip)
                <form method="POST" action="{{ route('hrms.payroll.payslips.email', $payslip) }}">@csrf
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Resend Email') }}</x-ui.button>
                </form>
            @endcan
            <x-ui.button :href="route('hrms.payroll.payslips.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
        </x-slot:actions>

        <x-entity.section :title="__('Summary')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Employee')">{{ $payslip->employee?->full_name }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Period')">{{ $payslip->payrollRun?->period?->name }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Net Salary')">{{ number_format((float) $payslip->net_salary, 2) }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Generated')">{{ $payslip->generated_at?->toDateTimeString() }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <div class="grid gap-4 lg:grid-cols-3">
            @foreach ([
                ['title' => __('Earnings'), 'lines' => $earnings],
                ['title' => __('Deductions'), 'lines' => $deductions],
                ['title' => __('Employer Contributions'), 'lines' => $employerContributions],
            ] as $section)
                <x-entity.section :title="$section['title']">
                    @forelse ($section['lines'] as $line)
                        <div class="flex justify-between border-b border-line py-2 text-sm last:border-0">
                            <span class="text-ink-muted">{{ $line['name'] ?? $line['code'] }}</span>
                            <span class="text-ink-heading">{{ number_format((float) ($line['amount'] ?? 0), 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted">{{ __('None') }}</p>
                    @endforelse
                </x-entity.section>
            @endforeach
        </div>
    </x-layouts.entity-detail>
</x-app-layout>
