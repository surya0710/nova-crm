@props(['organization', 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-14 w-14 text-base',
        'xl' => 'h-20 w-20 text-xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if ($organization->hasLogo())
    <img
        {{ $attributes->merge(['class' => "$sizeClass rounded-lg object-cover shrink-0 ring-1 ring-slate-200"]) }}
        src="{{ $organization->logo_url }}"
        alt="{{ $organization->name }}"
    />
@else
    <div
        {{ $attributes->merge(['class' => "$sizeClass rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-semibold text-white shrink-0 shadow-sm"]) }}
        title="{{ $organization->name }}"
    >
        {{ $organization->initials }}
    </div>
@endif
