@php
    $milestoneStatusColors = [
        'pending' => 'bg-slate-100 text-slate-600',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-slate-100 text-slate-500',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Milestones')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Milestones'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    @can('manageMilestones', $project)
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Add Milestone') }}</h3>
            <form method="POST" action="{{ route('projects.milestones.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                @csrf
                <x-text-input name="name" :value="old('name')" placeholder="{{ __('Milestone name') }}" class="w-full lg:col-span-2" required />
                <x-text-input name="due_date" type="date" :value="old('due_date')" class="w-full" />
                <x-text-input name="sequence" type="number" min="0" :value="old('sequence')" placeholder="{{ __('Sequence') }}" class="w-full" />
                <x-primary-button type="submit">{{ __('Add Milestone') }}</x-primary-button>
                <div class="sm:col-span-2 lg:col-span-5">
                    <textarea name="description" rows="2" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" placeholder="{{ __('Description (optional)') }}">{{ old('description') }}</textarea>
                </div>
            </form>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
    @endcan

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($milestones->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No milestones defined yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('#') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Milestone') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Due Date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($milestones as $milestone)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm text-slate-500">{{ $milestone->sequence }}</td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $milestone->name }}</p>
                                    @if ($milestone->description)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ Str::limit($milestone->description, 80) }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $milestone->due_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $milestoneStatusColors[$milestone->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $milestone->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @if (! $milestone->isCompleted())
                                            @can('complete', $milestone)
                                                <form method="POST" action="{{ route('projects.milestones.complete', [$project, $milestone]) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-sm text-emerald-600 hover:text-emerald-800">{{ __('Complete') }}</button>
                                                </form>
                                            @endcan
                                        @endif
                                        @can('delete', $milestone)
                                            <form method="POST" action="{{ route('projects.milestones.destroy', [$project, $milestone]) }}" class="inline" onsubmit="return confirm('{{ __('Delete this milestone?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    </div>
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
