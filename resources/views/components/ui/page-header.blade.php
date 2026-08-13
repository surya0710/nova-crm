@props(['title', 'subtitle' => null])
@php
    $title = is_string($title) ? $title : (is_scalar($title) ? (string) $title : '');
    $subtitle = ($subtitle === null || is_string($subtitle))
        ? $subtitle
        : (is_scalar($subtitle) ? (string) $subtitle : null);
@endphp
<div {{ $attributes->class(['mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        @isset($breadcrumbs)<div class="mb-2">{{ $breadcrumbs }}</div>@endisset
        <h1 class="text-xl font-semibold text-ink-heading truncate sm:text-2xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-ink-muted">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2 shrink-0">{{ $actions }}</div>@endisset
</div>
