@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Name'), __('Code'), __('Type'), __('Taxable'), __('Recurring'), __('Active'), __('Actions')];
    $payrollBreadcrumb = [
        ['label' => __('HR'), 'href' => route('hrms.home')],
        ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
        ['label' => __('Components'), 'current' => true],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Salary Components')" :subtitle="__('Define earnings and deduction components')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="$payrollBreadcrumb" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\SalaryComponent::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.components.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-6">
                    @csrf
                    <x-forms.field :label="__('Name')" name="name">
                        <x-forms.input name="name" placeholder="{{ __('Name') }}" :value="old('name')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Code')" name="code">
                        <x-forms.input name="code" placeholder="{{ __('Code') }}" :value="old('code')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Type')" name="component_type">
                        <x-forms.select name="component_type" required>
                            @foreach ($componentTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('component_type') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <label class="flex items-center gap-2 text-sm text-ink-heading">
                        <input type="checkbox" name="is_taxable" value="1" @checked(old('is_taxable', true)) class="rounded border-line" /> {{ __('Taxable') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-ink-heading">
                        <input type="checkbox" name="is_recurring" value="1" @checked(old('is_recurring', true)) class="rounded border-line" /> {{ __('Recurring') }}
                    </label>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Component') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($components->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($components as $component)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $component->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $component->code }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $componentTypes[$component->component_type] ?? $component->component_type }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $component->is_taxable ? __('Yes') : __('No') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $component->is_recurring ? __('Yes') : __('No') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $component->is_active ? __('Yes') : __('No') }}</td>
                        <td class="px-4 py-3">
                            @can('delete', $component)
                                <form method="POST" action="{{ route('hrms.payroll.components.destroy', $component) }}">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger">{{ __('Delete') }}</x-ui.button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($components->hasPages())
            <x-slot:pagination>{{ $components->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
