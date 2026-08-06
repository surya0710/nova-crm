<x-layouts.portal>
    <div class="max-w-md mx-auto rounded-xl border border-slate-200 bg-white p-6">
        <h1 class="text-xl font-semibold">{{ __('Client Login') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $portalSettings?->welcome_message ?? __('Sign in to view shared projects and deliverables.') }}</p>
        <form method="POST" action="{{ route('portal.login', $portalOrganization) }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm">{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-slate-300" required>
            </div>
            <div>
                <label class="block text-sm">{{ __('Password') }}</label>
                <input type="password" name="password" class="mt-1 w-full rounded-lg border-slate-300" required>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> {{ __('Remember me') }}</label>
            <button class="w-full rounded-lg bg-slate-800 px-4 py-2 text-white">{{ __('Login') }}</button>
        </form>
    </div>
</x-layouts.portal>
