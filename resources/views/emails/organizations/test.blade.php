<x-mail::message>
# {{ __('Email configuration test') }}

{{ __('This is a test message from :name.', ['name' => $organization->name]) }}

{{ __('If you received this email, your organization SMTP settings are working correctly.') }}

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
