<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Import Center')"
        :subtitle="__('Import business data from Excel or CSV into NovaCRM')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Import Center'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('administration.imports.history')" variant="secondary" size="sm">
                {{ __('Import history') }}
            </x-ui.button>
        </x-slot:actions>

        <div class="space-y-8">
            @forelse ($groups as $module => $entities)
                <x-entity.section :title="__($moduleLabels[$module] ?? ucfirst($module))">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($entities as $entity)
                            <a
                                href="{{ route('administration.imports.create', $entity['type']) }}"
                                class="block rounded-lg border border-line bg-surface px-4 py-4 transition hover:border-primary-300 hover:bg-primary-50/40"
                            >
                                <p class="font-medium text-ink-heading">{{ __($entity['label']) }}</p>
                                <p class="mt-1 text-xs text-ink-muted">
                                    {{ __(':count fields · CSV & Excel', ['count' => $entity['field_count']]) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </x-entity.section>
            @empty
                <x-ui.empty-state
                    :title="__('No imports available')"
                    :description="__('Your role or licensed modules do not include any importable entities.')"
                />
            @endforelse
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
