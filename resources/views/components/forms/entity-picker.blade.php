@props([
    'entity',
    'name' => null,
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'endpoint' => null,
])

@php
    $endpoint = $endpoint ?: route('shell.lookups.search', ['entity' => $entity]);
    $placeholder = $placeholder ?: __('Search…');
    $inputId = 'entity-picker-'.uniqid();
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="entityPicker({
        endpoint: @js($endpoint),
        initialValue: @js($value),
        inputName: @js($name),
        placeholder: @js($placeholder),
        debounceMs: @js((int) config('lookups.debounce_ms', 300)),
        minSearchLength: @js((int) config('lookups.min_search_length', 0)),
    })"
>
    @if ($name)
        <input type="hidden" name="{{ $name }}" :value="selectedId" @if($required) required @endif>
    @endif

    <div x-show="selectedItem" x-cloak class="mb-2 flex items-center justify-between gap-2 rounded-md border border-line bg-surface-muted/50 px-3 py-2">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-ink-heading" x-text="selectedItem?.label"></p>
            <p class="truncate text-xs text-ink-muted" x-show="selectedItem?.subtitle" x-text="selectedItem?.subtitle"></p>
        </div>
        <span
            x-show="selectedItem?.badge"
            class="shrink-0 rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700"
            x-text="selectedItem?.badge"
        ></span>
        <button type="button" class="shrink-0 text-xs text-ink-muted hover:text-ink" @click="clearSelection()">
            {{ __('Clear') }}
        </button>
    </div>

    <div class="relative" x-show="!selectedItem || open">
        <input
            id="{{ $inputId }}"
            type="text"
            class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"
            :placeholder="placeholder"
            x-model="query"
            @input="onQueryInput()"
            @focus="openDropdown()"
            @keydown.down.prevent="highlightNext()"
            @keydown.up.prevent="highlightPrev()"
            @keydown.enter.prevent="selectHighlighted()"
            @keydown.escape="closeDropdown()"
            autocomplete="off"
        >
        <div
            x-show="open"
            x-cloak
            class="absolute z-40 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-line bg-surface-card shadow-lg"
            @scroll="onScroll($event)"
        >
            <div x-show="loading" class="px-3 py-2 text-sm text-ink-muted">{{ __('Loading…') }}</div>
            <div x-show="!loading && results.length === 0" class="px-3 py-2 text-sm text-ink-muted">{{ __('No results found.') }}</div>
            <template x-for="(item, index) in results" :key="item.id">
                <button
                    type="button"
                    class="flex w-full items-start justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-surface-muted/60"
                    :class="index === highlighted ? 'bg-surface-muted/80' : ''"
                    @click="selectItem(item)"
                    @mouseenter="highlighted = index"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-ink-heading" x-text="item.label"></p>
                        <p class="truncate text-xs text-ink-muted" x-show="item.subtitle" x-text="item.subtitle"></p>
                    </div>
                    <span
                        x-show="item.badge"
                        class="shrink-0 rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted"
                        x-text="item.badge"
                    ></span>
                </button>
            </template>
            <div x-show="loadingMore" class="px-3 py-2 text-center text-xs text-ink-muted">{{ __('Loading more…') }}</div>
        </div>
    </div>
</div>

@once
@include('components.forms.partials.entity-picker-script')
@endonce
