<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Watching')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Watching'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Projects') }}</h3>
            </div>
            @if ($projects->isEmpty())
                <div class="p-8 text-center text-sm text-slate-500">{{ __('You are not watching any projects.') }}</div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($projects as $project)
                        <li class="px-6 py-4 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('projects.show', $project) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-700">{{ $project->name }}</a>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $project->project_number ?? '' }}</p>
                            </div>
                            @can('manageWatchers', $project)
                                <form method="POST" action="{{ route('projects.watch.destroy', $project) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-slate-500 hover:text-red-600">{{ __('Unwatch') }}</button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Tasks') }}</h3>
            </div>
            @if ($tasks->isEmpty())
                <div class="p-8 text-center text-sm text-slate-500">{{ __('You are not watching any tasks.') }}</div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($tasks as $task)
                        <li class="px-6 py-4 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('tasks.show', $task) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-700">{{ $task->title }}</a>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $task->task_number ?? '' }}
                                    @if ($task->project)
                                        · {{ $task->project->name }}
                                    @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('tasks.watch.destroy', $task) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-slate-500 hover:text-red-600">{{ __('Unwatch') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
