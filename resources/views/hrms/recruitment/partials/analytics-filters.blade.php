@props([
    'action',
    'filters' => [],
    'periods' => [],
    'extra' => [],
])

<form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3 mb-6">
    <x-forms.field :label="__('Period')" name="period" class="mb-0">
        <x-forms.select name="period">
            @foreach ($periods as $value => $label)
                <option value="{{ $value }}" @selected(($filters['period'] ?? 'month') === $value)>{{ __($label) }}</option>
            @endforeach
        </x-forms.select>
    </x-forms.field>
    <x-forms.field :label="__('From')" name="from" class="mb-0">
        <x-forms.input type="date" name="from" :value="$filters['from'] ?? ''" />
    </x-forms.field>
    <x-forms.field :label="__('To')" name="to" class="mb-0">
        <x-forms.input type="date" name="to" :value="$filters['to'] ?? ''" />
    </x-forms.field>
    @foreach ($extra as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Apply') }}</x-ui.button>
</form>
