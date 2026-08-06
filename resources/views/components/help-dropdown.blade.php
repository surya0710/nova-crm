@props([
    'route' => null,
    'label' => __('Help'),
])

@php
    $routeName = $route ?? optional(request()->route())->getName();
    $documentation = app(\App\Services\DocumentationService::class);
    $targets = auth()->check()
        ? $documentation->resolveHelpTargets(auth()->user(), $routeName)
        : collect();
    $tooltip = (string) config('documentation.help.button.tooltip', __('Open documentation for this page'));
@endphp

@if ($targets->count() === 1)
    <x-help-button :route="$routeName" :label="$label" {{ $attributes }} />
@elseif ($targets->count() > 1)
    <div {{ $attributes->merge(['class' => 'hidden md:block']) }}>
        <x-dropdown align="right" width="64" contentClasses="py-1 bg-white">
            <x-slot name="trigger">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    title="{{ $tooltip }}"
                    aria-label="{{ $tooltip }}"
                    aria-haspopup="true"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $label }}</span>
                    <span aria-hidden="true">▼</span>
                </button>
            </x-slot>
            <x-slot name="content">
                @foreach ($targets as $target)
                    <x-dropdown-link :href="$target['url']">
                        <span class="block text-xs text-slate-500">{{ $target['label'] }}</span>
                        <span class="block text-sm text-slate-800">{{ $target['title'] }}</span>
                    </x-dropdown-link>
                @endforeach
            </x-slot>
        </x-dropdown>
    </div>
@endif
