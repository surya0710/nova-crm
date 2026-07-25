@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        crm_term('payment'),
        crm_term('invoice'),
        crm_term('customer'),
        ['label' => __('Date'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Method'), 'class' => 'hidden lg:table-cell'],
        ['label' => __('Amount'), 'align' => 'right'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="crm_term('payments')"
        :subtitle="__('Payment history and receipts')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('payments'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\Payment::class)
                <x-ui.button :href="route('payments.create')" variant="primary" size="sm">{{ __('Record :label', ['label' => crm_term('payment')]) }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route('payments.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="payments-search" class="sr-only">{{ __('Search payments') }}</label>
                    <x-forms.input id="payments-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search payment, invoice, customer…') }}" />
                </div>
                <div class="flex gap-2">
                    <x-forms.select name="method" class="flex-1" aria-label="{{ __('Method') }}">
                        <option value="">{{ __('All methods') }}</option>
                        @foreach (config('payments.methods') as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['method'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($payments->isEmpty())
            <x-ui.card>
                @if (! empty($filters['search']) || ! empty($filters['method']))
                    <x-ui.empty-state-preset variant="search" />
                @else
                    <x-ui.empty-state-preset
                        variant="generic"
                        :title="__('No payments recorded yet.')"
                        :action-href="auth()->user()->can('create', App\Models\Payment::class) ? route('payments.create') : null"
                        :action-label="__('Record :label', ['label' => crm_term('payment')])"
                    />
                @endif
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($payments as $payment)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('payments.show', $payment) }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">{{ $payment->number }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-ink-heading hover:text-primary-700">{{ $payment->invoice->number }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $payment->customer->display_name }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $payment->payment_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm text-ink-muted">{{ $payment->method_label }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-ink-heading">{{ $payment->formatted_amount }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($payments->hasPages())
            <x-slot:pagination>
                {{ $payments->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
