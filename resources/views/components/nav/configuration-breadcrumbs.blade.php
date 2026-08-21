@props(['current' => null])
@php
    $items = app(\App\Services\Configuration\ConfigurationRegistry::class)
        ->breadcrumbItems(request()->route()?->getName(), $current);
@endphp
<x-nav.breadcrumbs :items="$items" {{ $attributes }} />
