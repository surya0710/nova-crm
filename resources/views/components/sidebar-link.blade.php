@props(['active' => false, 'disabled' => false, 'badge' => null, 'icon' => null, 'collapsed' => false])
<x-nav.sidebar-link
    :active="$active"
    :disabled="$disabled"
    :badge="$badge"
    :icon="$icon"
    :collapsed="$collapsed"
    {{ $attributes }}
>{{ $slot }}</x-nav.sidebar-link>
