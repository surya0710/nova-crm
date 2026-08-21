@php
    $chipFilters = $filters ?? [];
    $chipRoute = $chipRoute ?? $savedFilterRoute ?? '';
    $chipLabels = $chipLabels ?? [];
    $exclude = $exclude ?? ['metadata_filters', 'metadata_sort', 'metadata_sort_key', 'metadata_sort_direction', 'saved_filter', 'view', 'sort', 'sort_direction', 'page'];
    $chips = [];

    foreach ($chipFilters as $key => $value) {
        if (in_array($key, $exclude, true) || $value === null || $value === '' || $value === []) {
            continue;
        }

        if ($key === 'assigned_to') {
            $label = $chipLabels['assigned_to'] ?? __('Owner');
            $display = collect($assignees ?? [])->firstWhere('id', (int) $value)?->name ?? $value;
        } elseif (isset($chipLabels[$key]) && is_array($chipLabels[$key])) {
            $label = $chipLabels[$key]['_label'] ?? ucfirst(str_replace('_', ' ', $key));
            $display = $chipLabels[$key][$value] ?? $value;
        } else {
            $label = $chipLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
            $display = is_array($value) ? implode(', ', $value) : $value;
        }

        $without = $chipFilters;
        unset($without[$key]);
        if (($without['saved_filter'] ?? null) && $key !== 'saved_filter') {
            unset($without['saved_filter']);
        }

        $chips[] = [
            'key' => $key,
            'label' => $label,
            'display' => $display,
            'href' => $chipRoute ? route($chipRoute, array_filter($without, fn ($item) => $item !== null && $item !== '')) : '#',
        ];
    }

    $hasChips = $chips !== [];
    $clearHref = $clearHref ?? ($chipRoute ? route($chipRoute, ['view' => 'all']) : null);
@endphp

@if ($hasChips || $clearHref)
    <div class="flex flex-wrap items-center gap-2" data-filter-chips>
        @foreach ($chips as $chip)
            <a href="{{ $chip['href'] }}" class="inline-flex items-center gap-1 rounded-full border border-line bg-surface-muted px-2.5 py-1 text-xs text-ink hover:bg-surface-muted/80">
                <span class="font-medium">{{ $chip['label'] }}:</span>
                <span>{{ $chip['display'] }}</span>
                <span class="text-ink-muted" aria-hidden="true">×</span>
                <span class="sr-only">{{ __('Remove :filter', ['filter' => $chip['label']]) }}</span>
            </a>
        @endforeach
        @if ($clearHref)
            <a href="{{ $clearHref }}" class="text-xs font-medium text-primary-700 hover:text-primary-800">{{ __('Clear filters') }}</a>
        @endif
    </div>
@endif
