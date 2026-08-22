<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="application-name" content="{{ config('branding.product_name') }}">
        <meta name="apple-mobile-web-app-title" content="{{ config('branding.product_name') }}">
        <title>{{ __('Platform Login') }} — {{ config('branding.product_name') }}</title>
        <x-brand-favicon />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-950 text-slate-100">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="mb-6">
                <x-product-logo size="xl" />
                <p class="mt-2 text-sm text-slate-400">{{ __('SaaS Administration') }}</p>
            </div>
            <div class="w-full sm:max-w-md px-6 py-6 bg-slate-900 border border-slate-800 shadow-xl sm:rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
