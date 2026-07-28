@props(['title' => null, 'subtitle' => null])
{{-- Prefer utility classes so layout still works if component CSS is stale on deploy. --}}
<div {{ $attributes->class(['dashboard-stack flex flex-col gap-6']) }}>
    @if ($title)
        <x-ui.page-header :title="$title" :subtitle="$subtitle">
            @isset($breadcrumbs)
                <x-slot:breadcrumbs>{{ $breadcrumbs }}</x-slot:breadcrumbs>
            @endisset
            @isset($actions)
                <x-slot:actions>{{ $actions }}</x-slot:actions>
            @endisset
        </x-ui.page-header>
    @endif

    @isset($kpis)
        <section class="dashboard-kpis grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('Key metrics') }}">{{ $kpis }}</section>
    @endisset

    <div class="dashboard-primary grid gap-6 lg:grid-cols-3">
        <div class="dashboard-primary-main space-y-6 lg:col-span-2">{{ $slot }}</div>
        @isset($aside)
            <aside class="dashboard-primary-aside space-y-6" aria-label="{{ __('Sidebar widgets') }}">{{ $aside }}</aside>
        @endisset
    </div>
</div>
