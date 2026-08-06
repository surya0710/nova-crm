<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Tax Financial Years')" :subtitle="__('Configure assessment years and tax slabs')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Income Tax'), 'href' => route('hrms.payroll.tax.index')],
                ['label' => __('Financial Years'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\TaxFinancialYear::class)
            <x-ui.card class="mb-6">
                <form method="POST" action="{{ route('hrms.payroll.tax.financial-years.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <x-forms.field :label="__('Code')" name="code" required>
                        <x-forms.input name="code" :value="old('code')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Label')" name="label" required>
                        <x-forms.input name="label" :value="old('label')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Assessment Year')" name="assessment_year" required>
                        <x-forms.input name="assessment_year" :value="old('assessment_year')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Start Date')" name="start_date" required>
                        <x-forms.input name="start_date" type="date" :value="old('start_date')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('End Date')" name="end_date" required>
                        <x-forms.input name="end_date" type="date" :value="old('end_date')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Default Regime')" name="default_regime" required>
                        <x-forms.select name="default_regime" required>
                            <option value="new" @selected(old('default_regime', 'new') === 'new')>{{ __('New Regime') }}</option>
                            <option value="old" @selected(old('default_regime') === 'old')>{{ __('Old Regime') }}</option>
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Set Active')" name="is_active">
                        <x-forms.checkbox name="is_active" :checked="old('is_active')" />
                    </x-forms.field>
                    <div class="md:col-span-3">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Financial Year') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        <x-ui.card :padding="false">
            <x-tables.table :columns="[__('Code'), __('Label'), __('Assessment Year'), __('Period'), __('Slabs'), __('Active'), __('Actions')]">
                @forelse ($financialYears as $fy)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $fy->code }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $fy->label }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $fy->assessment_year }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $fy->start_date?->format('Y-m-d') }} – {{ $fy->end_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $fy->slabs_count }}</td>
                        <td class="px-4 py-3">
                            @if ($fy->is_active)
                                <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="neutral">{{ __('Inactive') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @can('activate', $fy)
                                @unless ($fy->is_active)
                                    <form method="POST" action="{{ route('hrms.payroll.tax.financial-years.activate', $fy) }}" class="inline">@csrf
                                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Activate') }}</x-ui.button>
                                    </form>
                                @endunless
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state-preset variant="payroll" /></td></tr>
                @endforelse
            </x-tables.table>
            <div class="border-t border-line px-4 py-3">{{ $financialYears->links() }}</div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-app-layout>
