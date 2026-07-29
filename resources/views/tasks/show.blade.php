@php
    $statusColors = [
        'pending' => 'bg-slate-100 text-slate-700',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-slate-100 text-slate-500',
    ];
    $statusLabel = $task->taskStatus?->name ?? $task->status_label;
    $priorityLabel = $task->taskPriority?->name ?? $task->priority_label;
    $dueDisplay = $task->due_date?->format('M j, Y')
        ?? $task->due_at?->format('M j, Y g:i A')
        ?? '—';

    $task->loadMissing(['labels', 'watchers', 'recurrence']);
    $isWatchingTask = $task->watchers->contains(fn ($w) => (int) $w->user_id === (int) auth()->id());
    $availableLabels = \App\Models\ProjectLabel::query()
        ->where('organization_id', $task->organization_id)
        ->orderBy('name')
        ->get()
        ->reject(fn ($label) => $task->labels->contains('id', $label->id));
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Tasks')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Tasks'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('tasks.checklists.index', $task) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Checklists') }}</a>
        <a href="{{ route('tasks.comments.index', $task) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Comments') }}</a>
        @if (config('attachments.task_attachments_enabled', true))
            <a href="{{ route('tasks.attachments.index', $task) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Attachments') }}</a>
        @endif
        <a href="{{ route('tasks.dependencies.index', $task) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Dependencies') }}</a>
        <a href="{{ route('tasks.time-logs.index', $task) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ __('Time Logs') }}</a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">{{ __('Overview') }}</h3>
                    @if ($task->completion_percentage !== null)
                        <span class="text-xs font-medium text-slate-500">{{ $task->completion_percentage }}% {{ __('complete') }}</span>
                    @endif
                </div>
                <div class="p-6 space-y-4">
                    @if ($task->description)
                        <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ $task->description }}</p>
                    @else
                        <p class="text-sm text-slate-500">{{ __('No description provided.') }}</p>
                    @endif

                    @if ($task->completion_percentage > 0)
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ min(100, (int) $task->completion_percentage) }}%"></div>
                        </div>
                    @endif
                </div>
            </div>

            @if (($task->checklists ?? collect())->isNotEmpty())
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">{{ __('Checklist') }}</h3>
                        <a href="{{ route('tasks.checklists.index', $task) }}" class="text-xs text-primary-600 hover:text-primary-700">{{ __('Manage') }}</a>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($task->checklists->sortBy('sequence')->take(8) as $item)
                            <li class="px-6 py-3 flex items-center gap-3 text-sm">
                                <span class="h-4 w-4 rounded border {{ $item->is_completed ? 'bg-emerald-500 border-emerald-500' : 'border-slate-300' }}"></span>
                                <span class="{{ $item->is_completed ? 'text-slate-400 line-through' : 'text-slate-800' }}">{{ $item->title }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (($task->comments ?? collect())->isNotEmpty())
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-900">{{ __('Recent Comments') }}</h3>
                        <a href="{{ route('tasks.comments.index', $task) }}" class="text-xs text-primary-600 hover:text-primary-700">{{ __('View all') }}</a>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($task->comments->whereNull('parent_comment_id')->sortByDesc('created_at')->take(5) as $comment)
                            <li class="px-6 py-3">
                                <div class="text-xs text-slate-500 mb-1">{{ $comment->user?->name ?? __('User') }} · {{ $comment->created_at?->diffForHumans() }}</div>
                                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($comment->comment, 240) }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Labels') }}</h3>
                </div>
                <div class="p-6 space-y-4">
                    @if ($task->labels->isEmpty())
                        <p class="text-sm text-slate-500">{{ __('No labels attached.') }}</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($task->labels as $label)
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-0.5 rounded-full border border-slate-200" style="background-color: {{ ($label->color ?? '#64748b') }}20; color: {{ $label->color ?? '#64748b' }}">
                                    {{ $label->name }}
                                    @can('update', $task)
                                        <form method="POST" action="{{ route('tasks.labels.detach', [$task, $label]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="hover:opacity-70" title="{{ __('Remove') }}">&times;</button>
                                        </form>
                                    @endcan
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @can('update', $task)
                        @if ($availableLabels->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach ($availableLabels as $label)
                                    <form method="POST" action="{{ route('tasks.labels.attach', [$task, $label]) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                            <span class="h-2 w-2 rounded-full" style="background-color: {{ $label->color ?? '#64748b' }}"></span>
                                            + {{ $label->name }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @elseif ($task->labels->isEmpty())
                            <a href="{{ route('project-labels.index') }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('Manage labels') }}</a>
                        @endif
                    @endcan
                </div>
            </div>

            @can('viewAny', App\Models\TaskRecurrence::class)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-semibold text-slate-900">{{ __('Recurrence') }}</h3>
                    </div>
                    <div class="p-6">
                        @include('tasks.partials.recurrence-form', ['task' => $task, 'recurrence' => $task->recurrence])
                    </div>
                </div>
            @endcan
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Details') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-slate-900">{{ $statusLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Priority') }}</dt>
                        <dd class="mt-1 text-slate-900">{{ $priorityLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Due') }}</dt>
                        <dd class="mt-1 {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-slate-900' }}">{{ $dueDisplay }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Assigned To') }}</dt>
                        <dd class="mt-1 text-slate-900">{{ $task->assignee?->name ?? __('Unassigned') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Estimated / Actual Hours') }}</dt>
                        <dd class="mt-1 text-slate-900">{{ $task->estimated_hours ?? '—' }} / {{ $task->actual_hours ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Created By') }}</dt>
                        <dd class="mt-1 text-slate-900">{{ $task->creator?->name ?? '—' }}</dd>
                    </div>
                    @if ($task->milestone)
                        <div>
                            <dt class="text-xs text-slate-500">{{ __('Milestone') }}</dt>
                            <dd class="mt-1 text-slate-900">{{ $task->milestone->name }}</dd>
                        </div>
                    @endif
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
                                        App\Models\Project::class => route('projects.show', $task->taskable),
                                        default => null,
                                    };
                                    $taskableLabel = match ($task->taskable_type) {
                                        App\Models\Lead::class => $task->taskable->name,
                                        App\Models\Customer::class => $task->taskable->display_name,
                                        App\Models\Opportunity::class => $task->taskable->title,
                                        App\Models\Project::class => $task->taskable->name,
                                        default => class_basename($task->taskable_type).' #'.$task->taskable_id,
                                    };
                                @endphp
                                @if ($taskableRoute)
                                    <a href="{{ $taskableRoute }}" class="text-primary-600 hover:text-primary-700">{{ $taskableLabel }}</a>
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

            <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">← {{ __('Back to tasks') }}</a>
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
