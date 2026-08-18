<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .totals { width: 280px; margin-left: auto; }
        .muted { color: #555; font-size: 11px; }
        .parties td { width: 50%; }
    </style>
</head>
<body>
    @php
        $billing = $invoice->resolvedBillingSnapshot();
        $shipping = $invoice->resolvedShippingSnapshot();
    @endphp
    <h1>{{ $organization?->name ?? __('Organization') }}</h1>
    <div class="meta">
        <div>{{ __('Invoice') }}: {{ $invoice->number }}</div>
        @if ($invoice->title)<div>{{ $invoice->title }}</div>@endif
        <div>{{ __('Issue date') }}: {{ $invoice->issue_date?->format('M j, Y') }}</div>
        @if ($invoice->due_date)
            <div>{{ __('Due date') }}: {{ $invoice->due_date->format('M j, Y') }}</div>
        @endif
        <div>{{ __('Status') }}: {{ $invoice->status_label }}</div>
        @if ($invoice->placeOfSupplyLabel())
            <div>{{ __('Place of supply') }}: {{ $invoice->placeOfSupplyLabel() }}</div>
        @endif
    </div>

    <table class="parties">
        <tr>
            <th>{{ __('Bill to') }}</th>
            <th>{{ __('Ship to') }}</th>
        </tr>
        <tr>
            <td>
                <strong>{{ $billing['name'] ?? $invoice->customer?->display_name }}</strong><br>
                @foreach ($invoice->billingAddressLines() as $line)
                    {{ $line }}<br>
                @endforeach
                @if (! empty($billing['gstin']))
                    <span class="muted">{{ __('GSTIN') }}: {{ $billing['gstin'] }}</span>
                @endif
            </td>
            <td>
                @if (! empty($shipping['same_as_billing']))
                    {{ __('Same as billing') }}
                @else
                    @foreach ($invoice->shippingAddressLines() as $line)
                        {{ $line }}<br>
                    @endforeach
                @endif
            </td>
        </tr>
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
                <th class="right">{{ __('Discount') }}</th>
                <th class="right">{{ __('Tax') }}</th>
                <th class="right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->sku ?: '—' }}</td>
                <td>{{ $item->hsn_sac ?: '—' }}</td>
                <td class="right">{{ number_format((float) $item->quantity, 2) }} {{ $item->unit }}</td>
                <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="right">{{ number_format((float) $item->discount_percent, 2) }}%</td>
                <td class="right">{{ number_format((float) $item->tax_amount, 2) }}</td>
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><th>{{ __('Subtotal') }}</th><td class="right">{{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
        @if ((float) $invoice->discount_amount > 0)
            <tr><th>{{ __('Discount') }}</th><td class="right">-{{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>
        @endif
        <tr><th>{{ __('Taxable') }}</th><td class="right">{{ number_format((float) $invoice->taxable_amount, 2) }}</td></tr>
        @if ((float) $invoice->cgst_amount > 0)
            <tr><th>{{ __('CGST') }}</th><td class="right">{{ number_format((float) $invoice->cgst_amount, 2) }}</td></tr>
        @endif
        @if ((float) $invoice->sgst_amount > 0)
            <tr><th>{{ __('SGST') }}</th><td class="right">{{ number_format((float) $invoice->sgst_amount, 2) }}</td></tr>
        @endif
        @if ((float) $invoice->igst_amount > 0)
            <tr><th>{{ __('IGST') }}</th><td class="right">{{ number_format((float) $invoice->igst_amount, 2) }}</td></tr>
        @endif
        @if ((float) $invoice->utgst_amount > 0)
            <tr><th>{{ __('UTGST') }}</th><td class="right">{{ number_format((float) $invoice->utgst_amount, 2) }}</td></tr>
        @endif
        @if ((float) $invoice->cess_amount > 0)
            <tr><th>{{ __('Cess') }}</th><td class="right">{{ number_format((float) $invoice->cess_amount, 2) }}</td></tr>
        @endif
        @if ((float) $invoice->other_tax_amount > 0 && (float) $invoice->cgst_amount + (float) $invoice->igst_amount === 0.0)
            <tr><th>{{ __('Tax') }}</th><td class="right">{{ number_format((float) $invoice->other_tax_amount, 2) }}</td></tr>
        @endif
        @if ((float) $invoice->shipping_amount > 0)
            <tr><th>{{ __('Shipping / other') }}</th><td class="right">{{ number_format((float) $invoice->shipping_amount, 2) }}</td></tr>
        @endif
        <tr><th>{{ __('Grand total') }}</th><td class="right">{{ $invoice->formatted_total }}</td></tr>
        <tr><th>{{ __('Paid') }}</th><td class="right">{{ number_format((float) $invoice->amount_paid, 2) }}</td></tr>
        <tr><th>{{ __('Balance due') }}</th><td class="right">{{ $invoice->formatted_balance_due }}</td></tr>
    </table>

    @if ($invoice->terms)
        <h2>{{ __('Payment terms') }}</h2>
        <div>{{ $invoice->terms }}</div>
    @endif

    @if ($invoice->notes)
        <h2>{{ __('Notes') }}</h2>
        <div>{{ $invoice->notes }}</div>
    @endif
</body>
</html>
