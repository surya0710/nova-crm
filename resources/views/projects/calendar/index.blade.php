@php
    $view = $calendar['view'] ?? 'month';
    $year = (int) ($calendar['year'] ?? now()->year);
    $month = (int) ($calendar['month'] ?? now()->month);
    $prev = \Illuminate\Support\Carbon::create($year, $month, 1)->subMonth();
    $next = \Illuminate\Support\Carbon::create($year, $month, 1)->addMonth();
    $days = $calendar['days'] ?? [];
    $events = $calendar['events'] ?? [];
    $legend = $calendar['legend'] ?? [];
    $typeColors = [
        'task' => 'bg-blue-100 text-blue-800',
        'milestone' => 'bg-amber-100 text-amber-800',
        'holiday' => 'bg-orange-100 text-orange-800',
        'leave' => 'bg-sky-100 text-sky-800',
        'sprint' => 'bg-violet-100 text-violet-800',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Project Calendar')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Calendar'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                @if ($view === 'month')
                    <a href="{{ route('projects.calendar', array_merge(request()->except(['year', 'month']), ['view' => 'month', 'year' => $prev->year, 'month' => $prev->month])) }}"
                       class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">←</a>
                    <h2 class="text-lg font-semibold text-slate-900">{{ \Illuminate\Support\Carbon::create($year, $month, 1)->format('F Y') }}</h2>
                    <a href="{{ route('projects.calendar', array_merge(request()->except(['year', 'month']), ['view' => 'month', 'year' => $next->year, 'month' => $next->month])) }}"
                       class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">→</a>
                @else
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ \Illuminate\Support\Carbon::parse($calendar['from'])->format('M j') }}
                        –
                        {{ \Illuminate\Support\Carbon::parse($calendar['to'])->format('M j, Y') }}
                    </h2>
                @endif
                <a href="{{ route('projects.calendar', ['view' => $view]) }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('Today') }}</a>
            </div>
            <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-white">
                @foreach (['month' => __('Month'), 'week' => __('Week'), 'agenda' => __('Agenda')] as $key => $label)
                    <a href="{{ route('projects.calendar', array_merge(request()->except(['view', 'from', 'to']), ['view' => $key])) }}"
                       class="px-3 py-1.5 text-sm rounded-md {{ $view === $key ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-600 hover:text-slate-900' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <form method="GET" action="{{ route('projects.calendar') }}" class="mb-6 rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
            <input type="hidden" name="view" value="{{ $view }}">
            @if ($view === 'month')
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <x-input-label for="project_id" :value="__('Project')" />
                    <select id="project_id" name="project_id" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('All projects') }}</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="employee_id" :value="__('Employee')" />
                    <select id="employee_id" name="employee_id" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('All employees') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <x-text-input id="status" name="status" class="block mt-1 w-full" :value="request('status')" placeholder="{{ __('e.g. pending') }}" />
                </div>
                <div>
                    <x-input-label for="priority" :value="__('Priority')" />
                    <select id="priority" name="priority" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('All priorities') }}</option>
                        @foreach ($priorities as $key => $label)
                            <option value="{{ is_string($key) ? $key : ($label['slug'] ?? $label) }}" @selected(request('priority') === (is_string($key) ? $key : ($label['slug'] ?? '')))>
                                {{ is_string($label) ? $label : ($label['name'] ?? $key) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <x-secondary-button type="submit" class="w-full justify-center">{{ __('Apply filters') }}</x-secondary-button>
                </div>
            </div>
        </form>

        <div class="mb-4 flex flex-wrap gap-3 text-xs">
            @foreach ($legend as $item)
                <span class="inline-flex items-center gap-1.5 text-slate-600">
                    <span class="h-2.5 w-2.5 rounded-full {{ match($item['color'] ?? '') {
                        'amber' => 'bg-amber-400',
                        'orange' => 'bg-orange-400',
                        'sky' => 'bg-sky-400',
                        'violet' => 'bg-violet-400',
                        default => 'bg-blue-400',
                    } }}"></span>
                    {{ $item['label'] }}
                </span>
            @endforeach
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            @if (empty($events))
                <div class="p-12 text-center text-sm text-slate-500">
                    {{ __('No calendar events found for this range.') }}
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($days as $date => $dayEvents)
                        @if ($view === 'agenda' && empty($dayEvents))
                            @continue
                        @endif
                        <div class="p-5 sm:p-6">
                            <h3 class="text-sm font-semibold text-slate-900 mb-3">
                                {{ \Illuminate\Support\Carbon::parse($date)->format('l, M j, Y') }}
                            </h3>
                            @if (empty($dayEvents))
                                <p class="text-xs text-slate-400">{{ __('No events') }}</p>
                            @else
                                <ul class="space-y-2">
                                    @foreach ($dayEvents as $event)
                                        <li class="flex items-start justify-between gap-3 rounded-lg border border-slate-100 px-4 py-3 hover:bg-slate-50/50">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $typeColors[$event['type']] ?? 'bg-slate-100 text-slate-600' }}">
                                                        {{ ucfirst($event['type']) }}
                                                    </span>
                                                    @if (! empty($event['url']))
                                                        <a href="{{ $event['url'] }}" class="text-sm font-medium text-slate-900 hover:text-primary-700">{{ $event['title'] }}</a>
                                                    @else
                                                        <p class="text-sm font-medium text-slate-900">{{ $event['title'] }}</p>
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    @if (! empty($event['project_name']))
                                                        {{ $event['project_name'] }} ·
                                                    @endif
                                                    @if (! empty($event['assignee']))
                                                        {{ $event['assignee'] }} ·
                                                    @endif
                                                    {{ $event['status'] ?? '' }}
                                                    @if (! empty($event['priority']))
                                                        · {{ ucfirst($event['priority']) }}
                                                    @endif
                                                </p>
                                            </div>
                                            @if (! empty($event['task_id']) && ! empty($event['url']))
                                                <a href="{{ $event['url'] }}" class="text-xs font-medium text-primary-600 hover:text-primary-700 shrink-0">{{ __('Open') }}</a>
                                            @elseif (! empty($event['project_id']))
                                                <a href="{{ route('projects.show', $event['project_id']) }}" class="text-xs font-medium text-primary-600 hover:text-primary-700 shrink-0">{{ __('Project') }}</a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
