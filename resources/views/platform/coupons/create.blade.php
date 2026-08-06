<x-platform-layout>
    <x-layouts.create
        :title="__('New Coupon')"
        :subtitle="__('Create a subscription discount code')"
        max-width="3xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Coupons'), 'href' => route('platform.coupons.index')],
                ['label' => __('New Coupon'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('platform.coupons.store') }}" class="space-y-6">
            @csrf

            <x-forms.section :title="__('Coupon Details')">
                <x-forms.field :label="__('Code')" name="code" required>
                    <x-forms.input name="code" value="{{ old('code') }}" required class="uppercase" />
                </x-forms.field>
                <x-forms.field :label="__('Name')" name="name" required>
                    <x-forms.input name="name" value="{{ old('name') }}" required />
                </x-forms.field>
                <x-forms.field :label="__('Type')" name="type" required>
                    <x-forms.select name="type" required>
                        <option value="percent" @selected(old('type', 'percent') === 'percent')>{{ __('Percent') }}</option>
                        <option value="fixed" @selected(old('type') === 'fixed')>{{ __('Fixed Amount') }}</option>
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Value')" name="value" required>
                    <x-forms.input type="number" name="value" value="{{ old('value') }}" min="0" step="0.01" required />
                </x-forms.field>
                <x-forms.field :label="__('Applies to Plan')" name="applies_to_plan">
                    <x-forms.select name="applies_to_plan">
                        <option value="">{{ __('Any plan') }}</option>
                        @foreach ($plans as $value => $label)
                            <option value="{{ $value }}" @selected(old('applies_to_plan') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Max Redemptions')" name="max_redemptions">
                    <x-forms.input type="number" name="max_redemptions" value="{{ old('max_redemptions') }}" min="1" />
                </x-forms.field>
                <x-forms.field :label="__('Starts At')" name="starts_at">
                    <x-forms.input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Ends At')" name="ends_at">
                    <x-forms.input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" />
                </x-forms.field>
                <x-forms.field name="is_active" class="sm:col-span-2">
                    <x-forms.checkbox name="is_active" value="1" :label="__('Active')" @checked(old('is_active', true)) />
                </x-forms.field>
            </x-forms.section>

            <x-forms.footer :cancel-href="route('platform.coupons.index')" :submit-label="__('Create Coupon')" />
        </form>
    </x-layouts.create>
</x-platform-layout>
