<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $note->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .totals { width: 280px; margin-left: auto; }
    </style>
</head>
<body>
    <h1>{{ $organization?->name }}</h1>
    <p>{{ $note->type_label }}: {{ $note->number }}</p>
    <p>{{ __('Date') }}: {{ $note->issue_date?->format('M j, Y') }}</p>
    @if ($note->invoice)<p>{{ __('Against invoice') }}: {{ $note->invoice->number }}</p>@endif
    <p>{{ __('Customer') }}: {{ $note->customer?->display_name }}</p>
    @if ($note->reason_label)<p>{{ __('Reason') }}: {{ $note->reason_label }}</p>@endif
    <table>
        <thead><tr><th>{{ __('Description') }}</th><th class="right">{{ __('Qty') }}</th><th class="right">{{ __('Price') }}</th><th class="right">{{ __('Total') }}</th></tr></thead>
        <tbody>
        @foreach ($note->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <table class="totals">
        <tr><th>{{ __('Subtotal') }}</th><td class="right">{{ number_format((float) $note->subtotal, 2) }}</td></tr>
        <tr><th>{{ __('Tax') }}</th><td class="right">{{ number_format((float) $note->tax_total, 2) }}</td></tr>
        <tr><th>{{ __('Total') }}</th><td class="right">{{ $note->formatted_total }}</td></tr>
    </table>
</body>
</html>
