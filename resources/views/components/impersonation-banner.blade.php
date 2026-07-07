@if (session('platform_impersonation') || app(\App\Services\Platform\PlatformImpersonationService::class)->isActive(request()))
    <div class="bg-amber-500 text-amber-950 px-4 py-2 text-sm flex items-center justify-between gap-4">
        <span class="font-medium">{{ __('You are impersonating an organization.') }}</span>
        <form method="POST" action="{{ route('impersonation.stop') }}">
            @csrf
            <button type="submit" class="underline font-semibold hover:no-underline">{{ __('Return to Platform') }}</button>
        </form>
    </div>
@endif
