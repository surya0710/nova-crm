<x-careers-layout>
    <div class="max-w-md mx-auto rounded-xl border border-slate-200 bg-white p-6">
        <h1 class="text-xl font-semibold">{{ __('Create candidate account') }}</h1>
        <form method="POST" action="{{ route('careers.register', $careerOrganization) }}" class="mt-6 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-sm">{{ __('First name') }}</label><input name="first_name" value="{{ old('first_name') }}" class="mt-1 w-full rounded-lg border-slate-300" required></div>
                <div><label class="block text-sm">{{ __('Last name') }}</label><input name="last_name" value="{{ old('last_name') }}" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            </div>
            <div><label class="block text-sm">{{ __('Email') }}</label><input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            <div><label class="block text-sm">{{ __('Phone') }}</label><input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border-slate-300"></div>
            <div><label class="block text-sm">{{ __('Password') }}</label><input type="password" name="password" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            <div><label class="block text-sm">{{ __('Confirm password') }}</label><input type="password" name="password_confirmation" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Register') }}</button>
        </form>
    </div>
</x-careers-layout>
