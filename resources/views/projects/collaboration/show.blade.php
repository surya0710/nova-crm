<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Collaboration')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Collaboration'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">{{ __('Pinned') }}</h2>
            @forelse ($feed['pins'] as $pin)
                <div class="text-sm text-slate-700 py-2 border-b border-slate-100 last:border-0">
                    {{ $pin->title ?: ($pin->source_type.' #'.$pin->source_id) }}
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('No pins yet.') }}</p>
            @endforelse
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-slate-800 mb-3">{{ __('Activity') }}</h2>
            @forelse ($feed['items'] as $item)
                <div class="text-sm text-slate-600 py-2 border-b border-slate-100 last:border-0">
                    {{ $item['type'] ?? 'item' }}
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('No collaboration activity yet.') }}</p>
            @endforelse
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
