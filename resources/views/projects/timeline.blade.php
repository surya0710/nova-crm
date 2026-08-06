@php
    $milestoneStatusColors = [
        'pending' => 'bg-slate-100 text-slate-600',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-slate-100 text-slate-500',
    ];
@endphp

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
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex flex-wrap items-center gap-3">
                @if ($project->status)
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full" style="background-color: {{ $project->status->color }}20; color: {{ $project->status->color }}">
                        {{ $project->status->name }}
                    </span>
                @endif
                @if ($project->lifecycleStage)
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">
                        {{ $project->lifecycleStage->name }}
                    </span>
                @endif
            </div>
        </div>

        @if ($project->milestones->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No milestones on this timeline yet.') }}</div>
        @else
            <div class="p-6">
                <ol class="relative border-l border-slate-200 ml-3 space-y-8">
                    @foreach ($project->milestones as $milestone)
                        <li class="ml-6">
                            <span class="absolute -left-1.5 flex h-3 w-3 items-center justify-center rounded-full {{ $milestone->isCompleted() ? 'bg-emerald-500' : 'bg-indigo-500' }} ring-4 ring-white"></span>
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">
                                        <span class="text-xs text-slate-400 font-normal mr-2">#{{ $milestone->sequence }}</span>
                                        {{ $milestone->name }}
                                    </p>
                                    @if ($milestone->description)
                                        <p class="mt-1 text-sm text-slate-600">{{ $milestone->description }}</p>
                                    @endif
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ __('Due') }}: {{ $milestone->due_date?->format('M j, Y') ?? '—' }}
                                        @if ($milestone->completed_at)
                                            · {{ __('Completed') }} {{ $milestone->completed_at->format('M j, Y') }}
                                        @endif
                                    </p>
                                </div>
                                <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full shrink-0 {{ $milestoneStatusColors[$milestone->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $milestone->status_label }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
