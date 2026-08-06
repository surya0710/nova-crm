<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="application-name" content="{{ config('branding.product_name') }}">
        <meta name="apple-mobile-web-app-title" content="{{ config('branding.product_name') }}">

        <title>{{ config('branding.product_name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="h-12 w-12 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-white text-lg">{{ strtoupper(substr(config('branding.product_short_name'), 0, 1)) }}</div>
                    <span class="font-semibold text-xl text-slate-900">{{ config('branding.product_name') }}</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-xl border border-slate-200">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
