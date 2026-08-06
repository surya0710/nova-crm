<x-app-layout>
    <x-flash-messages />

    <x-layouts.dashboard :title="__('Statutory Compliance')" :subtitle="__('Versioned rule packs for EPF, ESI, PT, and TDS')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Statutory Compliance'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:kpis>
            <a href="{{ route('hrms.payroll.statutory.rules') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                <p class="text-sm text-ink-muted">{{ __('Rule Sets') }}</p>
                <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $rule_set_count }}</p>
            </a>
            <a href="{{ route('hrms.payroll.statutory.profiles') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                <p class="text-sm text-ink-muted">{{ __('Employee Profiles') }}</p>
                <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $profile_count }}</p>
            </a>
            <a href="{{ route('hrms.payroll.statutory.validation') }}" class="rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-300">
                <p class="text-sm text-ink-muted">{{ __('Compliance Errors') }}</p>
                <p class="mt-1 text-2xl font-semibold text-ink-heading">{{ $compliance_error_count }}</p>
            </a>
            <div class="rounded-xl border border-line bg-surface-card p-4 shadow-sm">
                <p class="text-sm text-ink-muted">{{ __('Active Rule Set') }}</p>
                <p class="mt-1 text-lg font-semibold text-ink-heading">{{ $active_rule_set_name ?: __('None') }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $active_rule_set_jurisdiction }}</p>
            </div>
        </x-slot:kpis>

        <div class="md:col-span-2 xl:col-span-3">
            <x-ui.card>
                <p class="text-sm text-ink-muted">{{ __('Statutory Compliance Engine calculates EPF, ESI, Professional Tax, and prepares TDS using versioned, organization-scoped rule packs.') }}</p>
                <div class="mt-3 flex flex-wrap gap-3">
                    <x-ui.button :href="route('hrms.payroll.statutory.profiles')" variant="link" size="sm">{{ __('Profiles') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.statutory.rules')" variant="link" size="sm">{{ __('Rules') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.statutory.validation')" variant="link" size="sm">{{ __('Compliance') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.payroll.tax.index')" variant="link" size="sm">{{ __('Income Tax') }}</x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </x-layouts.dashboard>
</x-app-layout>
