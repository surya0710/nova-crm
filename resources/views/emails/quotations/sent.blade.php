<x-mail::message>
# {{ __('Quotation :number', ['number' => $quotation->number]) }}

@if ($quotation->title)
**{{ $quotation->title }}**
@endif

{{ __('Hello :name,', ['name' => $quotation->customer->name]) }}

@if ($personalMessage)
{{ $personalMessage }}
@else
{{ __('Please find your quotation details below.') }}
@endif

<x-mail::table>
| {{ __('Description') }} | {{ __('Qty') }} | {{ __('Price') }} | {{ __('Total') }} |
|:--|--:|--:|--:|
@foreach ($quotation->items as $item)
| {{ $item->description }} | {{ number_format((float) $item->quantity, 2) }} | {{ number_format((float) $item->unit_price, 2) }} | {{ number_format((float) $item->line_total, 2) }} |
@endforeach
</x-mail::table>

**{{ __('Subtotal') }}:** {{ number_format((float) $quotation->subtotal, 2) }} {{ $quotation->currency }}

@if ((float) $quotation->discount_amount > 0)
**{{ __('Discount') }}:** -{{ number_format((float) $quotation->discount_amount, 2) }} {{ $quotation->currency }}
@endif

@if ((float) $quotation->tax_total > 0)
**{{ __('Tax') }}:** {{ number_format((float) $quotation->tax_total, 2) }} {{ $quotation->currency }}
@endif

**{{ __('Total') }}:** {{ $quotation->formatted_total }}

@if ($quotation->valid_until)
{{ __('Valid until') }}: {{ $quotation->valid_until->format('M j, Y') }}
@endif

@if ($quotation->notes)
---

**{{ __('Terms & Notes') }}**

{{ $quotation->notes }}
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
