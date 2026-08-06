@props([
    'cancelHref' => null,
    'cancelLabel' => null,
    'submitLabel' => null,
])
<div {{ $attributes->class(['flex items-center justify-between gap-4 border-t border-line bg-surface-muted/40 px-5 py-4 -mx-5 -mb-5 mt-6 rounded-b-xl']) }}>
    @if ($cancelHref)
        <a href="{{ $cancelHref }}" class="text-sm text-ink-muted hover:text-ink-heading">{{ $cancelLabel ?? __('Cancel') }}</a>
    @else
        <span></span>
    @endif
    <div class="flex flex-wrap items-center gap-2">
        {{ $slot }}
        <x-ui.button type="submit" variant="primary">{{ $submitLabel ?? __('Save') }}</x-ui.button>
    </div>
</div>
