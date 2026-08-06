@props([
    'title',
    'subtitle' => null,
])
<div {{ $attributes->class(['flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0 space-y-2">
        @isset($breadcrumbs)
            <div>{{ $breadcrumbs }}</div>
        @endisset
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-semibold text-ink-heading truncate sm:text-2xl">{{ $title }}</h1>
            @isset($badges)
                <div class="flex flex-wrap items-center gap-1.5">{{ $badges }}</div>
            @endisset
        </div>
        @if ($subtitle)
            <p class="text-sm text-ink-muted">{{ $subtitle }}</p>
        @endif
        @isset($meta)
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink-muted">{{ $meta }}</div>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 shrink-0">{{ $actions }}</div>
    @endisset
</div>
