@if ($recentlyViewed->isNotEmpty())
    <section aria-label="{{ __('Recently viewed documentation') }}" class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Recently Viewed') }}</h2>
        <ul class="space-y-2">
            @foreach ($recentlyViewed as $item)
                <li>
                    <a href="{{ $item['url'] }}" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        {{ $item['title'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
