<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Dependencies')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => $task->title, 'href' => route('tasks.show', $task)],
                ['label' => __('Dependencies'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if (! empty($blockedBy))
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="text-sm font-semibold text-amber-900 mb-3">{{ __('Blocked By') }}</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($blockedBy as $blocker)
                        <li class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <a href="{{ $blocker['url'] }}" class="font-medium text-amber-900 hover:underline">{{ $blocker['title'] }}</a>
                                <p class="text-xs text-amber-800">
                                    {{ $blocker['assigned_to'] ?? __('Unassigned') }} · {{ $blocker['status'] }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($chain) && count($chain) > 1)
            <div class="mb-6 rounded-xl bg-white border border-slate-200 p-5">
                <h2 class="text-sm font-semibold text-slate-800 mb-4">{{ __('Dependency chain') }}</h2>
                <div class="flex flex-col items-start gap-1 text-sm">
                    @foreach ($chain as $index => $node)
                        <div class="font-medium text-slate-900">{{ $node['title'] }}</div>
                        @if ($index < count($chain) - 1)
                            <div class="pl-2 text-xs text-slate-400 leading-tight">↓<br>{{ __('Blocks') }}<br>↓</div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl bg-white border border-slate-200 p-5">
                <h2 class="text-sm font-semibold text-slate-800 mb-3">{{ __('Predecessors') }}</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($predecessors as $dep)
                        <li class="flex justify-between gap-2">
                            <span>{{ $dep->predecessor?->title }} ({{ $dep->dependency_type_label }})</span>
                            <form method="POST" action="{{ route('tasks.dependencies.destroy', [$task, $dep]) }}">@csrf @method('DELETE')<button class="text-red-600 text-xs">{{ __('Remove') }}</button></form>
                        </li>
                    @empty
                        <li class="text-slate-500">{{ __('None') }}</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('tasks.dependencies.store', $task) }}" class="mt-4 flex gap-2">
                    @csrf
                    <x-text-input name="predecessor_task_id" type="number" placeholder="{{ __('Predecessor task ID') }}" class="w-full" required />
                    <x-primary-button>{{ __('Add') }}</x-primary-button>
                </form>
            </div>
            <div class="rounded-xl bg-white border border-slate-200 p-5">
                <h2 class="text-sm font-semibold text-slate-800 mb-3">{{ __('Successors') }}</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($successors as $dep)
                        <li>{{ $dep->successor?->title }} ({{ $dep->dependency_type_label }})</li>
                    @empty
                        <li class="text-slate-500">{{ __('None') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
