@php
    $statusVariant = [
        'pending' => 'neutral',
        'in_progress' => 'info',
        'completed' => 'success',
        'cancelled' => 'neutral',
    ];
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Milestone'),
        __('Project'),
        ['label' => __('Due'), 'class' => 'hidden md:table-cell'],
        __('Status'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Milestones')"
        :subtitle="__('Upcoming and open project checkpoints')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Milestones'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" action="{{ route('projects.milestones.hub') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('projects.milestone_statuses', []) as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <label class="inline-flex items-center gap-2 text-sm text-ink-body">
                    <input type="checkbox" name="upcoming" value="1" @checked(! empty($filters['upcoming'])) class="rounded border-line text-primary-600 focus:ring-primary-500" />
                    {{ __('Upcoming only') }}
                </label>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($milestones->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="milestones" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($milestones as $milestone)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('projects.milestones.index', $milestone->project) }}" class="text-sm font-semibold text-ink-heading hover:text-primary-700">
                                {{ $milestone->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-body">
                            @if ($milestone->project)
                                <a href="{{ route('projects.show', $milestone->project) }}" class="hover:text-primary-700">{{ $milestone->project->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-body">{{ $milestone->due_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$statusVariant[$milestone->status] ?? 'neutral'">{{ $milestone->status_label }}</x-ui.badge>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
            <x-slot:pagination>{{ $milestones->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
