<x-mail::message>
# {{ __('You are invited') }}

{{ __('Hello :name,', ['name' => $user->name]) }}

{{ __(':organization has invited you to create your :product account.', ['organization' => $organization->name, 'product' => config('branding.product_name')]) }}

{{ __('Use the button below to set your password and activate your account. This link expires on :date.', [
    'date' => $invitation->expires_at->timezone(config('app.timezone'))->format('M j, Y g:i A'),
]) }}

<x-mail::button :url="$acceptUrl">
{{ __('Set password & activate') }}
</x-mail::button>

{{ __('If you did not expect this invitation, you can ignore this email.') }}

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
