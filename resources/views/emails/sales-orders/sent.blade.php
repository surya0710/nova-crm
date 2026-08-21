<x-mail::message>
# {{ __('Sales order :number', ['number' => $salesOrder->number]) }}

@if ($salesOrder->title)
**{{ $salesOrder->title }}**
@endif

{{ __('Hello :name,', ['name' => $salesOrder->customer->name]) }}

@if ($personalMessage)
{{ $personalMessage }}
@else
{{ __('Please find your sales order details below.') }}
@endif

<x-mail::table>
| {{ __('Description') }} | {{ __('Qty') }} | {{ __('Price') }} | {{ __('Total') }} |
|:--|--:|--:|--:|
@foreach ($salesOrder->items as $item)
| {{ $item->description }} | {{ number_format((float) $item->quantity, 2) }} | {{ number_format((float) $item->unit_price, 2) }} | {{ number_format((float) $item->line_total, 2) }} |
@endforeach
</x-mail::table>

**{{ __('Subtotal') }}:** {{ number_format((float) $salesOrder->subtotal, 2) }} {{ $salesOrder->currency }}

@if ((float) $salesOrder->discount_amount > 0)
**{{ __('Discount') }}:** -{{ number_format((float) $salesOrder->discount_amount, 2) }} {{ $salesOrder->currency }}
@endif

@if ((float) $salesOrder->tax_total > 0)
**{{ __('Tax') }}:** {{ number_format((float) $salesOrder->tax_total, 2) }} {{ $salesOrder->currency }}
@endif

**{{ __('Total') }}:** {{ $salesOrder->formatted_total }}

@if ($salesOrder->expected_delivery_date)
{{ __('Expected delivery') }}: {{ $salesOrder->expected_delivery_date->format('M j, Y') }}
@endif

@if ($salesOrder->notes)
---

**{{ __('Terms & Notes') }}**

{{ $salesOrder->notes }}
@endif

@include('emails.partials.signature')

{{ __('Thanks,') }}<br>
{{ $organization->name }}
@if ($organization->email)
<br>{{ $organization->email }}
@endif
@if ($organization->phone)
<br>{{ $organization->phone }}
@endif
</x-mail::message>
