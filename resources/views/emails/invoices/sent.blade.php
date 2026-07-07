<x-mail::message>
# {{ crm_term('invoice') }} {{ $invoice->number }}

@if ($invoice->title)
**{{ $invoice->title }}**
@endif

{{ __('Hello :name,', ['name' => $invoice->customer->name]) }}

@if ($personalMessage)
{{ $personalMessage }}
@else
{{ __('Please find your invoice details below.') }}
@endif

<x-mail::table>
| {{ __('Description') }} | {{ __('Qty') }} | {{ __('Price') }} | {{ __('Total') }} |
|:--|--:|--:|--:|
@foreach ($invoice->items as $item)
| {{ $item->description }} | {{ number_format((float) $item->quantity, 2) }} | {{ number_format((float) $item->unit_price, 2) }} | {{ number_format((float) $item->line_total, 2) }} |
@endforeach
</x-mail::table>

**{{ __('Subtotal') }}:** {{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}

@if ((float) $invoice->discount_amount > 0)
**{{ __('Discount') }}:** -{{ number_format((float) $invoice->discount_amount, 2) }} {{ $invoice->currency }}
@endif

@if ((float) $invoice->tax_total > 0)
**{{ __('Tax') }}:** {{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}
@endif

**{{ __('Total Due') }}:** {{ $invoice->formatted_total }}

@if ((float) $invoice->amount_paid > 0)
**{{ __('Amount Paid') }}:** {{ number_format((float) $invoice->amount_paid, 2) }} {{ $invoice->currency }}

**{{ __('Balance Due') }}:** {{ $invoice->formatted_balance_due }}
@endif

@if ($invoice->due_date)
**{{ __('Due Date') }}:** {{ $invoice->due_date->format('M j, Y') }}
@endif

@if ($invoice->notes)
---

**{{ __('Notes') }}**

{{ $invoice->notes }}
@endif

{{ __('Thanks,') }}<br>
{{ $organization->name }}
@if ($organization->email)
<br>{{ $organization->email }}
@endif
</x-mail::message>
