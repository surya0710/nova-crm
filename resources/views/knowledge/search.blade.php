<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Search Documentation') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Find guides, references, and troubleshooting pages.') }}</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
            @include('knowledge.partials.search', ['query' => $query])
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <aside class="lg:col-span-3">
                @include('knowledge.partials.sidebar', ['navigationTree' => $navigationTree])
            </aside>

            <section class="lg:col-span-9 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden" aria-live="polite">
                @if ($query === '')
                    <div class="p-8 text-sm text-slate-500">
                        {{ __('Enter a search term to find documentation across all enabled modules.') }}
                    </div>
                @elseif ($results->isEmpty())
                    <div class="p-8 text-sm text-slate-500">
                        {{ __('No documentation found for ":query".', ['query' => $query]) }}
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($results as $result)
                            <li>
                                <a href="{{ $result['url'] }}" class="block px-6 py-4 hover:bg-slate-50">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $result['title'] }}</p>
                                            @if (! empty($result['heading']))
                                                <p class="text-xs text-slate-500 mt-1">{{ $result['heading'] }}</p>
                                            @endif
                                            <p class="text-sm text-slate-600 mt-2">{!! $result['snippet'] !!}</p>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-indigo-50 text-indigo-700 px-2 py-1 text-xs">
                                            {{ $result['module_name'] }}
                                        </span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
