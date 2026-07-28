@props(['items' => []])

@php
    $colorClasses = [
        'emerald' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'red' => 'bg-red-50 border-red-200 text-red-800',
        'blue' => 'bg-blue-50 border-blue-200 text-blue-800',
        'purple' => 'bg-purple-50 border-purple-200 text-purple-800',
        'orange' => 'bg-orange-50 border-orange-200 text-orange-800',
        'slate' => 'bg-slate-100 border-slate-200 text-slate-600',
        'amber' => 'bg-amber-50 border-amber-200 text-amber-800',
        'cyan' => 'bg-cyan-50 border-cyan-200 text-cyan-800',
        'yellow' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'neutral' => 'bg-neutral-100 border-neutral-300 text-neutral-700',
        'default' => 'bg-surface-muted border-line text-ink-muted',
    ];
@endphp

<div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-ink-muted">
    @foreach ($items as $item)
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-flex h-3 w-3 rounded-full border {{ $colorClasses[$item['color']] ?? $colorClasses['default'] }}"></span>
            <span>{{ $item['symbol'] ?? '' }} {{ $item['label'] }}</span>
        </span>
    @endforeach
</div>
