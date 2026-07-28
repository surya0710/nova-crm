@props(['title' => null, 'subtitle' => null])
<div {{ $attributes->class(['dashboard-stack']) }}>
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
        <section class="dashboard-kpis" aria-label="{{ __('Key metrics') }}">{{ $kpis }}</section>
    @endisset
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">{{ $slot }}</div>
</div>
