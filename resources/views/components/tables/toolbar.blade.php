@props([
    'density' => null,
])
<div {{ $attributes->class(['flex flex-wrap items-center justify-between gap-3']) }}>
    <div class="flex flex-wrap items-center gap-2 min-w-0">
        @isset($leading){{ $leading }}@endisset
        {{ $slot }}
    </div>
    <div class="flex flex-wrap items-center gap-2 shrink-0">
        @isset($trailing){{ $trailing }}@endisset
        @if ($density)
            <span class="text-xs text-ink-muted" aria-hidden="true">{{ __('Density') }}: {{ $density }}</span>
        @endif
    </div>
</div>
