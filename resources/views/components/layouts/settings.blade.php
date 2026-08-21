@props(['title', 'subtitle' => null])
<div {{ $attributes->class(['space-y-6']) }}>
    <x-ui.page-header :title="$title" :subtitle="$subtitle">
        @isset($breadcrumbs)
            <x-slot:breadcrumbs>{{ $breadcrumbs }}</x-slot:breadcrumbs>
        @else
            <x-slot:breadcrumbs>
                <x-nav.configuration-breadcrumbs :current="$title" />
            </x-slot:breadcrumbs>
        @endisset
        @isset($actions)
            <x-slot:actions>{{ $actions }}</x-slot:actions>
        @endisset
    </x-ui.page-header>
    @isset($nav)
        <div class="lg:grid lg:grid-cols-12 lg:gap-6">
            <aside class="mb-4 lg:col-span-3 lg:mb-0">{{ $nav }}</aside>
            <div class="space-y-6 lg:col-span-9">{{ $slot }}</div>
        </div>
    @else
        {{ $slot }}
    @endif
</div>
