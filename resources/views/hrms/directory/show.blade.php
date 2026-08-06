<x-app-layout>
    <x-layouts.entity-detail
        :title="$profile['full_name']"
        :subtitle="$profile['employee_code']"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Directory'), 'href' => route('hrms.directory.index')],
                ['label' => $profile['full_name'], 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.directory.index')" variant="secondary" size="sm">{{ __('Back to Directory') }}</x-ui.button>
            <x-ui.button :href="route('hrms.employees.timeline', $employee)" variant="secondary" size="sm">{{ __('View Timeline') }}</x-ui.button>
        </x-slot:actions>

        <x-entity.section :title="__('Profile')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Employee Code')">{{ $profile['employee_code'] }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Designation')">{{ $profile['designation'] ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Department')">{{ $profile['department'] ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Branch')">{{ $profile['branch'] ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Manager')">{{ $profile['manager'] ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Email')">{{ $profile['email'] ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Phone')">{{ $profile['phone'] ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>
    </x-layouts.entity-detail>
</x-app-layout>
