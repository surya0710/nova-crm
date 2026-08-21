<x-mail::message>
# {{ __('Invoice :number is due soon', ['number' => $invoice->number]) }}

{{ __('Hello :name,', ['name' => $invoice->customer->name ?? __('there')]) }}

{{ __('Invoice :number for :amount is due on :date.', [
    'number' => $invoice->number,
    'amount' => $invoice->formatted_total,
    'date' => $invoice->due_date?->format('M j, Y'),
]) }}

{{ __('Outstanding balance') }}: {{ number_format($invoice->effective_balance, 2) }} {{ $invoice->currency }}

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
