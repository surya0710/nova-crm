<x-careers-layout>
    <div class="max-w-md mx-auto rounded-xl border border-slate-200 bg-white p-6">
        <h1 class="text-xl font-semibold">{{ __('Candidate Login') }}</h1>
        <form method="POST" action="{{ route('careers.login', $careerOrganization) }}" class="mt-6 space-y-4">
            @csrf
            <div><label class="block text-sm">{{ __('Email') }}</label><input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            <div><label class="block text-sm">{{ __('Password') }}</label><input type="password" name="password" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> {{ __('Remember me') }}</label>
            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Login') }}</button>
        </form>
        <p class="mt-4 text-sm text-slate-500"><a href="{{ route('careers.password.request', $careerOrganization) }}">{{ __('Forgot password?') }}</a></p>
    </div>
</x-careers-layout>
