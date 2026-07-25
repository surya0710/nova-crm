<x-app-layout>
    <x-layouts.entity-listing
        :title="__('CRM exports')"
        :subtitle="__('Download finance and revenue extracts')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Exports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if ($exports->isEmpty())
            <x-ui.card>
                <x-ui.empty-state
                    :title="__('No exports available')"
                    :description="__('You do not have permission to export CRM reports.')"
                />
            </x-ui.card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($exports as $export)
                    <x-ui.card>
                        <h3 class="text-sm font-semibold text-ink-heading">{{ $export['label'] }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ $export['description'] }}</p>
                        <div class="mt-4">
                            <x-ui.button :href="$export['href']" variant="secondary" size="sm">{{ __('Open') }}</x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
