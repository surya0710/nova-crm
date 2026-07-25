@props(['name', 'show' => false, 'maxWidth' => '2xl', 'title' => null])
@php
$maxWidth = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl'][$maxWidth] ?? 'sm:max-w-2xl';
@endphp
<div
    x-data="{ show: @js($show) }"
    x-on:open-modal-{{ $name }}.window="show = true"
    x-on:close-modal-{{ $name }}.window="show = false"
    x-on:keydown.escape.window="if (show) show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-modal overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-[var(--nova-color-bg-overlay)]" @click="show = false"></div>
    <div class="relative mb-6 transform overflow-hidden rounded-xl bg-surface-card shadow-lg transition-all sm:mx-auto sm:w-full {{ $maxWidth }}" @click.stop>
        @if ($title)
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-base font-semibold text-ink-heading">{{ $title }}</h2>
                <button type="button" class="rounded-md p-1.5 text-ink-muted hover:bg-surface-muted" @click="show = false" aria-label="{{ __('Close') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
        <div class="px-5 py-4">{{ $slot }}</div>
    </div>
</div>
