@php
    $grouped = $events->groupBy(function ($event) {
        return optional($event->due_date ?? $event->starts_at)->format('Y-m-d') ?: 'undated';
    });
    $eventTypeColors = [
        'project_deadline' => 'bg-red-100 text-red-800',
        'milestone_due' => 'bg-amber-100 text-amber-800',
        'task_due' => 'bg-blue-100 text-blue-800',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Calendar')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Calendar'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <form method="GET" action="{{ route('projects.calendar') }}" class="mb-6 rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <x-input-label for="from" :value="__('From')" />
                <x-text-input id="from" type="date" name="from" class="block mt-1 w-full" :value="request('from')" />
            </div>
            <div>
                <x-input-label for="to" :value="__('To')" />
                <x-text-input id="to" type="date" name="to" class="block mt-1 w-full" :value="request('to')" />
            </div>
            <div>
                <x-input-label for="event_type" :value="__('Type')" />
                <select id="event_type" name="event_type" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All types') }}</option>
                    <option value="project_deadline" @selected(request('event_type') === 'project_deadline')>{{ __('Project deadline') }}</option>
                    <option value="milestone_due" @selected(request('event_type') === 'milestone_due')>{{ __('Milestone') }}</option>
                    <option value="task_due" @selected(request('event_type') === 'task_due')>{{ __('Task due') }}</option>
                </select>
            </div>
            <div>
                <x-input-label for="project_id" :value="__('Project ID')" />
                <x-text-input id="project_id" type="number" name="project_id" class="block mt-1 w-full" :value="request('project_id')" min="1" />
            </div>
            <div class="flex items-end">
                <x-secondary-button type="submit" class="w-full justify-center">{{ __('Apply filters') }}</x-secondary-button>
            </div>
        </div>
    </form>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($events->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">
                {{ __('No calendar events found for this range.') }}
                <p class="mt-2 text-xs text-slate-400">{{ __('Sync a project calendar from the project page to populate due dates.') }}</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($grouped as $date => $dayEvents)
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-slate-900 mb-3">
                            @if ($date === 'undated')
                                {{ __('Undated') }}
                            @else
                                {{ \Illuminate\Support\Carbon::parse($date)->format('l, M j, Y') }}
                            @endif
                        </h3>
                        <ul class="space-y-2">
                            @foreach ($dayEvents as $event)
                                <li class="flex items-start justify-between gap-3 rounded-lg border border-slate-100 px-4 py-3 hover:bg-slate-50/50">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $eventTypeColors[$event->event_type] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ str_replace('_', ' ', $event->event_type) }}
                                            </span>
                                            <p class="text-sm font-medium text-slate-900">{{ $event->title }}</p>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">
                                            @if ($event->starts_at)
                                                {{ $event->starts_at->format('g:i A') }}
                                                @if ($event->ends_at)
                                                    – {{ $event->ends_at->format('g:i A') }}
                                                @endif
                                            @elseif ($event->due_date)
                                                {{ __('Due') }} {{ $event->due_date->format('M j, Y') }}
                                            @endif
                                            · {{ $event->provider ?? 'internal' }}
                                        </p>
                                    </div>
                                    @if ($event->project_id)
                                        <a href="{{ route('projects.show', $event->project_id) }}" class="text-xs font-medium text-primary-600 hover:text-primary-700 shrink-0">{{ __('Project') }}</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
