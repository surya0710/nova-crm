<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $payment->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $organization?->name ?? __('Organization') }}</h1>
    <div class="meta">
        <div>{{ __('Payment receipt') }}: {{ $payment->number }}</div>
        <div>{{ __('Date') }}: {{ $payment->payment_date?->format('M j, Y') }}</div>
        <div>{{ __('Amount') }}: {{ $payment->formatted_amount }}</div>
    </div>

    <h2>{{ __('Received from') }}</h2>
    <table>
        <tr><th>{{ __('Customer') }}</th><td>{{ $payment->customer?->display_name }}</td></tr>
        @if ($payment->invoice)
            <tr><th>{{ __('Invoice') }}</th><td>{{ $payment->invoice->number }}</td></tr>
        @endif
        <tr><th>{{ __('Method') }}</th><td>{{ $payment->method_label }}</td></tr>
        @if ($payment->reference)
            <tr><th>{{ __('Reference') }}</th><td>{{ $payment->reference }}</td></tr>
        @endif
        @if ($payment->bank_name)
            <tr><th>{{ __('Bank') }}</th><td>{{ $payment->bank_name }}</td></tr>
        @endif
        @if ($payment->bank_account_name)
            <tr><th>{{ __('Account name') }}</th><td>{{ $payment->bank_account_name }}</td></tr>
        @endif
        @if ($payment->bank_account_number)
            <tr><th>{{ __('Account number') }}</th><td>{{ $payment->bank_account_number }}</td></tr>
        @endif
        @if ($payment->bank_ifsc)
            <tr><th>{{ __('IFSC') }}</th><td>{{ $payment->bank_ifsc }}</td></tr>
        @endif
    </table>

    @if ($payment->notes)
        <h2>{{ __('Notes') }}</h2>
        <div>{{ $payment->notes }}</div>
    @endif
</body>
</html>
