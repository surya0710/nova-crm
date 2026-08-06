<nav class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mb-4">
    @foreach ($breadcrumbs as $index => $crumb)
        @if ($index > 0)
            <span>/</span>
        @endif
        @if ($crumb['url'])
            <a href="{{ $crumb['url'] }}" class="hover:text-indigo-600">{{ $crumb['title'] }}</a>
        @else
            <span class="text-slate-700">{{ $crumb['title'] }}</span>
        @endif
    @endforeach
</nav>
