@props([
    'route' => null,
    'label' => __('Help'),
    'class' => 'hidden md:inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
])

@php
    $routeName = $route ?? optional(request()->route())->getName();
    $documentation = app(\App\Services\DocumentationService::class);
    $context = auth()->check()
        ? $documentation->resolveContextForRoute(auth()->user(), $routeName)
        : ['available' => false];
    $tooltip = (string) config('documentation.help.button.tooltip', __('Open documentation for this page'));
@endphp

@if ($context['available'] ?? false)
    <a
        href="{{ $context['url'] }}"
        {{ $attributes->merge(['class' => $class]) }}
        title="{{ $tooltip }}"
        aria-label="{{ $tooltip }}: {{ $context['title'] }}"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ $label }}</span>
    </a>
@endif
