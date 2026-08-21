<x-mail::message>
# {{ __('Invoice :number is overdue', ['number' => $invoice->number]) }}

{{ __('Hello :name,', ['name' => $invoice->customer->name ?? __('there')]) }}

{{ __('Invoice :number was due on :date and still has an outstanding balance of :amount.', [
    'number' => $invoice->number,
    'date' => $invoice->due_date?->format('M j, Y'),
    'amount' => number_format($invoice->effective_balance, 2).' '.$invoice->currency,
]) }}

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
