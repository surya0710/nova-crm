<x-careers-layout>
    <div class="max-w-md mx-auto rounded-xl border border-slate-200 bg-white p-6">
        <h1 class="text-xl font-semibold">{{ __('Reset password') }}</h1>
        <form method="POST" action="{{ route('careers.password.request', $careerOrganization) }}" class="mt-6 space-y-4">
            @csrf
            <div><label class="block text-sm">{{ __('Email') }}</label><input type="email" name="email" class="mt-1 w-full rounded-lg border-slate-300" required></div>
            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Send reset link') }}</button>
        </form>
    </div>
</x-careers-layout>
