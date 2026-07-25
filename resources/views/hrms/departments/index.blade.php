@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Name'), __('Code'), __('Parent'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Departments')" :subtitle="__('Organize employees into departments')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Departments'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-4">
            <form method="POST" action="{{ route('hrms.departments.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                @csrf
                <x-forms.field :label="__('Name')" name="name">
                    <x-forms.input name="name" placeholder="{{ __('Name') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Code')" name="code">
                    <x-forms.input name="code" placeholder="{{ __('Code') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Branch')" name="branch_id">
                    <x-forms.select name="branch_id">
                        <option value="">{{ __('Branch') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Parent Department')" name="parent_id">
                    <x-forms.select name="parent_id">
                        <option value="">{{ __('Parent Department') }}</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Department') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($departments->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="generic" :title="__('No departments yet.')" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($departments as $department)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $department->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $department->code }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $department->parent?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('hrms.departments.destroy', $department) }}">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger">{{ __('Delete') }}</x-ui.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($departments->hasPages())
            <x-slot:pagination>{{ $departments->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
