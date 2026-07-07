@props(['active' => false, 'disabled' => false, 'badge' => null, 'icon' => null])

@php
    $base = 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150';
    $classes = $disabled
        ? "$base text-slate-500 cursor-not-allowed opacity-60"
        : ($active
            ? "$base bg-indigo-600/20 text-white"
            : "$base text-slate-300 hover:bg-slate-800 hover:text-white");
@endphp

@if ($disabled)
    <span {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <span class="shrink-0 opacity-70">{!! $icon !!}</span>
        @endif
        <span class="flex-1">{{ $slot }}</span>
        @if ($badge)
            <span class="text-[10px] uppercase tracking-wide bg-slate-700 text-slate-400 px-1.5 py-0.5 rounded">{{ $badge }}</span>
        @endif
    </span>
@else
    <a {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <span class="shrink-0">{!! $icon !!}</span>
        @endif
        <span class="flex-1">{{ $slot }}</span>
        @if ($badge)
            <span class="text-[10px] uppercase tracking-wide bg-slate-700 text-slate-400 px-1.5 py-0.5 rounded">{{ $badge }}</span>
        @endif
    </a>
@endif
