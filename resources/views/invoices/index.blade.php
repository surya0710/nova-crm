@php
    $statusVariant = [
        'draft' => 'neutral',
        'issued' => 'info',
        'partially_paid' => 'warning',
        'paid' => 'success',
        'cancelled' => 'neutral',
    ];
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        crm_term('invoice'),
        crm_term('customer'),
        ['label' => __('Due Date'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Total'), 'align' => 'right'],
        ['label' => __('Balance'), 'align' => 'right', 'class' => 'hidden sm:table-cell'],
        __('Status'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="crm_term('invoices')"
        :subtitle="__('Create and manage billing documents')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('invoices'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\Invoice::class)
                <x-ui.button :href="route('invoices.create')" variant="primary" size="sm">{{ __('New :label', ['label' => crm_term('invoice')]) }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label for="invoices-search" class="sr-only">{{ __('Search invoices') }}</label>
                    <x-forms.input id="invoices-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search number, title, customer…') }}" />
                </div>
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('invoices.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <div class="flex gap-2">
                    <x-forms.select name="customer_id" class="flex-1" aria-label="{{ crm_term('customer') }}">
                        <option value="">{{ __('All :label', ['label' => strtolower(crm_term('customers'))]) }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->display_name }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($invoices->isEmpty())
            <x-ui.card>
                @if (! empty($filters['search']) || ! empty($filters['status']) || ! empty($filters['customer_id']))
                    <x-ui.empty-state-preset variant="search" />
                @else
                    <x-ui.empty-state-preset
                        variant="generic"
                        :title="__('No invoices yet')"
                        :action-href="auth()->user()->can('create', App\Models\Invoice::class) ? route('invoices.create') : null"
                        :action-label="__('New :label', ['label' => crm_term('invoice')])"
                    />
                @endif
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($invoices as $invoice)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('invoices.show', $invoice) }}" class="group block">
                                <p class="text-sm font-semibold text-ink-heading group-hover:text-primary-700">{{ $invoice->number }}</p>
                                @if ($invoice->title)
                                    <p class="mt-0.5 text-xs text-ink-muted">{{ $invoice->title }}</p>
                                @endif
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $invoice->customer->display_name }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-heading">{{ $invoice->formatted_total }}</td>
                        <td class="px-4 py-3 hidden sm:table-cell text-right text-sm text-ink-muted">{{ $invoice->formatted_balance_due }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$statusVariant[$invoice->status] ?? 'neutral'">{{ $invoice->status_label }}</x-ui.badge>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($invoices->hasPages())
            <x-slot:pagination>
                {{ $invoices->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
