<x-platform-layout>
    <x-layouts.edit
        :title="__('Edit Organization')"
        :subtitle="$organization->name"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Organizations'), 'href' => route('platform.organizations.index')],
                ['label' => $organization->name, 'href' => route('platform.organizations.show', $organization)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('platform.organizations.update', $organization) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <x-forms.section :title="__('Organization Details')">
                <x-forms.field :label="__('Name')" name="name" required>
                    <x-forms.input name="name" value="{{ old('name', $organization->name) }}" required />
                </x-forms.field>
                <x-forms.field :label="__('Email')" name="email">
                    <x-forms.input type="email" name="email" value="{{ old('email', $organization->email) }}" />
                </x-forms.field>
                <x-forms.field :label="__('Phone')" name="phone">
                    <x-forms.input name="phone" value="{{ old('phone', $organization->phone) }}" />
                </x-forms.field>
                <x-forms.field :label="__('Website')" name="website">
                    <x-forms.input type="url" name="website" value="{{ old('website', $organization->website) }}" />
                </x-forms.field>
                <x-forms.field :label="__('Plan')" name="plan" required>
                    <x-forms.select name="plan" required>
                        @foreach ($plans as $value => $label)
                            <option value="{{ $value }}" @selected(old('plan', $organization->plan) === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Timezone')" name="timezone">
                    <x-forms.select name="timezone">
                        <option value="">{{ __('Use system default') }}</option>
                        @foreach ($timezones as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $organization->timezone) === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Currency')" name="currency">
                    <x-forms.select name="currency">
                        <option value="">{{ __('Use system default') }}</option>
                        @foreach ($currencies as $code => $label)
                            <option value="{{ $code }}" @selected(old('currency', $organization->currency) === $code)>{{ $code }} — {{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Tax Name')" name="tax_name">
                    <x-forms.input name="tax_name" value="{{ old('tax_name', $organization->tax_name) }}" />
                </x-forms.field>
            </x-forms.section>

            <x-forms.footer :cancel-href="route('platform.organizations.show', $organization)" :submit-label="__('Save Changes')" />
        </form>
    </x-layouts.edit>
</x-platform-layout>
