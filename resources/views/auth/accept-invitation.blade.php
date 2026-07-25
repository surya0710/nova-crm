<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Set a password to activate your account for :organization.', ['organization' => $organization->name]) }}
    </div>

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        <span class="font-medium text-gray-800 dark:text-gray-200">{{ __('Email') }}:</span>
        {{ $user->email }}
    </div>

    <form method="POST" action="{{ route('invitations.accept.store', ['token' => $token]) }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-input-error :messages="$errors->get('token')" class="mt-2" />

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Activate account') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
