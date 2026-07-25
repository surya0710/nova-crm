<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Baselines')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Baselines'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    @can('createBaselines', $project)
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5 mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Capture Baseline') }}</h3>
            <form method="POST" action="{{ route('projects.baselines.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @csrf
                <div>
                    <x-input-label for="name" :value="__('Name (optional)')" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="notes" :value="__('Notes')" />
                    <x-text-input id="notes" name="notes" class="block mt-1 w-full" :value="old('notes')" />
                </div>
                <div class="sm:col-span-3">
                    <x-primary-button>{{ __('Capture Baseline') }}</x-primary-button>
                </div>
            </form>
        </div>
    @endcan

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($baselines->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No baselines captured yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Version') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Captured By') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Date') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($baselines as $baseline)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">v{{ $baseline->version }}</td>
                                <td class="px-6 py-4 text-sm text-slate-900">{{ $baseline->name ?: __('Baseline :version', ['version' => $baseline->version]) }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $baseline->creator?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $baseline->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('projects.baselines.show', [$project, $baseline]) }}" class="text-sm text-primary-600 hover:text-primary-700">{{ __('Compare') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
