@props(['label', 'value', 'description' => null])
<div {{ $attributes->class(['rounded-xl border border-line bg-surface-card p-4 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">{{ $label }}</p>
            <p class="mt-1 text-xl font-semibold text-ink-heading">{{ $value }}</p>
        </div>
        @isset($icon)<div class="text-primary-600">{{ $icon }}</div>@endisset
    </div>
    @if ($description)<p class="mt-2 text-xs text-ink-muted">{{ $description }}</p>@endif
</div>
