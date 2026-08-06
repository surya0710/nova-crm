@props([
    'href' => null,
    'title',
    'subtitle' => null,
    'badge' => null,
    'badgeVariant' => 'warning',
])
<li>
    @php $tag = $href ? 'a' : 'div'; @endphp
    <{{ $tag }}
        @if ($href) href="{{ $href }}" @endif
        {{ $attributes->class(['flex items-start justify-between gap-3 px-4 py-3 hover:bg-surface-muted/60 transition']) }}
    >
        <div class="min-w-0">
            <p class="text-sm font-medium text-ink-heading truncate">{{ $title }}</p>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-ink-muted truncate">{{ $subtitle }}</p>
            @endif
        </div>
        @if ($badge)
            <x-ui.badge :variant="$badgeVariant" class="shrink-0">{{ $badge }}</x-ui.badge>
        @endif
    </{{ $tag }}>
</li>
