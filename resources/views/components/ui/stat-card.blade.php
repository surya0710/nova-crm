@props(['label', 'value', 'hint' => null, 'trend' => null])
<div {{ $attributes->class(['rounded-xl border border-line bg-surface-card p-5 shadow-sm']) }}>
    <p class="text-sm font-medium text-ink-muted">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold text-ink-heading">{{ $value }}</p>
    @if ($hint || $trend)
        <p class="mt-1 text-xs text-ink-muted">
            @if ($trend)<span class="font-medium">{{ $trend }}</span>@if($hint) · @endif @endif{{ $hint }}
        </p>
    @endif
</div>
