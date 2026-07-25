@php
    $statusColors = [
        'pending' => 'bg-slate-100 text-slate-700',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-slate-100 text-slate-500',
    ];
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600',
        'medium' => 'bg-amber-100 text-amber-800',
        'high' => 'bg-red-100 text-red-800',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Tasks')" :subtitle="__('Track delivery work across projects')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Tasks'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if (\Illuminate\Support\Facades\Route::has('tasks.board'))
                <x-ui.button :href="route('tasks.board')" variant="secondary" size="sm">{{ __('Board') }}</x-ui.button>
            @endif
            @can('create', App\Models\Task::class)
                <x-ui.button :href="route('tasks.create')" variant="primary" size="sm">{{ __('Add Task') }}</x-ui.button>
            @endcan
        </x-slot:actions>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('tasks.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search tasks…') }}" class="w-full" />
            </div>
            <select name="status" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (config('tasks.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="priority" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All priorities') }}</option>
                @foreach (config('tasks.priorities') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="assigned_to" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('Anyone') }}</option>
                @foreach ($assignees as $member)
                    <option value="{{ $member->id }}" @selected(($filters['assigned_to'] ?? '') == $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <select name="filter" class="flex-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All dates') }}</option>
                    <option value="due_today" @selected(($filters['filter'] ?? '') === 'due_today')>{{ __('Due today') }}</option>
                    <option value="overdue" @selected(($filters['filter'] ?? '') === 'overdue')>{{ __('Overdue') }}</option>
                </select>
                <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($tasks->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="tasks"
                    :action-href="auth()->user()->can('create', App\Models\Task::class) ? route('tasks.create') : null"
                />
            </x-ui.card>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Task') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Due') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Assigned') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tasks as $task)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <a href="{{ route('tasks.show', $task) }}" class="font-medium text-primary-600 hover:text-primary-700">{{ $task->title }}</a>
                                    @if ($task->taskable)
                                        <p class="text-xs text-slate-500 mt-1">{{ __('Linked record') }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusColors[$task->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $task->status_label }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-slate-600' }}">
                                    {{ $task->due_at?->format('M j, Y g:i A') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $task->assignee?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('update', $task)
                                        <a href="{{ route('tasks.edit', $task) }}" class="text-sm text-slate-500 hover:text-primary-600">{{ __('Edit') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100">{{ $tasks->links() }}</div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
