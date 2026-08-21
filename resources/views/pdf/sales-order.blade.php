<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $salesOrder->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .totals { width: 280px; margin-left: auto; }
        .muted { color: #555; font-size: 11px; }
    </style>
</head>
<body>
    <h1>{{ $organization?->name ?? __('Organization') }}</h1>
    <div class="meta">
        <div>{{ __('Sales order') }}: {{ $salesOrder->number }}</div>
        @if ($salesOrder->title)<div>{{ $salesOrder->title }}</div>@endif
        <div>{{ __('Order date') }}: {{ $salesOrder->order_date?->format('M j, Y') }}</div>
        @if ($salesOrder->expected_delivery_date)
            <div>{{ __('Expected delivery') }}: {{ $salesOrder->expected_delivery_date->format('M j, Y') }}</div>
        @endif
        @if ($salesOrder->quotation)
            <div>{{ __('Quotation') }}: {{ $salesOrder->quotation->number }}</div>
        @endif
        <div>{{ __('Status') }}: {{ $salesOrder->status_label }}</div>
    </div>

    <h2>{{ __('Customer') }}</h2>
    <table>
        <tr><th>{{ __('Name') }}</th><td>{{ $salesOrder->customer?->display_name }}</td></tr>
        @if ($salesOrder->customer?->gstin)
            <tr><th>{{ __('GSTIN') }}</th><td>{{ $salesOrder->customer->gstin }}</td></tr>
        @endif
        @if ($salesOrder->place_of_supply)
            <tr><th>{{ __('Place of supply') }}</th><td>{{ $salesOrder->place_of_supply }} — {{ data_get(config('tax.states.'.$salesOrder->place_of_supply), 'name') }}</td></tr>
        @endif
    </table>

    <h2>{{ __('Line items') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th>{{ __('SKU') }}</th>
                <th>{{ __('HSN/SAC') }}</th>
                <th class="right">{{ __('Qty') }}</th>
                <th class="right">{{ __('Price') }}</th>
                <th class="right">{{ __('Tax') }}</th>
                <th class="right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($salesOrder->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->sku ?: '—' }}</td>
                <td>{{ $item->hsn_sac ?: '—' }}</td>
                <td class="right">{{ number_format((float) $item->quantity, 2) }} {{ $item->unit }}</td>
                <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="right">{{ number_format((float) $item->tax_amount, 2) }}</td>
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><th>{{ __('Subtotal') }}</th><td class="right">{{ number_format((float) $salesOrder->subtotal, 2) }}</td></tr>
        @if ((float) $salesOrder->discount_amount > 0)
            <tr><th>{{ __('Discount') }}</th><td class="right">-{{ number_format((float) $salesOrder->discount_amount, 2) }}</td></tr>
        @endif
        <tr><th>{{ __('Taxable') }}</th><td class="right">{{ number_format((float) $salesOrder->taxable_amount, 2) }}</td></tr>
        @if ((float) $salesOrder->cgst_amount > 0)
            <tr><th>{{ __('CGST') }}</th><td class="right">{{ number_format((float) $salesOrder->cgst_amount, 2) }}</td></tr>
        @endif
        @if ((float) $salesOrder->sgst_amount > 0)
            <tr><th>{{ __('SGST') }}</th><td class="right">{{ number_format((float) $salesOrder->sgst_amount, 2) }}</td></tr>
        @endif
        @if ((float) $salesOrder->igst_amount > 0)
            <tr><th>{{ __('IGST') }}</th><td class="right">{{ number_format((float) $salesOrder->igst_amount, 2) }}</td></tr>
        @endif
        @if ((float) $salesOrder->utgst_amount > 0)
            <tr><th>{{ __('UTGST') }}</th><td class="right">{{ number_format((float) $salesOrder->utgst_amount, 2) }}</td></tr>
        @endif
        @if ((float) $salesOrder->cess_amount > 0)
            <tr><th>{{ __('Cess') }}</th><td class="right">{{ number_format((float) $salesOrder->cess_amount, 2) }}</td></tr>
        @endif
        @if ((float) $salesOrder->other_tax_amount > 0 && (float) $salesOrder->cgst_amount + (float) $salesOrder->igst_amount === 0.0)
            <tr><th>{{ __('Tax') }}</th><td class="right">{{ number_format((float) $salesOrder->other_tax_amount, 2) }}</td></tr>
        @endif
        @if ((float) $salesOrder->shipping_amount > 0)
            <tr><th>{{ __('Shipping') }}</th><td class="right">{{ number_format((float) $salesOrder->shipping_amount, 2) }}</td></tr>
        @endif
        <tr><th>{{ __('Grand total') }}</th><td class="right">{{ $salesOrder->formatted_total }}</td></tr>
    </table>

    @if ($salesOrder->terms)
        <h2>{{ __('Terms & conditions') }}</h2>
        <div>{{ $salesOrder->terms }}</div>
    @endif

    @if ($salesOrder->notes)
        <h2>{{ __('Notes') }}</h2>
        <div>{{ $salesOrder->notes }}</div>
    @endif
</body>
</html>
