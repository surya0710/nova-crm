@props([
    'actor' => null,
    'timestamp' => null,
    'type' => 'note',
])
<li {{ $attributes->class(['relative pb-6 last:pb-0']) }}>
    <span class="absolute -start-[1.9rem] top-1 flex h-7 w-7 items-center justify-center rounded-full border border-line bg-surface-card text-[10px] font-semibold text-primary-700" aria-hidden="true">
        @isset($icon)
            {{ $icon }}
        @else
            {{ $actor ? strtoupper(substr($actor, 0, 1)) : '•' }}
        @endif
    </span>
    <div class="min-w-0">
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
            @if ($actor)
                <span class="text-sm font-medium text-ink-heading">{{ $actor }}</span>
            @endif
            @isset($label)
                <span class="text-xs text-ink-muted">{{ $label }}</span>
            @endisset
            @if ($timestamp)
                <time class="text-xs text-ink-muted" datetime="{{ $timestamp instanceof \Carbon\Carbon ? $timestamp->toIso8601String() : $timestamp }}">
                    {{ $timestamp instanceof \Carbon\Carbon ? $timestamp->diffForHumans() : $timestamp }}
                </time>
            @endif
        </div>
        <div class="mt-1 text-sm text-ink whitespace-pre-wrap">{{ $slot }}</div>
    </div>
</li>
