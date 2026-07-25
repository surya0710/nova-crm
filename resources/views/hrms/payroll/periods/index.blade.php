@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Name'), __('Start'), __('End'), __('Status'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Payroll Periods')" :subtitle="__('Define payroll calculation periods')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Periods'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\PayrollPeriod::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.periods.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    @csrf
                    <x-forms.field :label="__('Name')" name="name">
                        <x-forms.input name="name" placeholder="{{ __('Name') }}" :value="old('name', now()->format('F Y'))" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Start Date')" name="start_date">
                        <x-forms.input name="start_date" type="date" :value="old('start_date', now()->startOfMonth()->toDateString())" required />
                    </x-forms.field>
                    <x-forms.field :label="__('End Date')" name="end_date">
                        <x-forms.input name="end_date" type="date" :value="old('end_date', now()->endOfMonth()->toDateString())" required />
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Period') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($periods->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($periods as $period)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $period->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $period->start_date?->toDateString() }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $period->end_date?->toDateString() }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $statuses[$period->status] ?? $period->status }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            @can('lock', $period)
                                @if (! $period->isLocked())
                                    <form method="POST" action="{{ route('hrms.payroll.periods.lock', $period) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="ghost" size="sm" class="text-warning">{{ __('Lock') }}</x-ui.button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($periods->hasPages())
            <x-slot:pagination>{{ $periods->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
