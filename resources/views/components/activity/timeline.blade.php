@props([
    'title' => null,
    'subtitle' => null,
    'emptyTitle' => null,
    'emptyDescription' => null,
    'empty' => null,
])
<section {{ $attributes->class(['space-y-4']) }} aria-label="{{ $title ?? __('Timeline') }}">
    @if ($title || isset($actions) || isset($composer))
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                @if ($title)
                    <h2 class="text-sm font-semibold text-ink-heading">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    @isset($composer)
        <div class="rounded-lg border border-line bg-surface-muted/40 p-4">{{ $composer }}</div>
    @endisset

    @if ($empty ?? $slot->isEmpty())
        <x-ui.empty-state
            :title="$emptyTitle ?? __('No activity yet')"
            :description="$emptyDescription ?? __('Notes, status changes, and system events will appear here.')"
            class="!py-8"
        >
            <x-slot:icon>
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-ui.empty-state>
    @else
        <ol class="relative space-y-0 border-s border-line ms-3 ps-6">
            {{ $slot }}
        </ol>
    @endif
</section>
