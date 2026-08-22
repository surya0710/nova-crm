<div class="flex h-full flex-col">
    <div class="border-b border-line p-5">
        <div class="flex items-center gap-2">
            <x-product-logo size="sm" />
        </div>
    </div>

    <nav class="flex-1 space-y-0.5 overflow-y-auto p-3 text-sm" aria-label="{{ __('Platform modules') }}">
        @php $user = auth('platform')->user(); @endphp

        @foreach (config('platform.navigation', []) as $item)
            @if ($user->hasPermission($item['permission']))
                @php
                    $active = collect($item['match'] ?? [$item['route']])->contains(fn ($pattern) => request()->routeIs($pattern));
                @endphp
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'block rounded-lg px-3 py-2 font-medium transition-colors',
                        'bg-primary-50 text-primary-700' => $active,
                        'text-ink-muted hover:bg-surface-muted hover:text-ink-heading' => ! $active,
                    ])
                    @if ($active) aria-current="page" @endif
                >
                    {{ __($item['label']) }}
                </a>
            @endif
        @endforeach
    </nav>

    <div class="border-t border-line p-4">
        <form method="POST" action="{{ route('platform.logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-ink-muted hover:bg-surface-muted hover:text-ink-heading">
                {{ __('Sign out') }}
            </button>
        </form>
    </div>
</div>
