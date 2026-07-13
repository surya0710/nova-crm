@php
    $metadataFilterRows = collect($filters['metadata_filters'] ?? [])
        ->filter(fn ($filter) => is_array($filter) && filled($filter['key'] ?? null))
        ->values();

    if ($metadataFilterRows->isEmpty()) {
        $metadataFilterRows = collect([['key' => '', 'operator' => 'equals', 'value' => '']]);
    }

    $metadataSort = $filters['metadata_sort'] ?? [
        'key' => $filters['metadata_sort_key'] ?? '',
        'direction' => $filters['metadata_sort_direction'] ?? 'asc',
    ];

    $metadataOperators = [
        'equals' => __('Equals'),
        'not_equals' => __('Does not equal'),
        'contains' => __('Contains'),
        'not_contains' => __('Does not contain'),
        'starts_with' => __('Starts with'),
        'ends_with' => __('Ends with'),
        'in' => __('In list'),
        'not_in' => __('Not in list'),
        'contains_any' => __('Contains any'),
        'contains_all' => __('Contains all'),
        'contains_none' => __('Contains none'),
        'greater_than' => __('Greater than'),
        'greater_than_or_equal' => __('Greater than or equal'),
        'less_than' => __('Less than'),
        'less_than_or_equal' => __('Less than or equal'),
        'between' => __('Between'),
        'before' => __('Before'),
        'after' => __('After'),
        'true' => __('True'),
        'false' => __('False'),
        'empty' => __('Empty'),
        'not_empty' => __('Not empty'),
    ];
@endphp

@if ($metadataFilterFields->isNotEmpty() || $metadataSortFields->isNotEmpty())
    <div class="lg:col-span-full grid grid-cols-1 md:grid-cols-4 gap-3 border-t border-slate-100 pt-3">
        @if ($metadataFilterFields->isNotEmpty())
            @foreach ($metadataFilterRows as $index => $metadataFilter)
                <select name="metadata_filters[{{ $index }}][key]" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('Metadata field') }}</option>
                    @foreach ($metadataFilterFields as $field)
                        <option value="{{ $field->key }}" @selected(($metadataFilter['key'] ?? '') === $field->key)>{{ $field->label }}</option>
                    @endforeach
                </select>
                <select name="metadata_filters[{{ $index }}][operator]" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach ($metadataOperators as $value => $label)
                        <option value="{{ $value }}" @selected(($metadataFilter['operator'] ?? 'equals') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-text-input name="metadata_filters[{{ $index }}][value]" :value="$metadataFilter['value'] ?? ''" placeholder="{{ __('Metadata value') }}" class="w-full text-sm" />
            @endforeach
        @endif

        @if ($metadataSortFields->isNotEmpty())
            <div class="flex gap-2">
                <select name="metadata_sort[key]" class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('Default sort') }}</option>
                    @foreach ($metadataSortFields as $field)
                        <option value="{{ $field->key }}" @selected(($metadataSort['key'] ?? '') === $field->key)>{{ __('Sort by :field', ['field' => $field->label]) }}</option>
                    @endforeach
                </select>
                <select name="metadata_sort[direction]" class="w-24 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="asc" @selected(($metadataSort['direction'] ?? 'asc') === 'asc')>{{ __('Asc') }}</option>
                    <option value="desc" @selected(($metadataSort['direction'] ?? 'asc') === 'desc')>{{ __('Desc') }}</option>
                </select>
            </div>
        @endif

        @error('metadata_filters')
            <p class="md:col-span-4 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
@endif
