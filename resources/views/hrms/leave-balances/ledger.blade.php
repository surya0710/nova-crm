@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Date'),
        __('Employee'),
        __('Type'),
        __('Transaction'),
        __('Qty'),
        __('Before'),
        __('After'),
        __('Remarks'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Leave Balance Ledger')"
        :subtitle="__('Transaction history for leave balance adjustments')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Leave Balances'), 'href' => route('hrms.leave-balances.index')],
                ['label' => __('Ledger'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if ($transactions->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="leave" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($transactions as $transaction)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $transaction->created_at->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $transaction->leaveBalance->employee->first_name ?? '—' }} {{ $transaction->leaveBalance->employee->last_name ?? '' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $transaction->leaveBalance->leaveType->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $transactionTypes[$transaction->transaction_type] ?? $transaction->transaction_type }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $transaction->quantity }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $transaction->balance_before }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $transaction->balance_after }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $transaction->remarks ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $transactions->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
