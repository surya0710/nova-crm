@props(['title' => null, 'subtitle' => null])
<div {{ $attributes->class(['space-y-6']) }}>
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
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{{ $kpis }}</div>
    @endisset
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">{{ $slot }}</div>
        @isset($aside)
            <aside class="space-y-6">{{ $aside }}</aside>
        @endisset
    </div>
</div>
