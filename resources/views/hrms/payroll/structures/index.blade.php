@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Name'), __('Effective'), __('Components'), __('Active'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Salary Structures')" :subtitle="__('Bundle salary components into structures')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Structures'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\SalaryStructure::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.structures.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <x-forms.field :label="__('Name')" name="name">
                            <x-forms.input name="name" placeholder="{{ __('Name') }}" :value="old('name')" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Effective Date')" name="effective_date">
                            <x-forms.input name="effective_date" type="date" :value="old('effective_date', now()->toDateString())" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Description')" name="description">
                            <x-forms.input name="description" placeholder="{{ __('Description') }}" :value="old('description')" />
                        </x-forms.field>
                        <label class="flex items-center gap-2 text-sm text-ink-heading">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-line" /> {{ __('Active') }}
                        </label>
                    </div>
                    @if ($availableComponents->isNotEmpty())
                        <div class="border-t border-line pt-3">
                            <p class="mb-2 text-sm font-medium text-ink-heading">{{ __('Attach Components') }}</p>
                            <div class="space-y-2">
                                @foreach ($availableComponents->take(5) as $index => $component)
                                    <div class="grid grid-cols-1 items-center gap-2 md:grid-cols-4">
                                        <label class="flex items-center gap-2 text-sm text-ink-heading">
                                            <input type="checkbox" name="components[{{ $index }}][salary_component_id]" value="{{ $component->id }}" class="rounded border-line" />
                                            {{ $component->name }} ({{ $component->code }})
                                        </label>
                                        <x-forms.select name="components[{{ $index }}][calculation_type]">
                                            @foreach ($calculationTypes as $value => $label)
                                                <option value="{{ $value }}">{{ __($label) }}</option>
                                            @endforeach
                                        </x-forms.select>
                                        <x-forms.input name="components[{{ $index }}][amount]" type="number" step="0.01" placeholder="{{ __('Amount') }}" />
                                        <x-forms.input name="components[{{ $index }}][percentage]" type="number" step="0.01" placeholder="{{ __('Percentage') }}" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Structure') }}</x-ui.button>
                </form>
            </x-ui.card>
        @endcan

        @if ($structures->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($structures as $structure)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $structure->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $structure->effective_date?->toDateString() }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $structure->structure_components_count }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $structure->is_active ? __('Yes') : __('No') }}</td>
                        <td class="px-4 py-3">
                            @can('delete', $structure)
                                <form method="POST" action="{{ route('hrms.payroll.structures.destroy', $structure) }}">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger">{{ __('Delete') }}</x-ui.button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($structures->hasPages())
            <x-slot:pagination>{{ $structures->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
