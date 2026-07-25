@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Name'), __('Code'), __('Department'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Designations')" :subtitle="__('Define job titles and roles')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Designations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-4">
            <form method="POST" action="{{ route('hrms.designations.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                @csrf
                <x-forms.field :label="__('Name')" name="name">
                    <x-forms.input name="name" placeholder="{{ __('Name') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Code')" name="code">
                    <x-forms.input name="code" placeholder="{{ __('Code') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Department')" name="department_id">
                    <x-forms.select name="department_id">
                        <option value="">{{ __('Department') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Description')" name="description">
                    <x-forms.input name="description" placeholder="{{ __('Description') }}" />
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Designation') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($designations->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="generic" :title="__('No designations yet.')" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($designations as $designation)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $designation->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $designation->code }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $designation->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('hrms.designations.destroy', $designation) }}">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger">{{ __('Delete') }}</x-ui.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($designations->hasPages())
            <x-slot:pagination>{{ $designations->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
