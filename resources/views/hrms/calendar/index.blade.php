@php
    $prev = \Carbon\Carbon::create($year, $month, 1)->subMonth();
    $next = \Carbon\Carbon::create($year, $month, 1)->addMonth();
@endphp

<x-app-layout>
    <x-layouts.entity-listing
        :title="__('Organization Calendar')"
        :subtitle="\Carbon\Carbon::create($year, $month, 1)->format('F Y')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Calendar'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.calendar', ['year' => $prev->year, 'month' => $prev->month])" variant="secondary" size="sm">← {{ $prev->format('M Y') }}</x-ui.button>
            <x-ui.button :href="route('hrms.calendar', ['year' => $next->year, 'month' => $next->month])" variant="secondary" size="sm">{{ $next->format('M Y') }} →</x-ui.button>
        </x-slot:actions>

        @if (empty($events))
            <x-ui.card>
                <x-ui.empty-state-preset variant="generic" :title="__('No events this month.')" />
            </x-ui.card>
        @else
            <x-ui.card :padding="false">
                @foreach ($events as $event)
                    <div class="flex gap-4 border-b border-line px-5 py-3 text-sm last:border-0">
                        <div class="w-28 shrink-0 text-ink-muted">{{ \Carbon\Carbon::parse($event['date'])->format('M j') }}</div>
                        <div>
                            <x-ui.badge variant="neutral" class="mr-2">{{ str_replace('_', ' ', $event['type']) }}</x-ui.badge>
                            <span class="text-ink-heading">{{ $event['title'] }}</span>
                        </div>
                    </div>
                @endforeach
            </x-ui.card>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
