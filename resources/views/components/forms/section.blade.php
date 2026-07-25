@props([
    'title' => null,
    'subtitle' => null,
])
<section {{ $attributes->class(['space-y-4']) }}>
    @if ($title)
        <div class="border-b border-line pb-3">
            <h3 class="text-sm font-semibold text-ink-heading">{{ $title }}</h3>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-ink-muted">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        {{ $slot }}
    </div>
</section>
