@props(['workspace' => null])

@if ($workspace)
    <div {{ $attributes->class(['mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-line bg-surface-muted px-3 py-2 text-sm']) }}>
        <span class="font-medium text-ink-heading">{{ $workspace }}</span>
        @isset($crumbs)
            <span class="text-ink-muted" aria-hidden="true">/</span>
            {{ $crumbs }}
        @endisset
        @isset($actions)
            <div class="ms-auto flex items-center gap-2">{{ $actions }}</div>
        @endisset
        {{ $slot }}
    </div>
@endif
