<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Rule Version')"
        :subtitle="$ruleSet->name . ' / ' . $version->version"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Statutory Compliance'), 'href' => route('hrms.payroll.statutory.index')],
                ['label' => __('Rule Sets'), 'href' => route('hrms.payroll.statutory.rules')],
                ['label' => $version->version, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.payroll.statutory.rules')" variant="secondary" size="sm">{{ __('Back to rule sets') }}</x-ui.button>
        </x-slot:actions>

        <x-entity.section :title="__('Version Details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Jurisdiction')">{{ $version->jurisdiction }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Status')">{{ $version->is_active ? __('Active') : __('Inactive') }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Effective From')">{{ $version->effective_from?->toDateString() }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Effective Until')">{{ $version->effective_until?->toDateString() ?: '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Configuration')">
            <pre class="overflow-auto rounded-md border border-line bg-surface-muted p-3 text-xs">{{ json_encode($version->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </x-entity.section>
    </x-layouts.entity-detail>
</x-app-layout>
