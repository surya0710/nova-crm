<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Timeline')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Timeline'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($tasks->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No scheduled tasks found.') }}</div>
        @else
            <ul class="divide-y divide-slate-200">
                @foreach ($tasks as $task)
                    <li class="px-6 py-4 flex items-center justify-between gap-4">
                        <div>
                            <a href="{{ route('tasks.show', $task) }}" class="text-sm font-semibold text-indigo-700 hover:underline">{{ $task->title }}</a>
                            <div class="text-xs text-slate-500 mt-1">
                                {{ $task->start_date?->toDateString() ?? '—' }}
                                →
                                {{ $task->due_date?->toDateString() ?? $task->due_at?->toDayDateTimeString() ?? '—' }}
                            </div>
                        </div>
                        <div class="text-xs text-slate-500">{{ $task->assignee?->name ?? __('Unassigned') }}</div>
                    </li>
                @endforeach
            </ul>
            <div class="px-6 py-3 border-t border-slate-200">{{ $tasks->withQueryString()->links() }}</div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
