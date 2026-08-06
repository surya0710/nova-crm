@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Project'),
        ['label' => __('Owner'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Estimated'), 'align' => 'right'],
        ['label' => __('Actual'), 'align' => 'right'],
        ['label' => __('Variance'), 'align' => 'right', 'class' => 'hidden lg:table-cell'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Budgets')"
        :subtitle="__('Project budget overview and variance')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Budgets'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-ui.stat-card :label="__('Estimated')" :value="number_format($totals['estimated'], 0)" />
            <x-ui.stat-card :label="__('Actual')" :value="number_format($totals['actual'], 0)" />
            <x-ui.stat-card :label="__('Variance')" :value="number_format($totals['variance'], 0)" :hint="__('Estimated − actual')" />
        </div>

        @if ($projects->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    :title="__('No project budgets')"
                    :description="__('Budgets appear when projects have estimated or actual amounts.')"
                />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($projects as $project)
                    @php $variance = (float) ($project->estimated_budget ?? 0) - (float) ($project->actual_budget ?? 0); @endphp
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('projects.budgets.show', $project) }}" class="text-sm font-semibold text-ink-heading hover:text-primary-700">{{ $project->name }}</a>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-body">{{ $project->owner?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-body">{{ $project->estimated_budget ? number_format($project->estimated_budget, 0) : '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-body">{{ $project->actual_budget ? number_format($project->actual_budget, 0) : '—' }}</td>
                        <td class="px-4 py-3 text-right hidden lg:table-cell text-sm text-ink-body">{{ number_format($variance, 0) }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
            <x-slot:pagination>{{ $projects->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
