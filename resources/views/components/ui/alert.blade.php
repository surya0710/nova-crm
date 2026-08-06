@props(['variant' => 'info', 'title' => null])
@php
$variants = [
    'info' => 'bg-info-soft border-sky-200 text-sky-900',
    'success' => 'bg-success-soft border-emerald-200 text-emerald-900',
    'warning' => 'bg-warning-soft border-amber-200 text-amber-900',
    'danger' => 'bg-danger-soft border-red-200 text-red-900',
];
@endphp
<div role="alert" {{ $attributes->class(['rounded-lg border px-4 py-3 text-sm', $variants[$variant] ?? $variants['info']]) }}>
    @if ($title)<p class="font-semibold mb-1">{{ $title }}</p>@endif
    <div>{{ $slot }}</div>
</div>
