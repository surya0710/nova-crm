<x-guest-layout>
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white">N</div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Set up your organization') }}</h2>
                <p class="text-sm text-gray-500">{{ __('Step 1 of 1 — Create your workspace') }}</p>
            </div>
        </div>
        <p class="text-sm text-gray-600">
            {{ __('Create your company workspace to start using NovaCRM.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('organization.setup.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200">
            <x-input-label :value="__('Organization Logo')" />
            <div class="mt-2">
                <x-logo-upload />
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Organization Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Business Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', auth()->user()->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="timezone" :value="__('Timezone')" />
            <select id="timezone" name="timezone" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                @foreach ($timezones as $timezone)
                    <option value="{{ $timezone }}" @selected(old('timezone', config('app.timezone')) === $timezone)>
                        {{ $timezone }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="currency" :value="__('Currency')" />
            <select id="currency" name="currency" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                @foreach ($currencies as $code => $label)
                    <option value="{{ $code }}" @selected(old('currency', 'USD') === $code)>
                        {{ $code }} — {{ $label }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('Create Organization') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
