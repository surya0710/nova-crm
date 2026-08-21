<x-mail::message>
# {{ $note->type_label }} {{ $note->number }}

{{ __('Hello :name,', ['name' => $note->customer->name]) }}

@if ($personalMessage)
{{ $personalMessage }}
@else
{{ __('Please find your :type below.', ['type' => strtolower($note->type_label)]) }}
@endif

@if ($note->invoice)
{{ __('This note is recorded against invoice :number. The original invoice total is unchanged.', ['number' => $note->invoice->number]) }}
@endif

<x-mail::table>
| {{ __('Description') }} | {{ __('Qty') }} | {{ __('Total') }} |
|:--|--:|--:|
@foreach ($note->items as $item)
| {{ $item->description }} | {{ number_format((float) $item->quantity, 2) }} | {{ number_format((float) $item->line_total, 2) }} |
@endforeach
</x-mail::table>

**{{ __('Total') }}:** {{ $note->formatted_total }}

@include('emails.partials.signature')

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
