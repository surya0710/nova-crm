@php
    $nav = [
        ['route' => 'resources.planner', 'label' => __('Planner')],
        ['route' => 'resources.capacity', 'label' => __('Capacity')],
        ['route' => 'resources.timeline', 'label' => __('Timeline')],
        ['route' => 'resources.forecast', 'label' => __('Forecast')],
        ['route' => 'resources.allocations.index', 'label' => __('Allocations')],
        ['route' => 'resources.calendars.index', 'label' => __('Calendars')],
    ];
@endphp
<div class="flex flex-wrap items-center gap-2 shrink-0">
    @foreach ($nav as $item)
        <a href="{{ route($item['route']) }}"
           class="inline-flex items-center rounded-lg border px-3 py-2 text-sm font-medium {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
    @can('create', App\Models\ResourceAllocation::class)
        <a href="{{ route('resources.allocations.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
            {{ __('Allocate') }}
        </a>
    @endcan
</div>
