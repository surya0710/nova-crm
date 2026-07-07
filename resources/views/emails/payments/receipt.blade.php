<x-mail::message>
# {{ __('Payment receipt :number', ['number' => $payment->number]) }}

{{ __('Hello :name,', ['name' => $payment->customer->name]) }}

@if ($personalMessage)
{{ $personalMessage }}
@else
{{ __('Thank you for your payment. Here are the receipt details.') }}
@endif

<x-mail::table>
| | |
|:--|--:|
| {{ __('Amount') }} | **{{ $payment->formatted_amount }}** |
| {{ __('Date') }} | {{ $payment->payment_date->format('M j, Y') }} |
| {{ __('Method') }} | {{ $payment->method_label }} |
@if ($payment->reference)
| {{ __('Reference') }} | {{ $payment->reference }} |
@endif
| {{ crm_term('invoice') }} | {{ $payment->invoice->number }} |
</x-mail::table>

@if ($payment->notes)
**{{ __('Notes') }}**

{{ $payment->notes }}
@endif

{{ __('Thanks,') }}<br>
{{ $organization->name }}
@if ($organization->email)
<br>{{ $organization->email }}
@endif
@if ($organization->phone)
<br>{{ $organization->phone }}
@endif
</x-mail::message>
