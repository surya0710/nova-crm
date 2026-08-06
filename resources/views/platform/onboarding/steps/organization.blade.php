<form id="onboarding-step-form" method="post" action="{{ route('platform.onboarding.steps', $onboarding) }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="step" value="organization">

    <div class="grid gap-4 sm:grid-cols-2">
        <x-forms.field :label="__('Organization name')" name="name" required>
            <x-forms.input name="name" value="{{ old('name', $stepData['name'] ?? '') }}" required />
        </x-forms.field>
        <x-forms.field :label="__('Industry')" name="industry">
            <x-forms.input name="industry" value="{{ old('industry', $stepData['industry'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Plan')" name="plan" required>
            <x-forms.select name="plan" required>
                @foreach ($plans as $value => $label)
                    <option value="{{ $value }}" @selected(old('plan', $stepData['plan'] ?? 'starter') === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Timezone')" name="timezone">
            <x-forms.select name="timezone">
                @foreach ($timezones as $tz)
                    <option value="{{ $tz }}" @selected(old('timezone', $stepData['timezone'] ?? 'UTC') === $tz)>{{ $tz }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Currency')" name="currency">
            <x-forms.select name="currency">
                @foreach ($currencies as $code => $label)
                    <option value="{{ is_string($code) ? $code : $label }}" @selected(old('currency', $stepData['currency'] ?? 'USD') === (is_string($code) ? $code : $label))>
                        {{ is_string($code) ? $code.' — '.$label : $label }}
                    </option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Date format')" name="date_format">
            <x-forms.input name="date_format" value="{{ old('date_format', $stepData['date_format'] ?? 'Y-m-d') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Fiscal year start (MM-DD)')" name="fiscal_year_start">
            <x-forms.input name="fiscal_year_start" value="{{ old('fiscal_year_start', $stepData['fiscal_year_start'] ?? '01-01') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Email')" name="email">
            <x-forms.input type="email" name="email" value="{{ old('email', $stepData['email'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Phone')" name="phone">
            <x-forms.input name="phone" value="{{ old('phone', $stepData['phone'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Website')" name="website" class="sm:col-span-2">
            <x-forms.input name="website" value="{{ old('website', $stepData['website'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Address line 1')" name="address_line_1" class="sm:col-span-2">
            <x-forms.input name="address_line_1" value="{{ old('address_line_1', $stepData['address_line_1'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('City')" name="city">
            <x-forms.input name="city" value="{{ old('city', $stepData['city'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Country')" name="country">
            <x-forms.input name="country" value="{{ old('country', $stepData['country'] ?? '') }}" />
        </x-forms.field>
    </div>

    @include('platform.onboarding.partials.actions')
</form>
