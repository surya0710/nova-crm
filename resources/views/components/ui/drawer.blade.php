@props(['name' => 'drawer', 'title' => null, 'side' => 'right'])
@php $sideClass = $side === 'left' ? 'left-0' : 'right-0'; @endphp
<div
    x-data="{ open: false }"
    x-on:open-drawer-{{ $name }}.window="open = true"
    x-on:close-drawer-{{ $name }}.window="open = false"
    x-on:keydown.escape.window="if (open) open = false"
    {{ $attributes }}
>
    <div x-show="open" x-cloak class="fixed inset-0 z-drawer" role="dialog" aria-modal="true" @if($title) aria-label="{{ $title }}" @endif>
        <div class="absolute inset-0 bg-[var(--nova-color-bg-overlay)]" @click="open = false"></div>
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-moderate"
            x-transition:enter-start="{{ $side === 'left' ? '-translate-x-full' : 'translate-x-full' }}"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-normal"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="{{ $side === 'left' ? '-translate-x-full' : 'translate-x-full' }}"
            class="absolute inset-y-0 {{ $sideClass }} flex w-full max-w-md flex-col bg-surface-card shadow-lg"
        >
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
                <h2 class="text-sm font-semibold text-ink-heading">{{ $title }}</h2>
                <button type="button" class="rounded-md p-2 text-ink-muted hover:bg-surface-muted" @click="open = false" aria-label="{{ __('Close') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">{{ $slot }}</div>
            @isset($footer)<div class="border-t border-line p-4">{{ $footer }}</div>@endisset
        </div>
    </div>
</div>
