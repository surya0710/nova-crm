<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Knowledge Center') }}</h1>
            <p class="text-sm text-slate-500">{{ $document['title'] }}</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
            @include('knowledge.partials.search')
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="xl:col-span-3">
                @include('knowledge.partials.sidebar', ['navigationTree' => $navigationTree])
            </aside>

            <article class="xl:col-span-6 space-y-4">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4 sm:p-5">
                    @include('knowledge.partials.breadcrumbs', ['breadcrumbs' => $document['breadcrumbs']])

                    <div class="prose prose-slate max-w-none prose-headings:scroll-mt-20 prose-pre:bg-slate-900 prose-pre:text-slate-100">
                        {!! $document['html'] !!}
                    </div>
                </div>

                @include('knowledge.partials.navigation', ['previous' => $previous, 'next' => $next])
            </article>

            <aside class="xl:col-span-3 space-y-4">
                @include('knowledge.partials.toc', ['toc' => $document['toc'] ?? []])
                <x-related-documents :documents="$relatedDocuments" />
                @include('knowledge.partials.recently-viewed', ['recentlyViewed' => $recentlyViewed])
            </aside>
        </div>
    </div>
</x-app-layout>
