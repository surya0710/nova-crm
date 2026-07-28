@props(['actions' => []])

@php
    // Accept either structured {primary, overflow, all} or a flat list (legacy).
    if (isset($actions['primary']) || isset($actions['overflow']) || isset($actions['all'])) {
        $primary = collect($actions['primary'] ?? []);
        $overflow = collect($actions['overflow'] ?? []);
    } else {
        $all = collect($actions);
        $limit = (int) config('navigation.quick_action_limits.primary', 5);
        $primary = $all->take($limit);
        $overflow = $all->slice($limit)->values();
    }
@endphp

@if ($primary->isNotEmpty() || $overflow->isNotEmpty())
    <div class="flex min-w-0 max-w-full items-center gap-1 overflow-hidden" data-testid="header-quick-actions">
        {{-- Desktop: primary action links --}}
        <div class="hidden min-w-0 items-center gap-1 lg:flex">
            @foreach ($primary as $action)
                <a
                    href="{{ $action['href'] }}"
                    class="inline-flex max-w-[9rem] items-center truncate rounded-lg border border-line bg-surface-muted px-2.5 py-1.5 text-xs font-medium text-ink-heading hover:bg-app xl:max-w-[11rem] xl:text-sm"
                >{{ $action['label'] }}</a>
            @endforeach
        </div>

        {{-- Tablet: fewer primary actions --}}
        <div class="hidden min-w-0 items-center gap-1 sm:flex lg:hidden">
            @foreach ($primary->take((int) config('navigation.quick_action_limits.tablet', 3)) as $action)
                <a
                    href="{{ $action['href'] }}"
                    class="inline-flex max-w-[8rem] items-center truncate rounded-lg border border-line bg-surface-muted px-2 py-1.5 text-xs font-medium text-ink-heading hover:bg-app"
                >{{ $action['label'] }}</a>
            @endforeach
        </div>

        {{-- More Actions: overflow on lg+, remaining on tablet, all on mobile --}}
        @php
            $tabletOverflow = $primary->slice((int) config('navigation.quick_action_limits.tablet', 3))->concat($overflow)->values();
            $mobileActions = $primary->concat($overflow)->values();
            $hasMoreDesktop = $overflow->isNotEmpty();
            $hasMoreTablet = $tabletOverflow->isNotEmpty();
            $hasMoreMobile = $mobileActions->isNotEmpty();
        @endphp

        @if ($hasMoreDesktop || $hasMoreTablet || $hasMoreMobile)
            <div
                class="relative shrink-0"
                x-data="{ open: false }"
                x-on:keydown.escape.window="open = false"
                x-on:click.outside="open = false"
            >
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg border border-line bg-surface-muted px-2.5 py-1.5 text-xs font-medium text-ink-heading hover:bg-app sm:text-sm"
                    x-on:click="open = ! open"
                    x-bind:aria-expanded="open.toString()"
                    aria-haspopup="menu"
                    aria-label="{{ __('More actions') }}"
                >
                    <svg class="h-4 w-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                    <span class="hidden sm:inline">{{ __('More Actions') }}</span>
                    <svg class="hidden h-3.5 w-3.5 sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/></svg>
                </button>

                {{-- Desktop / tablet dropdown --}}
                <div
                    x-show="open"
                    x-cloak
                    x-transition
                    class="absolute right-0 z-dropdown mt-1 hidden w-56 rounded-xl border border-line bg-surface-card py-1 shadow-lg sm:block"
                    role="menu"
                >
                    <div class="hidden lg:block">
                        @forelse ($overflow as $action)
                            <a href="{{ $action['href'] }}" class="block px-3 py-2 text-sm text-ink hover:bg-surface-muted" role="menuitem">{{ $action['label'] }}</a>
                        @empty
                            <p class="px-3 py-2 text-sm text-ink-muted">{{ __('No more actions') }}</p>
                        @endforelse
                    </div>
                    <div class="lg:hidden">
                        @foreach ($tabletOverflow as $action)
                            <a href="{{ $action['href'] }}" class="block px-3 py-2 text-sm text-ink hover:bg-surface-muted" role="menuitem">{{ $action['label'] }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Mobile drawer --}}
                <div
                    x-show="open"
                    x-cloak
                    class="fixed inset-0 z-drawer sm:hidden"
                    role="dialog"
                    aria-modal="true"
                    aria-label="{{ __('Quick actions') }}"
                >
                    <div class="absolute inset-0 bg-[var(--nova-color-bg-overlay)]" x-on:click="open = false"></div>
                    <div
                        class="absolute inset-x-0 bottom-0 max-h-[70vh] overflow-y-auto rounded-t-2xl border border-line bg-surface-card p-4 shadow-xl"
                        x-on:click.stop
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-ink-heading">{{ __('Quick actions') }}</p>
                            <button type="button" class="rounded-lg p-2 text-ink-muted hover:bg-surface-muted" x-on:click="open = false" aria-label="{{ __('Close') }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-1">
                            @foreach ($mobileActions as $action)
                                <a href="{{ $action['href'] }}" class="block rounded-lg px-3 py-3 text-sm font-medium text-ink hover:bg-surface-muted">{{ $action['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
