@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Period'), __('Status'), __('Employees'), __('Success'), __('Errors'), __('Triggered By')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Payroll Runs')" :subtitle="__('Create and manage payroll runs')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Runs'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.payroll.runs.preview')" variant="secondary" size="sm">{{ __('Preview') }}</x-ui.button>
        </x-slot:actions>

        @can('create', \App\Models\PayrollRun::class)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('hrms.payroll.runs.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <x-forms.field :label="__('Payroll Period')" name="payroll_period_id">
                        <x-forms.select name="payroll_period_id" required>
                            <option value="">{{ __('Select payroll period') }}</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}">{{ $period->name }} ({{ $period->start_date?->toDateString() }} – {{ $period->end_date?->toDateString() }})</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Run') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($runs->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($runs as $run)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('hrms.payroll.runs.show', $run) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $run->period?->name }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ config('hrms.payroll.run_statuses.'.$run->status, $run->status) }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $run->employee_count }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $run->success_count }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $run->error_count }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $run->triggeredBy?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($runs->hasPages())
            <x-slot:pagination>{{ $runs->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
