<x-mail::message>
# {{ __('Employee Workspace enabled') }}

{{ __('Hello :name,', ['name' => $user->name]) }}

{{ __('Your Employee Workspace access for :organization has been enabled.', ['organization' => $organization->name]) }}

<x-mail::button :url="$portalUrl">
{{ __('Open Employee Workspace') }}
</x-mail::button>

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
