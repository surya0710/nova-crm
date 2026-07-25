@props(['title', 'subtitle' => null, 'maxWidth' => '3xl'])
@php $width = ['2xl' => 'max-w-2xl', '3xl' => 'max-w-3xl', '4xl' => 'max-w-4xl', '5xl' => 'max-w-5xl', '6xl' => 'max-w-6xl'][$maxWidth] ?? 'max-w-3xl'; @endphp
<div {{ $attributes->class(['mx-auto w-full space-y-4', $width]) }}>
    <x-ui.page-header :title="$title" :subtitle="$subtitle">
        @isset($breadcrumbs)
            <x-slot:breadcrumbs>{{ $breadcrumbs }}</x-slot:breadcrumbs>
        @endisset
        @isset($actions)
            <x-slot:actions>{{ $actions }}</x-slot:actions>
        @endisset
    </x-ui.page-header>
    <x-ui.card>{{ $slot }}</x-ui.card>
</div>
