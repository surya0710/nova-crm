<x-careers-layout>
    <div class="max-w-md mx-auto rounded-xl border border-slate-200 bg-white p-6">
        <h1 class="text-xl font-semibold">{{ __('Choose new password') }}</h1>
        <form method="POST" action="{{ route('careers.password.store', $careerOrganization) }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div><label class="block text-sm">{{ __('Email') }}</label><input type="email" name="email" value="{{ old('email', $email) }}" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            <div><label class="block text-sm">{{ __('Password') }}</label><input type="password" name="password" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            <div><label class="block text-sm">{{ __('Confirm password') }}</label><input type="password" name="password_confirmation" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Reset password') }}</button>
        </form>
    </div>
</x-careers-layout>
