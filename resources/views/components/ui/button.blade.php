@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-5 py-2.5 text-sm',
];
$variants = [
    'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 border border-transparent',
    'secondary' => 'bg-surface-muted text-ink-heading hover:bg-neutral-100 border border-line focus:ring-primary-500',
    'ghost' => 'bg-transparent text-ink hover:bg-surface-muted border border-transparent focus:ring-primary-500',
    'danger' => 'bg-danger text-white hover:bg-red-700 focus:ring-red-500 border border-transparent',
    'link' => 'bg-transparent text-primary-600 hover:text-primary-700 underline-offset-2 hover:underline px-0 py-0 border-0 shadow-none',
];
$base = 'inline-flex items-center justify-center gap-2 rounded-md font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-fast disabled:opacity-[var(--nova-opacity-disabled)] disabled:pointer-events-none';
$classes = trim($base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']));
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class([$classes]) }}>{{ $slot }}</button>
@endif
