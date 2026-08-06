@php
    $healthColors = [
        'on_track' => 'bg-emerald-100 text-emerald-800',
        'at_risk' => 'bg-amber-100 text-amber-800',
        'delayed' => 'bg-red-100 text-red-800',
        'completed' => 'bg-indigo-100 text-indigo-800',
        'archived' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Progress')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Progress'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1">
            @can('createProgress', $project)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Post Progress Update') }}</h3>
                    <form method="POST" action="{{ route('projects.progress.store', $project) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Progress %') }}</label>
                            <x-text-input name="progress_percentage" type="number" min="0" max="100" :value="old('progress_percentage', $project->completion_percentage)" class="w-full" required />
                            <x-input-error :messages="$errors->get('progress_percentage')" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Milestone (optional)') }}</label>
                            <select name="milestone_id" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                                <option value="">{{ __('None') }}</option>
                                @foreach ($project->milestones as $milestone)
                                    <option value="{{ $milestone->id }}" @selected(old('milestone_id') == $milestone->id)>{{ $milestone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Summary') }}</label>
                            <textarea name="summary" rows="3" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" required>{{ old('summary') }}</textarea>
                            <x-input-error :messages="$errors->get('summary')" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Blockers') }}</label>
                            <textarea name="blockers" rows="2" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">{{ old('blockers') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Next Steps') }}</label>
                            <textarea name="next_steps" rows="2" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">{{ old('next_steps') }}</textarea>
                        </div>
                        <x-primary-button type="submit" class="w-full justify-center">{{ __('Submit Update') }}</x-primary-button>
                    </form>
                </div>
            @endcan

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mt-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Current Completion') }}</p>
                <p class="mt-1 text-3xl font-bold text-primary-600">{{ $project->completion_percentage }}%</p>
            </div>
        </div>

        <div class="xl:col-span-2">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Update History') }}</h3>
                </div>
                @if ($updates->isEmpty())
                    <div class="p-12 text-center text-sm text-slate-500">{{ __('No progress updates yet.') }}</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($updates as $update)
                            <div class="p-6" x-data="{ editing: false }">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-lg font-bold text-primary-600">{{ $update->progress_percentage }}%</span>
                                            @if ($update->milestone)
                                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">{{ $update->milestone->name }}</span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-sm text-slate-900 whitespace-pre-wrap">{{ $update->summary }}</p>
                                        @if ($update->blockers)
                                            <p class="mt-2 text-xs text-red-700"><span class="font-semibold">{{ __('Blockers') }}:</span> {{ $update->blockers }}</p>
                                        @endif
                                        @if ($update->next_steps)
                                            <p class="mt-1 text-xs text-slate-600"><span class="font-semibold">{{ __('Next') }}:</span> {{ $update->next_steps }}</p>
                                        @endif
                                        <p class="mt-2 text-xs text-slate-400">
                                            {{ $update->updater?->name ?? __('Unknown') }} · {{ $update->created_at?->format('M j, Y g:i A') }}
                                        </p>
                                    </div>
                                    @can('updateProgress', $project)
                                        <button type="button" @click="editing = !editing" class="text-xs text-primary-600 hover:text-primary-700 shrink-0">{{ __('Edit') }}</button>
                                    @endcan
                                </div>

                                @can('updateProgress', $project)
                                    <form x-show="editing" x-cloak method="POST" action="{{ route('projects.progress.update', [$project, $update]) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                                        @csrf
                                        @method('PATCH')
                                        <x-text-input name="progress_percentage" type="number" min="0" max="100" :value="$update->progress_percentage" class="w-full" />
                                        <textarea name="summary" rows="2" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">{{ $update->summary }}</textarea>
                                        <textarea name="blockers" rows="2" placeholder="{{ __('Blockers') }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">{{ $update->blockers }}</textarea>
                                        <textarea name="next_steps" rows="2" placeholder="{{ __('Next steps') }}" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">{{ $update->next_steps }}</textarea>
                                        <div class="flex gap-2">
                                            <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                                            @can('deleteProgress', $project)
                                                <button type="submit" formaction="{{ route('projects.progress.destroy', [$project, $update]) }}" formmethod="POST" onclick="return confirm('{{ __('Delete this update?') }}')" class="inline-flex items-center rounded-md border border-red-300 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-100">
                                                    @csrf
                                                    @method('DELETE')
                                                    {{ __('Delete') }}
                                                </button>
                                            @endcan
                                        </div>
                                    </form>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                    @if ($updates->hasPages())
                        <div class="px-6 py-4 border-t border-slate-100">{{ $updates->links() }}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
