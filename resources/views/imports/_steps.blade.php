@props(['current' => 'upload'])
@php
    $order = ['upload', 'preview', 'summary'];
    $labels = [
        'upload' => __('Upload'),
        'preview' => __('Preview'),
        'summary' => __('Summary'),
    ];
    $currentIndex = (int) array_search($current, $order, true);
@endphp
<div class="flex flex-wrap items-center gap-2" aria-label="{{ __('Import progress') }}">
    @foreach ($order as $index => $key)
        @if ($index > 0)
            <span class="text-ink-muted" aria-hidden="true">→</span>
        @endif
        <x-ui.badge :variant="$index === $currentIndex ? 'primary' : ($index < $currentIndex ? 'success' : 'neutral')">
            {{ $labels[$key] }}
        </x-ui.badge>
    @endforeach
</div>
