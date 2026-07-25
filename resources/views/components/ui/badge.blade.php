@props(['variant' => 'neutral', 'size' => 'sm'])
@php
$variants = [
    'neutral' => 'bg-neutral-100 text-neutral-700',
    'primary' => 'bg-primary-50 text-primary-700',
    'success' => 'bg-success-soft text-success',
    'warning' => 'bg-warning-soft text-warning',
    'danger' => 'bg-danger-soft text-danger',
    'info' => 'bg-info-soft text-info',
];
$sizeClass = $size === 'md' ? 'px-2.5 py-1 text-xs' : 'px-2 py-0.5 text-[11px]';
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-sm font-medium', $sizeClass, $variants[$variant] ?? $variants['neutral']]) }}>{{ $slot }}</span>
