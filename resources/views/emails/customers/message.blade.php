<x-mail::message>
# {{ $mailSubject }}

{{ __('Hello :name,', ['name' => $customer->name]) }}

@if ($personalMessage)
{{ $personalMessage }}
@else
{{ __('Please see the message from our team below.') }}
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
