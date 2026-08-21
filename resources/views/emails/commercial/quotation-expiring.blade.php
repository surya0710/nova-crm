<x-mail::message>
# {{ __('Quotation :number expires soon', ['number' => $quotation->number]) }}

{{ __('Hello :name,', ['name' => $quotation->customer->name ?? __('there')]) }}

{{ __('Quotation :number for :amount is valid until :date.', [
    'number' => $quotation->number,
    'amount' => $quotation->formatted_total,
    'date' => $quotation->valid_until?->format('M j, Y'),
]) }}

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
