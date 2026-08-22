@props(['organization', 'size' => 'md'])

@php
    $sizes = [
        'sm' => 'h-10 w-10',
        'md' => 'h-12 w-12',
        'lg' => 'h-16 w-16',
        'xl' => 'h-24 w-24',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $src = $organization->logo_url
        ?: asset(ltrim((string) config('branding.logo', 'konnect-logo.png'), '/'));
@endphp

<img
    {{ $attributes->merge(['class' => "$sizeClass rounded-lg object-contain shrink-0 ring-1 ring-slate-200 bg-black"]) }}
    src="{{ $src }}"
    alt="{{ $organization->name }}"
/>
