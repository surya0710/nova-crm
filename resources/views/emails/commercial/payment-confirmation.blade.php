<x-mail::message>
# {{ __('Payment received — :number', ['number' => $payment->number]) }}

{{ __('Hello :name,', ['name' => $payment->customer->name ?? __('there')]) }}

{{ __('We received your payment of :amount on :date.', [
    'amount' => $payment->formatted_amount,
    'date' => $payment->payment_date?->format('M j, Y'),
]) }}

@if ($invoice)
{{ __('Allocated to invoice :number.', ['number' => $invoice->number]) }}
@endif

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
