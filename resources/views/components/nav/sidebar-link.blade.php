@props(['active' => false, 'disabled' => false, 'badge' => null, 'icon' => null, 'collapsed' => false])

@php
    $base = 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-fast';
    $classes = $disabled
        ? "$base text-sidebar-muted cursor-not-allowed opacity-60"
        : ($active
            ? "$base bg-sidebar-active text-sidebar-text"
            : "$base text-sidebar-muted hover:bg-sidebar-hover hover:text-sidebar-text");
@endphp

@if ($disabled)
    <span {{ $attributes->merge(['class' => $classes]) }} @if($collapsed) title="{{ trim(strip_tags($slot)) }}" @endif>
        @if ($icon)
            <span class="shrink-0 opacity-70">{!! $icon !!}</span>
        @endif
        <span @class(['flex-1', 'sr-only' => $collapsed])>{{ $slot }}</span>
        @if ($badge && ! $collapsed)
            <span class="rounded bg-slate-700 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-slate-300">{{ $badge }}</span>
        @endif
    </span>
@else
    <a {{ $attributes->merge(['class' => $classes]) }} @if($collapsed) title="{{ trim(strip_tags($slot)) }}" @endif>
        @if ($icon)
            <span class="shrink-0">{!! $icon !!}</span>
        @endif
        <span @class(['flex-1', 'sr-only' => $collapsed])>{{ $slot }}</span>
        @if ($badge && ! $collapsed)
            <x-ui.badge variant="primary">{{ $badge }}</x-ui.badge>
        @endif
    </a>
@endif
