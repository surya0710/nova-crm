@php
    $statusVariant = ['draft' => 'neutral', 'issued' => 'info', 'applied' => 'success', 'cancelled' => 'danger'];
@endphp
<x-app-layout>
    <x-flash-messages />
    <x-layouts.entity-detail :title="$note->number" :subtitle="$note->type_label">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => $note->type_label, 'href' => route($note->routePrefix().'.index')],
                ['label' => $note->number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-ui.button :href="route($note->routePrefix().'.pdf', $note)" variant="secondary" size="sm">{{ __('Download PDF') }}</x-ui.button>
            @can('update', $note)
                <x-ui.button :href="route($note->routePrefix().'.edit', $note)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @can('issue', $note)
                <form method="POST" action="{{ route($note->routePrefix().'.issue', $note) }}">@csrf<x-ui.button type="submit" variant="primary" size="sm">{{ __('Issue') }}</x-ui.button></form>
            @endcan
            @can('apply', $note)
                <form method="POST" action="{{ route($note->routePrefix().'.apply', $note) }}">@csrf<x-ui.button type="submit" variant="primary" size="sm">{{ __('Apply to invoice') }}</x-ui.button></form>
            @endcan
            @if ($note->canCancel())
                <form method="POST" action="{{ route($note->routePrefix().'.cancel', $note) }}" onsubmit="return confirm('{{ __('Cancel this note?') }}')">@csrf<x-ui.button type="submit" variant="danger" size="sm">{{ __('Cancel') }}</x-ui.button></form>
            @endif
            @can('delete', $note)
                <form method="POST" action="{{ route($note->routePrefix().'.destroy', $note) }}" onsubmit="return confirm('{{ __('Delete this note?') }}')">@csrf @method('DELETE')<x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button></form>
            @endcan
        </x-slot:actions>
        <x-slot:tabs>
            <x-ui.badge :variant="$statusVariant[$note->status] ?? 'neutral'">{{ $note->status_label }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Line items')">
            <x-tables.table :columns="[__('Description'), ['label' => __('Qty'), 'align' => 'right'], ['label' => __('Price'), 'align' => 'right'], ['label' => __('Total'), 'align' => 'right']]" :sticky="false">
                @foreach ($note->items as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-right text-sm">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
            @include('commercial._tax-totals', ['document' => $note])
        </x-entity.section>

        @can('send', $note)
            <x-client-email-form
                :action="route($note->routePrefix().'.send', $note)"
                :email="old('email', $note->customer->email)"
                :submit-label="__('Send note')"
                :description="__('Email this note. The sender is CC’d.')"
                :organization="$organization"
                :missing-email-hint="! $note->customer->email"
                :show-cc="true"
                :show-bcc="true"
                :cc-sender="true"
                module="invoices"
                :related="$note"
            />
        @endcan

        <x-slot:aside>
            <x-entity.section :title="__('Details')">
                <x-entity.definition-list>
                    <x-entity.definition-item :label="__('Customer')" :span="2">
                        <a class="text-primary-600" href="{{ route('customers.show', $note->customer) }}">{{ $note->customer->display_name }}</a>
                    </x-entity.definition-item>
                    @if ($note->invoice)
                        <x-entity.definition-item :label="__('Invoice')" :span="2">
                            <a class="text-primary-600" href="{{ route('invoices.show', $note->invoice) }}">{{ $note->invoice->number }}</a>
                            <p class="mt-1 text-xs text-ink-muted">{{ __('Invoice stored total remains :amount', ['amount' => $note->invoice->formatted_total]) }}</p>
                        </x-entity.definition-item>
                    @endif
                    <x-entity.definition-item :label="__('Reason')">{{ $note->reason_label ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Issue date')">{{ $note->issue_date?->format('M j, Y') }}</x-entity.definition-item>
                    @if ((float) $note->applied_amount > 0)
                        <x-entity.definition-item :label="__('Applied')">{{ number_format((float) $note->applied_amount, 2) }} {{ $note->currency }}</x-entity.definition-item>
                    @endif
                </x-entity.definition-list>
            </x-entity.section>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
