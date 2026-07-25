@props([
    'title' => null,
])
<aside {{ $attributes->class(['rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden']) }} aria-label="{{ $title ?? __('Needs attention') }}">
    <div class="border-b border-line bg-surface-muted/50 px-4 py-3">
        <h2 class="text-sm font-semibold text-ink-heading">{{ $title ?? __('Needs attention') }}</h2>
    </div>
    @if ($slot->isEmpty())
        <div class="px-4 py-6 text-sm text-ink-muted">{{ __('You are all caught up.') }}</div>
    @else
        <ul class="divide-y divide-line">{{ $slot }}</ul>
    @endif
</aside>
