@props([
    'title' => null,
    'actions' => [],
])

@php
    if (isset($actions['primary']) || isset($actions['overflow']) || isset($actions['all'])) {
        $primary = collect($actions['primary'] ?? []);
        $overflow = collect($actions['overflow'] ?? []);
        $flat = collect($actions['all'] ?? $primary->concat($overflow));
    } else {
        $flat = collect($actions);
        $limit = (int) config('navigation.quick_action_limits.primary', 5);
        $primary = $flat->take($limit);
        $overflow = $flat->slice($limit)->values();
    }
@endphp

@if ($primary->isNotEmpty() || $overflow->isNotEmpty())
    <section {{ $attributes->class(['space-y-3']) }} aria-label="{{ $title ?? __('Quick actions') }}">
        @if ($title)
            <h2 class="text-sm font-semibold text-ink-heading">{{ $title }}</h2>
        @endif
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($primary as $action)
                @continue(empty($action['href']))
                <x-ui.button
                    :href="$action['href']"
                    :variant="$action['variant'] ?? 'secondary'"
                    size="sm"
                >{{ $action['label'] }}</x-ui.button>
            @endforeach

            @if ($overflow->isNotEmpty())
                <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
                    <x-ui.button type="button" variant="ghost" size="sm" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()">
                        {{ __('More Actions') }}
                        <svg class="ml-1 h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/></svg>
                    </x-ui.button>
                    <div
                        x-show="open"
                        x-cloak
                        x-transition
                        class="absolute left-0 z-dropdown mt-1 w-56 rounded-xl border border-line bg-surface-card py-1 shadow-lg"
                        role="menu"
                    >
                        @foreach ($overflow as $action)
                            @continue(empty($action['href']))
                            <a href="{{ $action['href'] }}" class="block px-3 py-2 text-sm text-ink hover:bg-surface-muted" role="menuitem">{{ $action['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
            {{ $slot }}
        </div>
    </section>
@endif
