@props(['items' => []])
@if (count($items))
    <nav aria-label="{{ __('Breadcrumb') }}" {{ $attributes->class(['text-sm']) }}>
        <ol class="flex flex-wrap items-center gap-1.5 text-ink-muted">
            @foreach ($items as $item)
                <li class="inline-flex items-center gap-1.5">
                    @if (! ($item['current'] ?? false) && ! empty($item['href']))
                        <a href="{{ $item['href'] }}" class="hover:text-ink truncate max-w-[12rem]">{{ $item['label'] }}</a>
                        <span aria-hidden="true" class="text-ink-muted/60">/</span>
                    @else
                        <span class="font-medium text-ink-heading truncate max-w-[16rem]" aria-current="page">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
