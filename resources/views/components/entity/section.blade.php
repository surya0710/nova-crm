@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
])
<x-ui.card :padding="false" {{ $attributes }}>
    @if ($title || isset($actions))
        <div class="flex items-start justify-between gap-3 border-b border-line bg-surface-muted/50 px-5 py-3.5">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-sm font-semibold text-ink-heading">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2 shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div @class([$padding ? 'p-5' : ''])>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="border-t border-line px-5 py-3">{{ $footer }}</div>
    @endisset
</x-ui.card>
