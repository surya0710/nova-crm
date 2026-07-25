@php
    $tagsValue = old('tags', isset($customer->tags) ? implode(', ', $customer->tags ?? []) : '');
@endphp

<div class="space-y-8">
    <x-forms.section :title="__('Contact Information')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Contact Name')" name="name" required>
                <x-forms.input id="name" type="text" name="name" :value="old('name', $customer->name)" required />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('Company')" name="company">
            <x-forms.input id="company" type="text" name="company" :value="old('company', $customer->company)" />
        </x-forms.field>
        <x-forms.field :label="__('Industry')" name="industry">
            <x-forms.input id="industry" type="text" name="industry" :value="old('industry', $customer->industry)" />
        </x-forms.field>
        <x-forms.field :label="__('Email')" name="email">
            <x-forms.input id="email" type="email" name="email" :value="old('email', $customer->email)" />
        </x-forms.field>
        <x-forms.field :label="__('Phone')" name="phone">
            <x-forms.input id="phone" type="text" name="phone" :value="old('phone', $customer->phone)" />
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Website')" name="website">
                <x-forms.input id="website" type="url" name="website" :value="old('website', $customer->website)" placeholder="https://" />
            </x-forms.field>
        </div>
    </x-forms.section>

    <x-forms.section :title="__('Address & Tax')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Address Line 1')" name="address_line_1">
                <x-forms.input id="address_line_1" type="text" name="address_line_1" :value="old('address_line_1', $customer->address_line_1)" />
            </x-forms.field>
        </div>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Address Line 2')" name="address_line_2">
                <x-forms.input id="address_line_2" type="text" name="address_line_2" :value="old('address_line_2', $customer->address_line_2)" />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('City')" name="city">
            <x-forms.input id="city" type="text" name="city" :value="old('city', $customer->city)" />
        </x-forms.field>
        <x-forms.field :label="__('State / Province')" name="state">
            <x-forms.input id="state" type="text" name="state" :value="old('state', $customer->state)" />
        </x-forms.field>
        <x-forms.field :label="__('Postal Code')" name="postal_code">
            <x-forms.input id="postal_code" type="text" name="postal_code" :value="old('postal_code', $customer->postal_code)" />
        </x-forms.field>
        <x-forms.field :label="__('Country')" name="country">
            <x-forms.input id="country" type="text" name="country" :value="old('country', $customer->country)" />
        </x-forms.field>
        <x-forms.field :label="__('Tax Number')" name="tax_number">
            <x-forms.input id="tax_number" type="text" name="tax_number" :value="old('tax_number', $customer->tax_number)" />
        </x-forms.field>
    </x-forms.section>

    <x-forms.section :title="__('Account')">
        <x-forms.field :label="__('Status')" name="status" required>
            <x-forms.select id="status" name="status" required>
                @foreach (config('customers.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $customer->status) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Account Manager')" name="assigned_to">
            <x-forms.select id="assigned_to" name="assigned_to">
                <option value="">{{ __('Unassigned') }}</option>
                @foreach ($assignees as $member)
                    <option value="{{ $member->id }}" @selected(old('assigned_to', $customer->assigned_to) == $member->id)>{{ $member->name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Tags')" name="tags" :hint="__('Comma-separated tags')">
                <x-forms.input id="tags" type="text" name="tags" :value="$tagsValue" placeholder="vip, enterprise, recurring" />
            </x-forms.field>
        </div>
    </x-forms.section>

    @include('metadata-fields._runtime_form', [
        'metadataFields' => $metadataFields ?? collect(),
        'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
        'record' => $customer,
    ])
</div>
