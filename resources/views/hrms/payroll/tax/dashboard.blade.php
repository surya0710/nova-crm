<x-app-layout>
    <x-flash-messages />

    <x-layouts.dashboard :title="__('Income Tax')" :subtitle="__('TDS, declarations, proofs, and Form 16')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Income Tax'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:kpis>
            <a href="{{ route('hrms.payroll.tax.declarations.index') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                <p class="text-sm text-ink-muted">{{ __('Pending Declarations') }}</p>
                <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $dashboard['pending_declarations'] ?? 0 }}</p>
            </a>
            <a href="{{ route('hrms.payroll.tax.proofs.index') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                <p class="text-sm text-ink-muted">{{ __('Proofs to Verify') }}</p>
                <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $dashboard['pending_proof_verification'] ?? 0 }}</p>
            </a>
            <div class="rounded-xl border border-line bg-surface-card p-4 shadow-sm">
                <p class="text-sm text-ink-muted">{{ __('Monthly TDS') }}</p>
                <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ number_format((float) ($dashboard['monthly_tds'] ?? 0), 2) }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface-card p-4 shadow-sm">
                <p class="text-sm text-ink-muted">{{ __('Annual Tax Liability') }}</p>
                <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ number_format((float) ($dashboard['annual_tax_liability'] ?? 0), 2) }}</p>
            </div>
        </x-slot:kpis>

        <div class="md:col-span-2 xl:col-span-3">
            <x-ui.card>
                <p class="text-sm text-ink-muted">
                    {{ __('Financial Year') }}:
                    <span class="font-medium text-ink-heading">{{ $dashboard['financial_year']['label'] ?? __('Not configured') }}</span>
                    @if (! empty($dashboard['financial_year']['code']))
                        <span class="text-ink-muted">({{ $dashboard['financial_year']['code'] }})</span>
                    @endif
                </p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('Employees without regime selection') }}: {{ $dashboard['employees_without_regime'] ?? 0 }}</p>
                <div class="mt-3 flex flex-wrap gap-3">
                    <x-ui.button :href="route('hrms.payroll.tax.financial-years.index')" variant="link" size="sm">{{ __('Financial Years') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.tax.regimes.index')" variant="link" size="sm">{{ __('Regimes') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.tax.declarations.index')" variant="link" size="sm">{{ __('Declarations') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.tax.proofs.index')" variant="link" size="sm">{{ __('Proofs') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.tax.projections.index')" variant="link" size="sm">{{ __('Projections') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.tax.form16.index')" variant="link" size="sm">{{ __('Form 16') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.tax.reports.index')" variant="link" size="sm">{{ __('Reports') }}</x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </x-layouts.dashboard>
</x-app-layout>
