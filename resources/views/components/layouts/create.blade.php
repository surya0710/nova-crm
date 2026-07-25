@props(['title', 'subtitle' => null, 'maxWidth' => '3xl'])
<x-layouts.entity-form :title="$title" :subtitle="$subtitle" :max-width="$maxWidth" {{ $attributes }}>
    @isset($breadcrumbs)
        <x-slot:breadcrumbs>{{ $breadcrumbs }}</x-slot:breadcrumbs>
    @endisset
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset
    {{ $slot }}
</x-layouts.entity-form>
