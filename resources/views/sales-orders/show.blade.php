@php
    $statusVariant = [
        'draft' => 'neutral',
        'confirmed' => 'info',
        'processing' => 'primary',
        'partially_fulfilled' => 'warning',
        'fulfilled' => 'success',
        'cancelled' => 'danger',
    ];

    $allowedTransitions = $salesOrder->allowedTransitions();
    $selectableStatuses = array_unique(array_merge([$salesOrder->status], $allowedTransitions));
    $quickActions = [
        'confirmed' => ['label' => __('Confirm'), 'variant' => 'primary'],
        'processing' => ['label' => __('Processing'), 'variant' => 'secondary'],
        'partially_fulfilled' => ['label' => __('Partially Fulfilled'), 'variant' => 'secondary'],
        'fulfilled' => ['label' => __('Fulfilled'), 'variant' => 'primary'],
        'cancelled' => ['label' => __('Cancel'), 'variant' => 'danger'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$salesOrder->number"
        :subtitle="collect([$salesOrder->customer->display_name, $salesOrder->title])->filter()->implode(' · ')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('sales_orders'), 'href' => route('sales-orders.index')],
                ['label' => $salesOrder->number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('view', $salesOrder)
                <x-ui.button :href="route('sales-orders.pdf', $salesOrder)" variant="secondary" size="sm">{{ __('Download PDF') }}</x-ui.button>
            @endcan
            @can('update', $salesOrder)
                <x-ui.button :href="route('sales-orders.edit', $salesOrder)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @if ($salesOrder->canConvert())
                @can('convert', $salesOrder)
                    @if ($salesOrder->invoice && $salesOrder->invoice->status !== 'cancelled')
                        <x-ui.button :href="route('invoices.show', $salesOrder->invoice)" variant="secondary" size="sm">{{ __('View :label', ['label' => crm_term('invoice')]) }}</x-ui.button>
                    @else
                        <form method="POST" action="{{ route('sales-orders.convert', $salesOrder) }}">
                            @csrf
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Generate :label', ['label' => crm_term('invoice')]) }}</x-ui.button>
                        </form>
                    @endif
                @endcan
            @endif
            @can('delete', $salesOrder)
                <form method="POST" action="{{ route('sales-orders.destroy', $salesOrder) }}" onsubmit="return confirm('{{ __('Delete this sales order?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge :variant="$statusVariant[$salesOrder->status] ?? 'neutral'">{{ $salesOrder->status_label }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Line Items')">
            <x-tables.table :columns="[
                __('Description'),
                __('SKU'),
                ['label' => __('Qty'), 'align' => 'right'],
                ['label' => __('Price'), 'align' => 'right'],
                ['label' => __('Tax'), 'align' => 'right'],
                ['label' => __('Total'), 'align' => 'right'],
            ]" :sticky="false">
                @foreach ($salesOrder->items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="text-sm text-ink-heading">{{ $item->description }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ collect([$item->hsn_sac, $item->unit])->filter()->implode(' · ') }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $item->sku ?: ($item->product->sku ?? '—') }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-muted">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-muted">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-muted">{{ number_format((float) $item->tax_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-ink-heading">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
            @include('commercial._tax-totals', ['document' => $salesOrder])
        </x-entity.section>

        @if ($salesOrder->notes)
            <x-entity.section :title="__('Notes')">
                <div class="text-sm whitespace-pre-line text-ink">{{ $salesOrder->notes }}</div>
            </x-entity.section>
        @endif

        @if ($salesOrder->terms)
            <x-entity.section :title="__('Terms & Conditions')">
                <div class="text-sm whitespace-pre-line text-ink">{{ $salesOrder->terms }}</div>
            </x-entity.section>
        @endif

        @can('send', $salesOrder)
            <x-client-email-form
                :action="route('sales-orders.send', $salesOrder)"
                :email="old('email', $salesOrder->customer->email)"
                :submit-label="__('Send Sales Order')"
                :description="__('Email this sales order to your customer')"
                :organization="$organization"
                :missing-email-hint="! $salesOrder->customer->email"
                :show-cc="true"
                :show-bcc="true"
                :cc-sender="true"
                module="sales_orders"
                :related="$salesOrder"
            />
        @endcan

        @can('changeStatus', $salesOrder)
            @if ($allowedTransitions !== [])
                <x-entity.section :title="__('Update Status')">
                    <form method="POST" action="{{ route('sales-orders.status.update', $salesOrder) }}" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        @csrf
                        @method('PATCH')
                        <x-forms.field :label="__('Status')" name="status" class="sm:min-w-[160px]">
                            <x-forms.select
                                id="sales-order-status"
                                name="status"
                                onchange="this.form.submit()"
                            >
                                @foreach ($selectableStatuses as $value)
                                    <option value="{{ $value }}" @selected($salesOrder->status === $value)>{{ config('sales_orders.statuses.'.$value) }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                    </form>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($quickActions as $status => $meta)
                            @if (in_array($status, $allowedTransitions, true))
                                <form method="POST" action="{{ route('sales-orders.status.update', $salesOrder) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <x-ui.button type="submit" :variant="$meta['variant']" size="sm">{{ $meta['label'] }}</x-ui.button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </x-entity.section>
            @endif
        @endcan

        <x-attachments-panel
            attachable-type="sales_order"
            :attachable-id="$salesOrder->id"
            :attachments="$salesOrder->attachments"
            :can-upload="auth()->user()->can('attachments.create')"
            :can-delete="auth()->user()->can('attachments.delete')"
        />

        <x-slot:aside>
            <x-entity.section :title="__('Details')">
                <x-entity.definition-list>
                    <x-entity.definition-item :label="__('Customer')" :span="2">
                        <a href="{{ route('customers.show', $salesOrder->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $salesOrder->customer->display_name }}</a>
                    </x-entity.definition-item>
                    @if ($salesOrder->quotation)
                        <x-entity.definition-item :label="crm_term('quotation')" :span="2">
                            <a href="{{ route('quotations.show', $salesOrder->quotation) }}" class="text-primary-600 hover:text-primary-700">{{ $salesOrder->quotation->number }}</a>
                        </x-entity.definition-item>
                    @endif
                    @if ($salesOrder->opportunity)
                        <x-entity.definition-item :label="__('Deal')" :span="2">
                            <a href="{{ route('pipeline.show', $salesOrder->opportunity) }}" class="text-primary-600 hover:text-primary-700">{{ $salesOrder->opportunity->title }}</a>
                        </x-entity.definition-item>
                    @endif
                    <x-entity.definition-item :label="__('Order Date')">{{ $salesOrder->order_date->format('M j, Y') }}</x-entity.definition-item>
                    @if ($salesOrder->expected_delivery_date)
                        <x-entity.definition-item :label="__('Expected Delivery')">{{ $salesOrder->expected_delivery_date->format('M j, Y') }}</x-entity.definition-item>
                    @endif
                    @if ($salesOrder->placeOfSupplyLabel())
                        <x-entity.definition-item :label="__('Place of Supply')" :span="2">{{ $salesOrder->placeOfSupplyLabel() }}</x-entity.definition-item>
                    @endif
                    <x-entity.definition-item :label="__('Created By')">{{ $salesOrder->creator?->name ?? '—' }}</x-entity.definition-item>
                </x-entity.definition-list>
            </x-entity.section>
            <x-ui.button :href="route('sales-orders.index')" variant="link" size="sm">← {{ __('Back to sales orders') }}</x-ui.button>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
