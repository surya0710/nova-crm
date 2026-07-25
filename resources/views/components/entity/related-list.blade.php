@props([
    'title',
    'emptyTitle' => null,
    'emptyDescription' => null,
])
<x-entity.section :title="$title" {{ $attributes }}>
    @if ($slot->isEmpty())
        <x-ui.empty-state
            :title="$emptyTitle ?? __('Nothing here yet')"
            :description="$emptyDescription"
            class="!py-8"
        />
    @else
        <ul class="divide-y divide-line -mx-1">
            {{ $slot }}
        </ul>
    @endif
</x-entity.section>
