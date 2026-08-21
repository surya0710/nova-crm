<x-mail::message>
# {{ $mailSubject }}

@if ($recipientName)
{{ __('Hello :name,', ['name' => $recipientName]) }}
@else
{{ __('Hello,') }}
@endif

@if ($body)
{{ $body }}
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
