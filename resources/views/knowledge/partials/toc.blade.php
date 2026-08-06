@if (! empty($toc))
    <aside aria-label="{{ __('Table of contents') }}" class="mb-6 lg:sticky lg:top-6">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('On this page') }}</h2>
            <ul class="space-y-1 text-sm">
                @foreach ($toc as $item)
                    <li class="{{ $item['level'] === 2 ? 'ml-3' : ($item['level'] === 3 ? 'ml-6' : '') }}">
                        <a href="#{{ $item['anchor'] }}" class="text-slate-600 hover:text-indigo-700">
                            {{ $item['title'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </aside>
@endif
