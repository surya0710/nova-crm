@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Code'), __('Employee'), __('Message'), __('Rule Set'), __('When')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Compliance Validation')" :subtitle="__('Review and run statutory compliance checks')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Statutory Compliance'), 'href' => route('hrms.payroll.statutory.index')],
                ['label' => __('Validation'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('validate', \App\Models\StatutoryComplianceError::class)
                <form method="POST" action="{{ route('hrms.payroll.statutory.validation.run') }}">
                    @csrf
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Run Validation') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        @if (session('statutory_validation_summary'))
            <x-ui.card class="mb-4">
                <p class="text-sm text-ink-muted">
                    {{ __('Validated :count employees with :errors compliance issue(s).', [
                        'count' => session('statutory_validation_summary.validated'),
                        'errors' => session('statutory_validation_summary.error_count'),
                    ]) }}
                </p>
            </x-ui.card>
        @endif

        <p class="mb-4 text-sm text-ink-muted">{{ __('Open issues (no payroll run): :count', ['count' => $stats['open_error_count']]) }}</p>

        @if ($errors->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" :title="__('No compliance errors recorded.')" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($errors as $error)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ $error->code }}</td>
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $error->employee?->employee_code ?? '—' }} {{ $error->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $error->message }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $error->ruleSet?->code ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $error->created_at?->toDateTimeString() }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
