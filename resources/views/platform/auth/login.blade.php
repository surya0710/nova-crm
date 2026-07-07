<x-platform-guest-layout>
    <form method="POST" action="{{ route('platform.login.store') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-slate-300" />
            <x-text-input id="email" class="block mt-1 w-full bg-slate-800 border-slate-700 text-white" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" class="text-slate-300" />
            <x-text-input id="password" class="block mt-1 w-full bg-slate-800 border-slate-700 text-white" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label class="inline-flex items-center text-sm text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-slate-600 bg-slate-800 text-violet-600">
                <span class="ms-2">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex justify-end mt-6">
            <x-primary-button class="bg-violet-600 hover:bg-violet-500">
                {{ __('Platform Sign In') }}
            </x-primary-button>
        </div>
    </form>
</x-platform-guest-layout>
