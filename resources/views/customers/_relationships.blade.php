@php
    $counts = $hub['counts'] ?? [];
    $value = $hub['value'] ?? [];
    $fmt = function (?float $amount) use ($value) {
        if ($amount === null) {
            return '—';
        }

        return number_format($amount, 2).' '.($value['currency'] ?? '');
    };
@endphp

<x-entity.section class="scroll-mt-24" :title="__('Value & activity')" :subtitle="__('Lifecycle summary for this company')">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        <x-ui.stat-card :label="__('Open pipeline')" :value="$fmt($value['open_pipeline'] ?? 0)" />
        <x-ui.stat-card :label="__('Won value')" :value="$fmt($value['won_value'] ?? 0)" />
        <x-ui.stat-card :label="__('Invoiced')" :value="$fmt($value['invoiced'] ?? null)" />
        <x-ui.stat-card :label="__('Outstanding')" :value="$fmt($value['outstanding'] ?? null)" />
        <x-ui.stat-card :label="__('Contacts')" :value="(string) ($counts['contacts'] ?? 0)" />
        <x-ui.stat-card :label="__('Open tickets')" :value="(string) ($counts['tickets'] ?? 0)" />
    </div>
</x-entity.section>

<nav class="flex gap-2 overflow-x-auto pb-1 text-xs" aria-label="{{ __('Company record') }}">
    @foreach ([
        'contacts' => __('Contacts'),
        'opportunities' => __('Opportunities'),
        'quotations' => __('Quotations'),
        'sales-orders' => __('Sales orders'),
        'invoices' => __('Invoices'),
        'payments' => __('Payments'),
        'tickets' => __('Tickets'),
        'activity' => __('Activities'),
    ] as $anchor => $label)
        <a href="#{{ $anchor }}" class="shrink-0 rounded-full border border-line px-3 py-1.5 text-ink hover:border-primary-200 hover:text-primary-700">{{ $label }}</a>
    @endforeach
</nav>

<x-entity.section id="contacts" class="scroll-mt-24" :title="__('Contacts')">
    <x-slot:actions>
        @can('update', $customer)
            <x-ui.button :href="route('customers.contacts.create', $customer)" variant="secondary" size="sm">{{ __('Add contact') }}</x-ui.button>
        @endcan
    </x-slot:actions>
    @if (($hub['contacts'] ?? collect())->isEmpty())
        <x-ui.empty-state-preset
            variant="contacts"
            :title="__('No contacts yet')"
            :description="__('Add people at this company — primary contact, decision maker, and billing stakeholders.')"
            :action-href="auth()->user()->can('update', $customer) ? route('customers.contacts.create', $customer) : null"
            :action-label="__('Add contact')"
        />
    @else
        <x-tables.table :columns="[__('Name'), __('Title'), __('Email'), __('Role')]" :sticky="false">
            @foreach ($hub['contacts'] as $contact)
                <tr>
                    <td class="px-4 py-2 text-sm">
                        <a href="{{ route('contacts.show', $contact) }}" class="font-medium text-primary-600 hover:text-primary-700">{{ $contact->name }}</a>
                    </td>
                    <td class="px-4 py-2 text-sm text-ink-muted">{{ $contact->title ?? '—' }}</td>
                    <td class="px-4 py-2 text-sm text-ink-muted">{{ $contact->email ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @if ($contact->is_primary)
                            <x-ui.badge variant="primary">{{ __('Primary') }}</x-ui.badge>
                        @endif
                        @if ($contact->is_decision_maker)
                            <x-ui.badge variant="info">{{ __('Decision maker') }}</x-ui.badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-tables.table>
    @endif
</x-entity.section>

@can('viewAny', App\Models\Opportunity::class)
<x-entity.section id="opportunities" class="scroll-mt-24" :title="__('Opportunities')">
    @if (($hub['opportunities'] ?? collect())->isEmpty())
        <p class="py-4 text-center text-sm text-ink-muted">{{ __('No opportunities linked yet.') }}</p>
    @else
        <x-tables.table :columns="[__('Opportunity'), __('Stage'), ['label' => __('Amount'), 'align' => 'right']]" :sticky="false">
            @foreach ($hub['opportunities'] as $opportunity)
                <tr>
                    <td class="px-4 py-2 text-sm"><a href="{{ route('pipeline.show', $opportunity) }}" class="text-primary-600 hover:text-primary-700">{{ $opportunity->title }}</a></td>
                    <td class="px-4 py-2 text-sm text-ink-muted">{{ $opportunity->stage_label }}</td>
                    <td class="px-4 py-2 text-right text-sm">{{ number_format((float) $opportunity->amount, 2) }} {{ $opportunity->currency }}</td>
                </tr>
            @endforeach
        </x-tables.table>
    @endif
</x-entity.section>
@endcan

@can('viewAny', App\Models\Quotation::class)
<x-entity.section id="quotations" class="scroll-mt-24" :title="__('Quotations')">
    @if (($hub['quotations'] ?? collect())->isEmpty())
        <p class="py-4 text-center text-sm text-ink-muted">{{ __('No quotations yet.') }}</p>
    @else
        <x-tables.table :columns="[__('Number'), __('Status'), ['label' => __('Total'), 'align' => 'right']]" :sticky="false">
            @foreach ($hub['quotations'] as $quotation)
                <tr>
                    <td class="px-4 py-2 text-sm"><a href="{{ route('quotations.show', $quotation) }}" class="text-primary-600 hover:text-primary-700">{{ $quotation->number }}</a></td>
                    <td class="px-4 py-2 text-sm text-ink-muted">{{ $quotation->status_label ?? $quotation->status }}</td>
                    <td class="px-4 py-2 text-right text-sm">{{ number_format((float) $quotation->total, 2) }} {{ $quotation->currency }}</td>
                </tr>
            @endforeach
        </x-tables.table>
    @endif
</x-entity.section>
@endcan

@can('viewAny', App\Models\SalesOrder::class)
<x-entity.section id="sales-orders" class="scroll-mt-24" :title="__('Sales orders')">
    @if (($hub['sales_orders'] ?? collect())->isEmpty())
        <p class="py-4 text-center text-sm text-ink-muted">{{ __('No sales orders yet.') }}</p>
    @else
        <x-tables.table :columns="[__('Number'), __('Status'), ['label' => __('Total'), 'align' => 'right']]" :sticky="false">
            @foreach ($hub['sales_orders'] as $order)
                <tr>
                    <td class="px-4 py-2 text-sm"><a href="{{ route('sales-orders.show', $order) }}" class="text-primary-600 hover:text-primary-700">{{ $order->number }}</a></td>
                    <td class="px-4 py-2 text-sm text-ink-muted">{{ $order->status_label ?? $order->status }}</td>
                    <td class="px-4 py-2 text-right text-sm">{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</td>
                </tr>
            @endforeach
        </x-tables.table>
    @endif
</x-entity.section>
@endcan

@can('viewAny', App\Models\Invoice::class)
<x-entity.section id="invoices" class="scroll-mt-24" :title="__('Invoices')">
    @if (($hub['invoices'] ?? collect())->isEmpty())
        <p class="py-4 text-center text-sm text-ink-muted">{{ __('No invoices yet.') }}</p>
    @else
        <x-tables.table :columns="[__('Number'), __('Status'), ['label' => __('Total'), 'align' => 'right']]" :sticky="false">
            @foreach ($hub['invoices'] as $invoice)
                <tr>
                    <td class="px-4 py-2 text-sm"><a href="{{ route('invoices.show', $invoice) }}" class="text-primary-600 hover:text-primary-700">{{ $invoice->number }}</a></td>
                    <td class="px-4 py-2 text-sm text-ink-muted">{{ $invoice->status_label }}</td>
                    <td class="px-4 py-2 text-right text-sm">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
                </tr>
            @endforeach
        </x-tables.table>
    @endif
</x-entity.section>
@endcan

@can('viewAny', App\Models\Payment::class)
<x-entity.section id="payments" class="scroll-mt-24" :title="__('Payments')">
    @if (($hub['payments'] ?? collect())->isEmpty())
        <p class="py-4 text-center text-sm text-ink-muted">{{ __('No payments yet.') }}</p>
    @else
        <x-tables.table :columns="[__('Number'), __('Date'), ['label' => __('Amount'), 'align' => 'right']]" :sticky="false">
            @foreach ($hub['payments'] as $payment)
                <tr>
                    <td class="px-4 py-2 text-sm"><a href="{{ route('payments.show', $payment) }}" class="text-primary-600 hover:text-primary-700">{{ $payment->number }}</a></td>
                    <td class="px-4 py-2 text-sm text-ink-muted">{{ optional($payment->payment_date)->format('M j, Y') ?? '—' }}</td>
                    <td class="px-4 py-2 text-right text-sm">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
                </tr>
            @endforeach
        </x-tables.table>
    @endif
</x-entity.section>
@endcan

<x-entity.section id="tickets" class="scroll-mt-24" :title="__('Tickets')">
    <x-slot:actions>
        @can('update', $customer)
            <x-ui.button :href="route('customers.tickets.create', $customer)" variant="secondary" size="sm">{{ __('New ticket') }}</x-ui.button>
        @endcan
    </x-slot:actions>
    @if (($hub['tickets'] ?? collect())->isEmpty())
        <x-ui.empty-state-preset
            variant="tickets"
            :title="__('No tickets yet')"
            :description="__('Track support requests against this company.')"
            :action-href="auth()->user()->can('update', $customer) ? route('customers.tickets.create', $customer) : null"
            :action-label="__('New ticket')"
        />
    @else
        <x-tables.table :columns="[__('Ticket'), __('Status'), __('Priority')]" :sticky="false">
            @foreach ($hub['tickets'] as $ticket)
                <tr>
                    <td class="px-4 py-2 text-sm"><a href="{{ route('tickets.show', $ticket) }}" class="text-primary-600 hover:text-primary-700">{{ $ticket->number }} · {{ $ticket->subject }}</a></td>
                    <td class="px-4 py-2 text-sm text-ink-muted">{{ $ticket->status_label }}</td>
                    <td class="px-4 py-2 text-sm text-ink-muted">{{ $ticket->priority_label }}</td>
                </tr>
            @endforeach
        </x-tables.table>
    @endif
</x-entity.section>
