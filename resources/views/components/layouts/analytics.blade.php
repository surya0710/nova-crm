@props(['title', 'subtitle' => null])
<div {{ $attributes->class(['space-y-6']) }}>
    <x-ui.page-header :title="$title" :subtitle="$subtitle">
        @isset($breadcrumbs)
            <x-slot:breadcrumbs>{{ $breadcrumbs }}</x-slot:breadcrumbs>
        @endisset
        @isset($actions)
            <x-slot:actions>{{ $actions }}</x-slot:actions>
        @endisset
    </x-ui.page-header>
    @isset($kpis)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{{ $kpis }}</div>
    @endisset
    @isset($filters)
        <div class="rounded-xl border border-line bg-surface-card p-4 shadow-sm">{{ $filters }}</div>
    @endisset
    <div class="grid gap-6 lg:grid-cols-2">{{ $slot }}</div>
</div>
