@props([
    'title',
    'subtitle' => null,
    'href' => null,
    'linkLabel' => null,
])
<section {{ $attributes->class(['rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden flex flex-col']) }}>
    <div class="flex items-start justify-between gap-3 border-b border-line bg-surface-muted/40 px-4 py-3">
        <div class="min-w-0">
            <h2 class="text-sm font-semibold text-ink-heading truncate">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-ink-muted">{{ $subtitle }}</p>
            @endif
        </div>
        @if ($href)
            <a href="{{ $href }}" class="text-xs font-medium text-primary-600 hover:text-primary-700 shrink-0">{{ $linkLabel ?? __('View all') }}</a>
        @endif
    </div>
    <div class="flex-1 p-4">{{ $slot }}</div>
</section>
