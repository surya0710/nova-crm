@props(['documents' => collect()])

@if ($documents->isNotEmpty())
    <section aria-label="{{ __('Related documentation') }}" class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Related Documentation') }}</h2>
        <ul class="space-y-2">
            @foreach ($documents as $document)
                <li>
                    <a href="{{ $document['url'] }}" class="block rounded-md px-3 py-2 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="text-sm font-medium text-slate-900">{{ $document['title'] }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500">
                            {{ $document['module_name'] }} · {{ str($document['category'])->replace('-', ' ')->title() }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
