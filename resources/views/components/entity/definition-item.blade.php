@props([
    'label',
    'span' => 1,
])
<div {{ $attributes->class([
    'sm:col-span-2' => $span >= 2,
]) }}>
    <dt class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $label }}</dt>
    <dd class="mt-1 text-sm text-ink">{{ $slot }}</dd>
</div>
