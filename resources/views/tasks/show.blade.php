@php
    $statusColors = [
        'pending' => 'bg-slate-100 text-slate-700',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-slate-100 text-slate-500',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ $task->title }}</h1>
                <p class="text-sm text-slate-500">{{ __('Task details') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($task->isOpen())
                    @can('update', $task)
                        <form method="POST" action="{{ route('tasks.complete', $task) }}">
                            @csrf @method('PATCH')
                            <x-primary-button type="submit">{{ __('Mark Complete') }}</x-primary-button>
                        </form>
                    @endcan
                @endif
                @can('update', $task)
                    <a href="{{ route('tasks.edit', $task) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Edit') }}</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">{{ __('Overview') }}</h3>
                    <span class="inline-flex text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusColors[$task->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $task->status_label }}</span>
                </div>
                <div class="p-6 space-y-4">
                    @if ($task->description)
                        <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ $task->description }}</p>
                    @else
                        <p class="text-sm text-slate-500">{{ __('No description provided.') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Details') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Priority') }}</dt>
                        <dd class="mt-1 text-slate-900">{{ $task->priority_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Due') }}</dt>
                        <dd class="mt-1 {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-slate-900' }}">
                            {{ $task->due_at?->format('M j, Y g:i A') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Assigned To') }}</dt>
                        <dd class="mt-1 text-slate-900">{{ $task->assignee?->name ?? __('Unassigned') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Created By') }}</dt>
                        <dd class="mt-1 text-slate-900">{{ $task->creator?->name ?? '—' }}</dd>
                    </div>
                    @if ($task->completed_at)
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Completed') }}</dt>
                            <dd class="mt-1 text-slate-900">{{ $task->completed_at->format('M j, Y g:i A') }}</dd>
                        </div>
                    @endif
                    @if ($task->taskable)
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Related Record') }}</dt>
                            <dd class="mt-1">
                                @php
                                    $taskableRoute = match ($task->taskable_type) {
                                        App\Models\Lead::class => route('leads.show', $task->taskable),
                                        App\Models\Customer::class => route('customers.show', $task->taskable),
                                        App\Models\Opportunity::class => route('pipeline.show', $task->taskable),
                                        default => null,
                                    };
                                    $taskableLabel = match ($task->taskable_type) {
                                        App\Models\Lead::class => $task->taskable->name,
                                        App\Models\Customer::class => $task->taskable->display_name,
                                        App\Models\Opportunity::class => $task->taskable->title,
                                        default => class_basename($task->taskable_type).' #'.$task->taskable_id,
                                    };
                                @endphp
                                @if ($taskableRoute)
                                    <a href="{{ $taskableRoute }}" class="text-indigo-600 hover:text-indigo-800">{{ $taskableLabel }}</a>
                                @else
                                    {{ $taskableLabel }}
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            @can('delete', $task)
                <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('{{ __('Delete this task?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete Task') }}</button>
                </form>
            @endcan

            <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">← {{ __('Back to tasks') }}</a>
        </div>
    </div>
</x-app-layout>
