@props(['size' => 'md', 'variant' => 'default'])

@php
    $widths = [
        'compact' => 'w-10',
        'sm' => 'w-[150px]',
        'md' => 'w-[175px]',
        'lg' => 'w-[180px]',
        'xl' => 'w-[200px]',
        '2xl' => 'w-[200px]',
    ];
    $widthClass = $widths[$size] ?? $widths['md'];
    $file = $variant === 'dark'
        ? config('branding.logo_dark', 'konnect-dark-logo.jpg')
        : config('branding.logo', 'konnect-logo.png');
@endphp

<img
    src="{{ asset(ltrim((string) $file, '/')) }}"
    alt="{{ config('branding.product_name') }}"
    {{ $attributes->merge(['class' => "$widthClass h-auto max-w-full object-contain shrink-0"]) }}
>
