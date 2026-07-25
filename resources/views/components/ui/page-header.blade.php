@props(['title', 'subtitle' => null])
<div {{ $attributes->class(['mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        @isset($breadcrumbs)<div class="mb-2">{{ $breadcrumbs }}</div>@endisset
        <h1 class="text-xl font-semibold text-ink-heading truncate sm:text-2xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-ink-muted">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2 shrink-0">{{ $actions }}</div>@endisset
</div>
