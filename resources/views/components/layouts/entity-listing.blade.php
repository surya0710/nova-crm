@props(['title', 'subtitle' => null])
<div {{ $attributes->class(['space-y-4']) }}>
    <x-ui.page-header :title="$title" :subtitle="$subtitle">
        @isset($breadcrumbs)
            <x-slot:breadcrumbs>{{ $breadcrumbs }}</x-slot:breadcrumbs>
        @endisset
        @isset($actions)
            <x-slot:actions>{{ $actions }}</x-slot:actions>
        @endisset
    </x-ui.page-header>
    @isset($filters)
        <div class="rounded-xl border border-line bg-surface-card p-4 shadow-sm">{{ $filters }}</div>
    @endisset
    <div>{{ $slot }}</div>
    @isset($pagination)
        <x-tables.pagination>{{ $pagination }}</x-tables.pagination>
    @endisset
</div>
