<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Task board')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Task board'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach ($statuses as $status)
            <div class="min-w-[260px] w-72 shrink-0 rounded-xl border border-slate-200 bg-slate-50">
                <div class="px-3 py-2 border-b border-slate-200 flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $status->color ?? '#94a3b8' }}"></span>
                    <h2 class="text-sm font-semibold text-slate-800">{{ $status->name }}</h2>
                    <span class="ml-auto text-xs text-slate-500">{{ ($columns[$status->id] ?? collect())->count() }}</span>
                </div>
                <div class="p-2 space-y-2 max-h-[70vh] overflow-y-auto">
                    @foreach (($columns[$status->id] ?? collect()) as $task)
                        <a href="{{ route('tasks.show', $task) }}" class="block rounded-lg bg-white border border-slate-200 p-3 hover:border-indigo-300 transition">
                            <div class="text-sm font-medium text-slate-900">{{ $task->title }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $task->assignee?->name ?? __('Unassigned') }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
