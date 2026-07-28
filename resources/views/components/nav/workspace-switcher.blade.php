@props(['workspaces' => collect(), 'current' => null])

@php
    $items = collect($workspaces)->reject(fn ($w) => ($w['footer'] ?? false));
@endphp

<div {{ $attributes->class(['px-3']) }} x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        class="flex w-full items-center justify-between gap-2 rounded-lg border border-sidebar-border bg-slate-800/60 px-3 py-2 text-left text-sm text-sidebar-text hover:bg-sidebar-hover"
        @click="open = ! open"
        aria-haspopup="listbox"
        :aria-expanded="open.toString()"
    >
        <span class="truncate font-medium">
            {{ collect($workspaces)->firstWhere('id', $current)['label'] ?? __('Workspace') }}
        </span>
        <svg class="h-4 w-4 shrink-0 text-sidebar-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute z-50 mt-1 w-[calc(16rem-1.5rem)] rounded-lg border border-sidebar-border bg-slate-900 p-1 shadow-md"
        role="listbox"
    >
        @foreach ($items as $workspace)
            <a
                href="{{ $workspace['href'] }}"
                class="flex items-center gap-2 rounded-md px-3 py-2 text-sm {{ ($workspace['id'] ?? null) === $current ? 'bg-sidebar-active text-white' : 'text-slate-300 hover:bg-sidebar-hover hover:text-white' }}"
                role="option"
                @click="open = false"
            >
                <span class="truncate">{{ $workspace['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
