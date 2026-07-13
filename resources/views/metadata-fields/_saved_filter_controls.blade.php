@php
    $activeSavedFilter = $activeSavedFilter ?? null;
    $savedFilters = $savedFilters ?? collect();
    $savedFilterRoute = $savedFilterRoute ?? '';
    $savedFilterEntityType = $savedFilterEntityType ?? '';
    $filterFormId = $filterFormId ?? '';
@endphp

<div class="lg:col-span-full border-t border-slate-100 pt-3 space-y-3" data-saved-filter-controls>
    <div class="flex flex-col lg:flex-row lg:items-end gap-3">
        <div class="flex-1">
            <label for="saved_filter_select" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">
                {{ __('Saved filters') }}
            </label>
            <select
                id="saved_filter_select"
                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                onchange="if (this.value) { window.location = this.value; }"
            >
                <option value="">{{ __('Load saved filter…') }}</option>
                @if ($activeSavedFilter)
                    <option value="{{ route($savedFilterRoute) }}">{{ __('Clear saved filter') }}</option>
                @endif
                @foreach ($savedFilters as $savedFilter)
                    <option
                        value="{{ route($savedFilterRoute, ['saved_filter' => $savedFilter->id]) }}"
                        @selected($activeSavedFilter?->id === $savedFilter->id)
                    >
                        {{ $savedFilter->name }}
                        @if ($savedFilter->isShared())
                            ({{ __('Shared') }})
                        @endif
                        @if ($savedFilter->validation_status !== 'valid')
                            ({{ __('Needs review') }})
                        @endif
                    </option>
                @endforeach
            </select>
            @if ($savedFilters->isEmpty() && ! $activeSavedFilter)
                <p class="mt-1 text-xs text-slate-500">{{ __('Save your current filters to reuse them later.') }}</p>
            @endif
        </div>

        <details class="flex-1 rounded-lg border border-slate-200 p-3">
            <summary class="cursor-pointer text-sm font-semibold text-slate-700">{{ __('Save current filters') }}</summary>
            <form
                method="POST"
                action="{{ route('saved-filters.store') }}"
                class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3"
                data-saved-filter-sync-from="{{ $filterFormId }}"
            >
                @csrf
                <input type="hidden" name="entity_type" value="{{ $savedFilterEntityType }}">
                <input type="hidden" name="redirect_route" value="{{ $savedFilterRoute }}">
                <div>
                    <x-input-label for="saved_filter_name" :value="__('Name')" />
                    <x-text-input id="saved_filter_name" name="name" class="block mt-1 w-full" required />
                </div>
                <div>
                    <x-input-label for="saved_filter_visibility" :value="__('Visibility')" />
                    <select id="saved_filter_visibility" name="visibility" class="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="private">{{ __('Private') }}</option>
                        <option value="shared">{{ __('Shared within organization') }}</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="saved_filter_description" :value="__('Description')" />
                    <textarea id="saved_filter_description" name="description" rows="2" class="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"></textarea>
                </div>
                <div class="md:col-span-2">
                    <x-primary-button type="submit">{{ __('Save filter') }}</x-primary-button>
                </div>
            </form>
        </details>
    </div>

    @if ($activeSavedFilter)
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-lg bg-slate-50 border border-slate-200 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-slate-900">{{ __('Active saved filter: :name', ['name' => $activeSavedFilter->name]) }}</p>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ $activeSavedFilter->isShared() ? __('Shared within organization') : __('Private') }}
                </p>
                @if ($activeSavedFilter->validation_status !== 'valid')
                    <p class="text-xs text-amber-700 mt-1">{{ __('Some filter criteria are no longer available. Results may be incomplete.') }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $activeSavedFilter)
                    <details>
                        <summary class="cursor-pointer inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            {{ __('Rename') }}
                        </summary>
                        <form method="POST" action="{{ route('saved-filters.update', $activeSavedFilter) }}" class="mt-2 flex flex-col gap-2 min-w-[16rem]">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="redirect_route" value="{{ $savedFilterRoute }}">
                            <x-text-input name="name" :value="$activeSavedFilter->name" class="w-full text-sm" required />
                            <textarea name="description" rows="2" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ $activeSavedFilter->description }}</textarea>
                            <select name="visibility" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="private" @selected($activeSavedFilter->visibility === 'private')>{{ __('Private') }}</option>
                                <option value="shared" @selected($activeSavedFilter->visibility === 'shared')>{{ __('Shared within organization') }}</option>
                            </select>
                            <x-primary-button type="submit">{{ __('Update filter') }}</x-primary-button>
                        </form>
                    </details>
                    <form
                        method="POST"
                        action="{{ route('saved-filters.update', $activeSavedFilter) }}"
                        data-saved-filter-sync-from="{{ $filterFormId }}"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="redirect_route" value="{{ $savedFilterRoute }}">
                        <input type="hidden" name="update_filter_definition" value="1">
                        <input type="hidden" name="name" value="{{ $activeSavedFilter->name }}">
                        <input type="hidden" name="description" value="{{ $activeSavedFilter->description }}">
                        <input type="hidden" name="visibility" value="{{ $activeSavedFilter->visibility }}">
                        <button type="submit" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            {{ __('Update criteria') }}
                        </button>
                    </form>
                @endcan
                @can('duplicate', $activeSavedFilter)
                    <form method="POST" action="{{ route('saved-filters.duplicate', $activeSavedFilter) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            {{ __('Duplicate') }}
                        </button>
                    </form>
                @endcan
                @can('delete', $activeSavedFilter)
                    <form method="POST" action="{{ route('saved-filters.destroy', $activeSavedFilter) }}" onsubmit="return confirm(@js(__('Delete this saved filter?')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                            {{ __('Delete') }}
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    @endif

    @error('saved_filter')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function syncFilterFormToTarget(form) {
                const filterFormId = form.dataset.savedFilterSyncFrom;
                const filterForm = filterFormId ? document.getElementById(filterFormId) : null;

                if (!filterForm) {
                    return;
                }

                form.querySelectorAll('[data-saved-filter-sync]').forEach(function (element) {
                    element.remove();
                });

                const formData = new FormData(filterForm);

                formData.forEach(function (value, name) {
                    if (!name || name === '_token' || name === '_method') {
                        return;
                    }

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    input.dataset.savedFilterSync = '1';
                    form.appendChild(input);
                });
            }

            document.querySelectorAll('[data-saved-filter-sync-from]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    syncFilterFormToTarget(form);
                });
            });
        });
    </script>
@endonce
