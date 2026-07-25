@php
    $cards = [
        'active' => [
            'label' => __('Active Projects'),
            'value' => $summary['active'] ?? 0,
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'color' => 'bg-emerald-50 text-emerald-600',
        ],
        'my_projects' => [
            'label' => __('My Projects'),
            'value' => $summary['my_projects'] ?? 0,
            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'color' => 'bg-indigo-50 text-primary-600',
        ],
        'upcoming_deadlines' => [
            'label' => __('Upcoming Deadlines'),
            'value' => $summary['upcoming_deadlines'] ?? 0,
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'color' => 'bg-amber-50 text-amber-600',
        ],
        'milestones_due' => [
            'label' => __('Milestones Due'),
            'value' => $summary['milestones_due'] ?? 0,
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'color' => 'bg-violet-50 text-violet-600',
        ],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Dashboard')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach ($cards as $key => $card)
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($card['value']) }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-lg flex items-center justify-center shrink-0 {{ $card['color'] }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $card['icon'] }}"/></svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
        <h3 class="font-semibold text-slate-900">{{ __('Quick Links') }}</h3>
        <p class="text-sm text-slate-500 mt-1">{{ __('Jump to common project management tasks') }}</p>
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:border-indigo-300 hover:text-indigo-700 transition">
                {{ __('Browse Projects') }}
            </a>
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition">
                    {{ __('Create Project') }}
                </a>
            @endcan
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
