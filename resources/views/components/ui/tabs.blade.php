@props(['tabs' => []])
<nav {{ $attributes->class(['flex gap-1 overflow-x-auto border-b border-line']) }} role="tablist">
    @foreach ($tabs as $tab)
        <a
            href="{{ $tab['href'] ?? '#' }}"
            @class([
                'px-3 py-2 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition',
                'border-primary-600 text-primary-700' => $tab['active'] ?? false,
                'border-transparent text-ink-muted hover:text-ink hover:border-line-strong' => ! ($tab['active'] ?? false),
            ])
            @if ($tab['active'] ?? false) aria-current="page" @endif
        >{{ $tab['label'] }}</a>
    @endforeach
    {{ $slot }}
</nav>
