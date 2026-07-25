<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-forms.field :label="__('Email')" name="email" required>
            <x-forms.input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        </x-forms.field>

        <x-forms.field :label="__('Password')" name="password" required>
            <x-forms.input id="password" type="password" name="password" required autocomplete="current-password" />
        </x-forms.field>

        <div class="block">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-ink">
                <input id="remember_me" type="checkbox" class="rounded border-line text-primary-600 focus:ring-primary-500" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3">
            @if (Route::has('password.request'))
                <a class="text-sm text-ink-muted hover:text-ink-heading focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-md" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-ui.button type="submit" variant="primary">
                {{ __('Log in') }}
            </x-ui.button>
        </div>
    </form>
</x-guest-layout>
