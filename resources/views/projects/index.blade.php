@php
    $priorityVariant = [
        'low' => 'neutral',
        'medium' => 'warning',
        'high' => 'danger',
        'critical' => 'danger',
    ];
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Project'),
        __('Status'),
        ['label' => __('Owner'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Manager'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Planned End'), 'class' => 'hidden lg:table-cell'],
        __('Priority'),
        ['label' => '', 'align' => 'right'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Projects')"
        :subtitle="__('Manage and track your projects')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('All projects'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\Project::class)
                <x-ui.button :href="route('projects.create')" variant="primary" size="sm">{{ __('Add Project') }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route('projects.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label for="projects-search" class="sr-only">{{ __('Search projects') }}</label>
                    <x-forms.input id="projects-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search name, number, description…') }}" />
                </div>
                <x-forms.select name="status_id" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected(($filters['status_id'] ?? '') == $status->id)>{{ $status->name }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="category_id" aria-label="{{ __('Category') }}">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="owner_id" aria-label="{{ __('Owner') }}">
                    <option value="">{{ __('All owners') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['owner_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="manager_id" aria-label="{{ __('Manager') }}">
                    <option value="">{{ __('All managers') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['manager_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="priority" aria-label="{{ __('Priority') }}">
                    <option value="">{{ __('All priorities') }}</option>
                    @foreach (config('projects.priorities') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <div class="flex flex-wrap items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-ink-body">
                        <input type="checkbox" name="is_archived" value="1" @checked(($filters['is_archived'] ?? '') == '1') class="rounded border-line text-primary-600 focus:ring-primary-500" />
                        {{ __('Archived') }}
                    </label>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($projects->isEmpty())
            <x-ui.card>
                @if (! empty($filters['search']))
                    <x-ui.empty-state-preset variant="search" />
                @else
                    <x-ui.empty-state-preset
                        variant="projects"
                        :action-href="auth()->user()->can('create', App\Models\Project::class) ? route('projects.create') : null"
                    />
                @endif
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($projects as $project)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('projects.show', $project) }}" class="group block">
                                <p class="text-sm font-semibold text-ink-heading group-hover:text-primary-700">{{ $project->name }}</p>
                                <p class="text-xs text-ink-muted mt-0.5">{{ $project->project_number ?? '—' }}</p>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            @if ($project->status)
                                <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full" style="background-color: {{ $project->status->color }}20; color: {{ $project->status->color }}">
                                    {{ $project->status->name }}
                                </span>
                            @else
                                <span class="text-sm text-ink-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-body">{{ $project->owner?->name ?? '—' }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-body">{{ $project->manager?->name ?? '—' }}</td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm text-ink-body">
                            {{ $project->planned_end_date?->format('M j, Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$priorityVariant[$project->priority] ?? 'neutral'">{{ $project->priority_label }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $project)
                                <a href="{{ route('projects.edit', $project) }}" class="text-sm text-ink-muted hover:text-primary-700">{{ __('Edit') }}</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
            <x-slot:pagination>
                {{ $projects->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
