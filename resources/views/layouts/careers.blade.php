<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $careerSiteSettings?->seo_title ?? ($careerOrganization->name ?? config('app.name')).' Careers' }}</title>
    @if($careerSiteSettings?->seo_description)
        <meta name="description" content="{{ $careerSiteSettings->seo_description }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <a href="{{ route('careers.home', $careerOrganization) }}" class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-semibold">
                    {{ strtoupper(substr($careerOrganization->name, 0, 1)) }}
                </div>
                <div>
                    <div class="font-semibold">{{ $careerOrganization->name }}</div>
                    <div class="text-xs text-slate-500">{{ __('Careers') }}</div>
                </div>
            </a>
            <nav class="flex items-center gap-3 text-sm">
                <a href="{{ route('careers.home', $careerOrganization) }}" class="text-slate-600 hover:text-slate-900">{{ __('Jobs') }}</a>
                @auth('candidate')
                    <a href="{{ route('careers.dashboard', $careerOrganization) }}" class="text-slate-600 hover:text-slate-900">{{ __('Dashboard') }}</a>
                    <form method="POST" action="{{ route('careers.logout', $careerOrganization) }}" class="inline">@csrf<button class="text-slate-600 hover:text-slate-900">{{ __('Logout') }}</button></form>
                @else
                    <a href="{{ route('careers.login', $careerOrganization) }}" class="text-slate-600 hover:text-slate-900">{{ __('Login') }}</a>
                    <a href="{{ route('careers.register', $careerOrganization) }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-white">{{ __('Register') }}</a>
                @endauth
            </nav>
        </div>
    </header>

    @if($careerSiteSettings?->banner_path)
        <div class="bg-indigo-700 text-white">
            <div class="max-w-6xl mx-auto px-4 py-10">
                <h1 class="text-3xl font-semibold">{{ $careerSiteSettings->seo_title ?? __('Join our team') }}</h1>
                @if($careerSiteSettings->about_us)
                    <p class="mt-3 max-w-3xl text-indigo-100">{{ Str::limit(strip_tags($careerSiteSettings->about_us), 180) }}</p>
                @endif
            </div>
        </div>
    @endif

    <main class="max-w-6xl mx-auto px-4 py-8">
        @if(session('status'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white mt-12">
        <div class="max-w-6xl mx-auto px-4 py-6 text-sm text-slate-500">
            @if($careerSiteSettings?->custom_footer)
                {!! nl2br(e($careerSiteSettings->custom_footer)) !!}
            @else
                {{ __('Powered by NovaCRM Recruitment') }}
            @endif
        </div>
    </footer>
</body>
</html>
