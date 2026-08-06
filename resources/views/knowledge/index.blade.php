<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Knowledge Center') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Explore module documentation, architecture notes, and API guides.') }}</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
            @include('knowledge.partials.search')
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <aside class="lg:col-span-3">
                @include('knowledge.partials.sidebar', ['navigationTree' => $navigationTree])
            </aside>

            <section class="lg:col-span-6 rounded-xl bg-white border border-slate-200 shadow-sm p-5 sm:p-6">
                <h2 class="text-base font-semibold text-slate-900">{{ __('Documentation Modules') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('Select a module to view its documentation pages.') }}</p>

                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($modules as $module)
                        <a href="{{ $module['url'] }}" class="rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                            <p class="text-sm font-medium text-slate-900">{{ $module['title'] }}</p>
                        </a>
                    @endforeach
                </div>

                @if ($modules->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">{{ __('No modules are currently available.') }}</p>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
