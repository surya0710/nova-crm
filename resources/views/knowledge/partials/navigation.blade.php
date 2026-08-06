<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4 min-h-24">
        @if ($previous)
            <p class="text-xs text-slate-500 mb-1">{{ __('Previous') }}</p>
            <a href="{{ $previous['url'] }}" class="text-sm font-medium text-indigo-700 hover:underline">{{ $previous['title'] }}</a>
        @endif
    </div>
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4 min-h-24 text-left sm:text-right">
        @if ($next)
            <p class="text-xs text-slate-500 mb-1">{{ __('Next') }}</p>
            <a href="{{ $next['url'] }}" class="text-sm font-medium text-indigo-700 hover:underline">{{ $next['title'] }}</a>
        @endif
    </div>
</div>
