<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Dependencies')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Dependencies'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

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
