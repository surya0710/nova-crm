@extends('layouts.app')

@section('content')
    @php
        $isEdit = $rule !== null;
    @endphp
    <div class="space-y-6">
        <x-ui.page-header :title="$isEdit ? __('Edit overtime rule') : __('Create overtime rule')">
            <x-slot:actions>
                <x-ui.button :href="route('hrms.attendance.overtime.rules')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form
                method="POST"
                action="{{ $isEdit ? route('hrms.attendance.overtime.rules.update', $rule) : route('hrms.attendance.overtime.rules.store') }}"
                class="grid grid-cols-1 gap-3 md:grid-cols-3"
            >
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <x-forms.field :label="__('Name')" name="name">
                    <x-forms.input name="name" :value="old('name', $rule?->name)" required />
                </x-forms.field>
                <x-forms.field :label="__('Code')" name="code">
                    <x-forms.input name="code" :value="old('code', $rule?->code)" />
                </x-forms.field>
                <x-forms.field :label="__('Type')" name="rule_type">
                    <x-forms.select name="rule_type" required>
                        @foreach ($ruleTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('rule_type', $rule?->rule_type ?? 'daily') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Minimum minutes')" name="minimum_minutes">
                    <x-forms.input type="number" name="minimum_minutes" :value="old('minimum_minutes', $rule?->minimum_minutes ?? 0)" />
                </x-forms.field>
                <x-forms.field :label="__('Maximum minutes')" name="maximum_minutes">
                    <x-forms.input type="number" name="maximum_minutes" :value="old('maximum_minutes', $rule?->maximum_minutes)" />
                </x-forms.field>
                <x-forms.field :label="__('Round-off minutes')" name="round_off_minutes">
                    <x-forms.input type="number" name="round_off_minutes" :value="old('round_off_minutes', $rule?->round_off_minutes ?? 0)" />
                </x-forms.field>
                <x-forms.field :label="__('Multiplier')" name="multiplier">
                    <x-forms.input type="number" step="0.01" name="multiplier" :value="old('multiplier', $rule?->multiplier ?? 1)" />
                </x-forms.field>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="requires_approval" value="1" @checked(old('requires_approval', $rule?->requires_approval))>
                    {{ __('Requires approval') }}
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule?->is_active ?? true))>
                    {{ __('Active') }}
                </label>
                <div class="md:col-span-3">
                    <x-ui.button type="submit" size="sm">{{ $isEdit ? __('Update rule') : __('Save rule') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
