<x-mail::message>
# {{ __('Welcome') }}

{{ __('Hello :name,', ['name' => $user->name]) }}

{{ __('Your account for :organization is active. You can sign in anytime.', ['organization' => $organization->name]) }}

<x-mail::button :url="$loginUrl">
{{ __('Sign in') }}
</x-mail::button>

{{ __('If you have Employee Workspace access, visit My HR after signing in.') }}

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
