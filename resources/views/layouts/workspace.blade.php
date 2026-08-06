{{-- Optional workspace chrome wrapper (Phase 14.1) --}}
@props(['workspace' => null])
<div {{ $attributes->class(['space-y-4']) }}>
    <x-shell.context-bar :workspace="$workspace">
        @isset($crumbs)<x-slot:crumbs>{{ $crumbs }}</x-slot:crumbs>@endisset
        @isset($actions)<x-slot:actions>{{ $actions }}</x-slot:actions>@endisset
    </x-shell.context-bar>
    {{ $slot }}
</div>
