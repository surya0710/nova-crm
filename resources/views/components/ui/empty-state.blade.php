@props(['title' => null, 'description' => null])
<div {{ $attributes->class(['flex flex-col items-center justify-center text-center px-6 py-12']) }}>
    @isset($icon)<div class="mb-4 text-ink-muted">{{ $icon }}</div>@endisset
    @if ($title)<h3 class="text-base font-semibold text-ink-heading">{{ $title }}</h3>@endif
    @if ($description)<p class="mt-1 max-w-sm text-sm text-ink-muted">{{ $description }}</p>@endif
    @isset($actions)<div class="mt-4 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>@endisset
    {{ $slot }}
</div>
