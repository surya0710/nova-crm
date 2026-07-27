@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        ['label' => '', 'class' => 'w-10'],
        __('Code'),
        __('Name'),
        ['label' => __('Department'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Designation'), 'class' => 'hidden lg:table-cell'],
        __('Status'),
        ['label' => __('Account'), 'class' => 'hidden sm:table-cell'],
    ];
    $pageIds = $employees->getCollection()->pluck('id')->all();
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Employees')"
        :subtitle="__('Manage people across your organization')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Employees'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if (auth()->user()->hasPermission('hrms.view'))
                <x-ui.button :href="route('hrms.employees.create')" variant="primary" size="sm">{{ __('Add Employee') }}</x-ui.button>
            @endif
        </x-slot:actions>

        @if ($employees->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="employees"
                    :action-href="auth()->user()->hasPermission('hrms.view') ? route('hrms.employees.create') : null"
                />
            </x-ui.card>
        @else
            <x-bulk.toolbar
                entity-type="employee"
                :actions="$bulkActions ?? []"
                :page-ids="$pageIds"
                :redirect-to="route('hrms.employees.index')"
                :export-enabled="true"
                :filters="request()->except(['page'])"
            >
                <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                    @foreach ($employees as $employee)
                        <tr class="hover:bg-surface-muted/60 transition">
                            <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="rounded border-line text-primary-600"
                                        @change="toggleId({{ $employee->id }}, $event.target.checked)"
                                        :checked="selected.includes({{ $employee->id }})"
                                    >
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('hrms.employees.show', $employee) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">
                                    {{ $employee->employee_code }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('hrms.employees.show', $employee) }}" class="text-sm font-medium text-ink-heading hover:text-primary-700">
                                    {{ $employee->full_name }}
                                </a>
                                @if ($employee->email)
                                    <p class="text-xs text-ink-muted">{{ $employee->email }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $employee->department?->name ?? '—' }}</td>
                            <td class="px-4 py-3 hidden lg:table-cell text-sm text-ink-muted">{{ $employee->designation?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge variant="neutral">{{ config('hrms.employment_statuses.'.$employee->status, $employee->status) }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                @if ($employee->user)
                                    <x-ui.badge variant="neutral">{{ $employee->user->displayAccountStatusLabel() }}</x-ui.badge>
                                @else
                                    <span class="text-xs text-ink-muted">{{ __('No login') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
            </x-bulk.toolbar>
            <div class="mt-4">{{ $employees->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
