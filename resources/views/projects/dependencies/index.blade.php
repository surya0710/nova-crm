@php
    $dependencyTypes = config('projects.dependency_types', []);
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Dependencies')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Dependencies'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Direct Predecessors') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ count($impact['direct_predecessors'] ?? []) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Direct Successors') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ count($impact['direct_successors'] ?? []) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Downstream Impact') }}</p>
            <p class="mt-1 text-3xl font-bold text-primary-600">{{ count($impact['downstream_projects'] ?? []) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Predecessors') }}</h3>
                <p class="text-sm text-slate-500 mt-0.5">{{ __('Projects this project depends on') }}</p>
            </div>
            @forelse ($impact['direct_predecessors'] ?? [] as $item)
                <div class="px-6 py-4 border-b border-slate-100 last:border-0 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $item['name'] ?? '—' }}</p>
                        <p class="text-xs text-slate-500">{{ $dependencyTypes[$item['type'] ?? ''] ?? ($item['type'] ?? '') }}</p>
                    </div>
                    @if (! empty($item['project_id']))
                        <a href="{{ route('projects.show', $item['project_id']) }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('View') }}</a>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-500">{{ __('No predecessor dependencies.') }}</div>
            @endforelse
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Successors') }}</h3>
                <p class="text-sm text-slate-500 mt-0.5">{{ __('Projects that depend on this project') }}</p>
            </div>
            @forelse ($impact['direct_successors'] ?? [] as $item)
                <div class="px-6 py-4 border-b border-slate-100 last:border-0 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $item['name'] ?? '—' }}</p>
                        <p class="text-xs text-slate-500">{{ $dependencyTypes[$item['type'] ?? ''] ?? ($item['type'] ?? '') }}</p>
                    </div>
                    @if (! empty($item['project_id']))
                        <a href="{{ route('projects.show', $item['project_id']) }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('View') }}</a>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-500">{{ __('No successor dependencies.') }}</div>
            @endforelse
        </div>
    </div>

    @if (! empty($impact['downstream_projects']))
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden mt-6">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Full Downstream Chain') }}</h3>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($impact['downstream_projects'] as $item)
                    <li class="px-6 py-3 text-sm text-slate-700">{{ is_array($item) ? ($item['name'] ?? $item['project_id'] ?? '—') : $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('project-dependencies.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('View organization dependency graph') }}</a>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
