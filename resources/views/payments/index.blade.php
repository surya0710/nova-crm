<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ crm_term('payments') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Payment history and receipts') }}</p>
            </div>
            @can('create', App\Models\Payment::class)
                <a href="{{ route('payments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    {{ __('Record :label', ['label' => crm_term('payment')]) }}
                </a>
            @endcan
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search payment, invoice, customer…') }}" class="w-full sm:col-span-2" />
            <div class="flex gap-2">
                <select name="method" class="flex-1 border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All methods') }}</option>
                    @foreach (config('payments.methods') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['method'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($payments->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No payments recorded yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ crm_term('payment') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ crm_term('invoice') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ crm_term('customer') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('Date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('Method') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($payments as $payment)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <a href="{{ route('payments.show', $payment) }}" class="text-sm font-semibold text-indigo-600">{{ $payment->number }}</a>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-slate-700 hover:text-indigo-600">{{ $payment->invoice->number }}</a>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $payment->customer->display_name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $payment->payment_date->format('M j, Y') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $payment->method_label }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-right text-slate-900">{{ $payment->formatted_amount }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($payments->hasPages())
                <div class="px-6 py-4 border-t">{{ $payments->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
