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

    <x-forms.section :title="__('Address Information')">
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
        <x-forms.field :label="__('State')" name="state">
            <x-forms.input id="state" type="text" name="state" :value="old('state', $customer->state)" />
        </x-forms.field>
        <x-forms.field :label="__('Country')" name="country">
            <x-forms.input id="country" type="text" name="country" :value="old('country', $customer->country)" />
        </x-forms.field>
        <x-forms.field :label="__('Postal Code')" name="postal_code">
            <x-forms.input id="postal_code" type="text" name="postal_code" :value="old('postal_code', $customer->postal_code)" />
        </x-forms.field>
        <x-forms.field :label="__('Tax Number')" name="tax_number">
            <x-forms.input id="tax_number" type="text" name="tax_number" :value="old('tax_number', $customer->tax_number)" />
        </x-forms.field>
    </x-forms.section>

    <x-forms.section :title="__('GST / Tax Profile')" x-data="{ sameAsBilling: {{ old('shipping_same_as_billing', $customer->shipping_same_as_billing ?? true) ? 'true' : 'false' }} }">
        <x-forms.field :label="__('GSTIN')" name="gstin">
            <x-forms.input id="gstin" type="text" name="gstin" maxlength="15" :value="old('gstin', $customer->gstin)" placeholder="27AAAAA0000A1Z5" />
        </x-forms.field>
        <x-forms.field :label="__('PAN')" name="pan">
            <x-forms.input id="pan" type="text" name="pan" maxlength="10" :value="old('pan', $customer->pan)" />
        </x-forms.field>
        <x-forms.field :label="__('GST registration type')" name="gst_registration_type">
            <x-forms.select id="gst_registration_type" name="gst_registration_type">
                <option value="">{{ __('Not set') }}</option>
                @foreach (config('tax.gst_registration_types') as $value => $label)
                    <option value="{{ $value }}" @selected(old('gst_registration_type', $customer->gst_registration_type) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Tax registration status')" name="tax_registration_status">
            <x-forms.select id="tax_registration_status" name="tax_registration_status">
                <option value="">{{ __('Not set') }}</option>
                @foreach (config('tax.tax_registration_statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(old('tax_registration_status', $customer->tax_registration_status) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Billing state')" name="billing_state_code">
            <x-forms.select id="billing_state_code" name="billing_state_code">
                <option value="">{{ __('Select GST state') }}</option>
                @foreach (config('tax.states') as $code => $state)
                    <option value="{{ $code }}" @selected(old('billing_state_code', $customer->billing_state_code) === $code)>{{ $code }} — {{ $state['name'] }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Place of supply')" name="place_of_supply">
            <x-forms.select id="place_of_supply" name="place_of_supply">
                <option value="">{{ __('Same as billing state') }}</option>
                @foreach (config('tax.states') as $code => $state)
                    <option value="{{ $code }}" @selected(old('place_of_supply', $customer->place_of_supply) === $code)>{{ $code }} — {{ $state['name'] }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Tax exemption status')" name="tax_exemption_status">
            <x-forms.select id="tax_exemption_status" name="tax_exemption_status">
                @foreach (config('tax.tax_exemption_statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(old('tax_exemption_status', $customer->tax_exemption_status ?: 'not_exempt') === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Tax exemption reason')" name="tax_exemption_reason">
            <x-forms.input id="tax_exemption_reason" type="text" name="tax_exemption_reason" :value="old('tax_exemption_reason', $customer->tax_exemption_reason)" />
        </x-forms.field>
        <x-forms.field :label="__('Default tax preference')" name="default_tax_preference">
            <x-forms.select id="default_tax_preference" name="default_tax_preference">
                <option value="">{{ __('Tax exclusive') }}</option>
                @foreach (config('tax.tax_preferences') as $value => $label)
                    <option value="{{ $value }}" @selected(old('default_tax_preference', $customer->default_tax_preference) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm text-ink">
                <input type="hidden" name="shipping_same_as_billing" value="0">
                <input type="checkbox" name="shipping_same_as_billing" value="1" x-model="sameAsBilling" class="rounded border-line text-primary-600 focus:ring-primary-500">
                {{ __('Shipping address same as billing') }}
            </label>
        </div>
        <template x-if="! sameAsBilling">
            <div class="sm:col-span-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-forms.field :label="__('Shipping address line 1')" name="shipping_address_line_1">
                        <x-forms.input id="shipping_address_line_1" type="text" name="shipping_address_line_1" :value="old('shipping_address_line_1', $customer->shipping_address_line_1)" />
                    </x-forms.field>
                </div>
                <div class="sm:col-span-2">
                    <x-forms.field :label="__('Shipping address line 2')" name="shipping_address_line_2">
                        <x-forms.input id="shipping_address_line_2" type="text" name="shipping_address_line_2" :value="old('shipping_address_line_2', $customer->shipping_address_line_2)" />
                    </x-forms.field>
                </div>
                <x-forms.field :label="__('Shipping city')" name="shipping_city">
                    <x-forms.input id="shipping_city" type="text" name="shipping_city" :value="old('shipping_city', $customer->shipping_city)" />
                </x-forms.field>
                <x-forms.field :label="__('Shipping state')" name="shipping_state">
                    <x-forms.input id="shipping_state" type="text" name="shipping_state" :value="old('shipping_state', $customer->shipping_state)" />
                </x-forms.field>
                <x-forms.field :label="__('Shipping country')" name="shipping_country">
                    <x-forms.input id="shipping_country" type="text" name="shipping_country" :value="old('shipping_country', $customer->shipping_country)" />
                </x-forms.field>
                <x-forms.field :label="__('Shipping postal code')" name="shipping_postal_code">
                    <x-forms.input id="shipping_postal_code" type="text" name="shipping_postal_code" :value="old('shipping_postal_code', $customer->shipping_postal_code)" />
                </x-forms.field>
            </div>
        </template>
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
