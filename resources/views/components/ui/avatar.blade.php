@props(['name' => '?', 'size' => 'md', 'src' => null])
@php
$sizes = ['xs' => 'h-6 w-6 text-[10px]', 'sm' => 'h-8 w-8 text-xs', 'md' => 'h-9 w-9 text-sm', 'lg' => 'h-12 w-12 text-base', 'xl' => 'h-16 w-16 text-lg'];
$initial = strtoupper(mb_substr($name, 0, 1));
@endphp
@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->class(['rounded-full object-cover', $sizes[$size] ?? $sizes['md']]) }} />
@else
    <span {{ $attributes->class(['inline-flex items-center justify-center rounded-full bg-primary-600 text-white font-semibold', $sizes[$size] ?? $sizes['md']]) }} aria-hidden="true">{{ $initial }}</span>
@endif
