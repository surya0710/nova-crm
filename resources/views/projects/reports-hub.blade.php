<x-app-layout>
    <x-layouts.entity-listing
        :title="__('Project reports')"
        :subtitle="__('Executive, portfolio, budget, and resource report shortcuts')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if ($reports->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="reports" />
            </x-ui.card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($reports as $report)
                    <x-ui.card>
                        <h3 class="text-sm font-semibold text-ink-heading">{{ $report['label'] }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ $report['description'] }}</p>
                        <div class="mt-4">
                            <x-ui.button :href="$report['href']" variant="secondary" size="sm">{{ __('Open') }}</x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
