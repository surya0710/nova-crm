<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Search') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Find records across your CRM') }}</p>
        </div>
    </x-slot>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('search.index') }}" class="flex gap-3">
            <x-text-input name="q" :value="$query" placeholder="{{ __('Search leads, customers, invoices…') }}" class="flex-1" autofocus />
            <x-primary-button type="submit">{{ __('Search') }}</x-primary-button>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($query === '')
            <div class="p-12 text-center text-sm text-slate-500">{{ __('Enter a search term to get started.') }}</div>
        @elseif ($results->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No results for “:query”.', ['query' => $query]) }}</div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($results as $result)
                    <a href="{{ $result['url'] }}" class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-slate-50 transition">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $result['title'] }}</p>
                            @if ($result['subtitle'])
                                <p class="text-xs text-slate-500 truncate">{{ $result['subtitle'] }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">{{ $result['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
