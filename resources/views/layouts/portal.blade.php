<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($portalOrganization->name ?? config('app.name')).' — '.__('Client Portal') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <a href="{{ route('portal.dashboard', $portalOrganization) }}" class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-slate-800 text-white flex items-center justify-center font-semibold">
                    {{ strtoupper(substr($portalOrganization->name, 0, 1)) }}
                </div>
                <div>
                    <div class="font-semibold">{{ $portalOrganization->name }}</div>
                    <div class="text-xs text-slate-500">{{ __('Client Portal') }}</div>
                </div>
            </a>
            <nav class="flex items-center gap-3 text-sm">
                @auth('client')
                    <a href="{{ route('portal.dashboard', $portalOrganization) }}" class="text-slate-600 hover:text-slate-900">{{ __('Dashboard') }}</a>
                    <form method="POST" action="{{ route('portal.logout', $portalOrganization) }}" class="inline">@csrf<button class="text-slate-600 hover:text-slate-900">{{ __('Logout') }}</button></form>
                @else
                    <a href="{{ route('portal.login', $portalOrganization) }}" class="rounded-lg bg-slate-800 px-3 py-2 text-white">{{ __('Login') }}</a>
                @endauth
            </nav>
        </div>
    </header>
    <main class="max-w-6xl mx-auto px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
