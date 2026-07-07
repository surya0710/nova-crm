@props([
    'taskableType' => null,
    'taskableId' => null,
    'tasks',
    'canCreate' => false,
])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden']) }}>
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold text-slate-900">{{ __('Tasks') }}</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ __('Follow-ups linked to this record') }}</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('tasks.create', ['taskable_type' => $taskableType, 'taskable_id' => $taskableId]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Add Task') }}</a>
        @endif
    </div>
    <div class="p-6 space-y-4">
        @if ($canCreate)
            <form method="POST" action="{{ route('tasks.store') }}" class="space-y-3 border-b border-slate-100 pb-4">
                @csrf
                <input type="hidden" name="taskable_type" value="{{ $taskableType }}">
                <input type="hidden" name="taskable_id" value="{{ $taskableId }}">
                <input type="hidden" name="redirect_back" value="1">
                <input type="hidden" name="status" value="pending">
                <input type="hidden" name="priority" value="medium">
                <x-text-input name="title" placeholder="{{ __('Quick task title…') }}" class="w-full" required />
                <div class="flex flex-col sm:flex-row gap-3">
                    <x-text-input name="due_at" type="datetime-local" class="w-full sm:flex-1" />
                    <select name="assigned_to" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm sm:flex-1">
                        <option value="">{{ __('Unassigned') }}</option>
                        @foreach (app(\App\Services\TenantContext::class)->get()?->users()->orderBy('name')->get() ?? [] as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                    <x-primary-button type="submit" class="shrink-0">{{ __('Add') }}</x-primary-button>
                </div>
                <x-input-error :messages="$errors->get('title')" />
            </form>
        @endif

        @if ($tasks->isEmpty())
            <p class="text-sm text-slate-500 text-center py-4">{{ __('No tasks yet.') }}</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($tasks as $task)
                    <div class="flex items-start justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <a href="{{ route('tasks.show', $task) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 block truncate">{{ $task->title }}</a>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $task->status_label }}
                                @if ($task->due_at)
                                    · {{ $task->due_at->format('M j, g:i A') }}
                                @endif
                                @if ($task->isOverdue())
                                    · <span class="text-red-600 font-medium">{{ __('Overdue') }}</span>
                                @endif
                            </p>
                        </div>
                        @if ($task->isOpen() && auth()->user()->can('update', $task))
                            <form method="POST" action="{{ route('tasks.complete', $task) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-emerald-600 hover:text-emerald-800 whitespace-nowrap">{{ __('Complete') }}</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
