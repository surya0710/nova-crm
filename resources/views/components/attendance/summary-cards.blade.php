@props(['summary' => []])

@php
    $cards = [
        ['key' => 'present', 'label' => __('Present')],
        ['key' => 'leave_approved', 'label' => __('Leave')],
        ['key' => 'absent', 'label' => __('Absent')],
        ['key' => 'holiday', 'label' => __('Holiday')],
        ['key' => 'weekend', 'label' => __('Weekend')],
        ['key' => 'late', 'label' => __('Late')],
        ['key' => 'half_day', 'label' => __('Half Day')],
    ];
@endphp

<div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
    @foreach ($cards as $card)
        <x-ui.stat-card :label="$card['label']" :value="$summary[$card['key']] ?? 0" />
    @endforeach
</div>
