@php
    $severityColors = fn ($score) => match (true) {
        $score >= 16 => 'bg-red-100 text-red-800',
        $score >= 10 => 'bg-orange-100 text-orange-800',
        $score >= 5 => 'bg-amber-100 text-amber-800',
        default => 'bg-emerald-100 text-emerald-800',
    };
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Risks')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Risks'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        <div class="xl:col-span-1">
            @include('risks._matrix', ['matrix' => $matrix])
        </div>
        <div class="xl:col-span-2">
            @can('createRisks', $project)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Add Risk') }}</h3>
                    <form method="POST" action="{{ route('projects.risks.store', $project) }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <x-input-label for="title" :value="__('Title')" />
                                <x-text-input id="title" name="title" class="block mt-1 w-full" :value="old('title')" required />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="description" :value="__('Description')" />
                                <textarea id="description" name="description" rows="2" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                            </div>
                            <div>
                                <x-input-label for="probability" :value="__('Probability (1–5)')" />
                                <x-text-input id="probability" name="probability" type="number" min="1" max="5" class="block mt-1 w-full" :value="old('probability', 3)" />
                            </div>
                            <div>
                                <x-input-label for="impact" :value="__('Impact (1–5)')" />
                                <x-text-input id="impact" name="impact" type="number" min="1" max="5" class="block mt-1 w-full" :value="old('impact', 3)" />
                            </div>
                            <div>
                                <x-input-label for="category" :value="__('Category')" />
                                <select id="category" name="category" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach (config('projects.risk_categories') as $value => $label)
                                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                                    @foreach (config('projects.risk_statuses') as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', 'open') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <x-primary-button>{{ __('Add Risk') }}</x-primary-button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($risks->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No risks recorded for this project.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Risk') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('P×I') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Severity') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Due') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($risks as $risk)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $risk->title }}</p>
                                    @if ($risk->description)
                                        <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $risk->description }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $risk->probability }}×{{ $risk->impact }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $severityColors($risk->severity) }}">{{ $risk->severity }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ config('projects.risk_statuses')[$risk->status] ?? $risk->status }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $risk->due_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('delete', $risk)
                                        <form method="POST" action="{{ route('projects.risks.destroy', [$project, $risk]) }}" class="inline" onsubmit="return confirm('{{ __('Delete this risk?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
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
