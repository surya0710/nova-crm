@php
    $tagsValue = old('tags', isset($customer->tags) ? implode(', ', $customer->tags ?? []) : '');
@endphp

<div class="space-y-8">
    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Contact Information') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <x-input-label for="name" :value="__('Contact Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $customer->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="company" :value="__('Company')" />
                <x-text-input id="company" class="block mt-1 w-full" type="text" name="company" :value="old('company', $customer->company)" />
                <x-input-error :messages="$errors->get('company')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="industry" :value="__('Industry')" />
                <x-text-input id="industry" class="block mt-1 w-full" type="text" name="industry" :value="old('industry', $customer->industry)" />
                <x-input-error :messages="$errors->get('industry')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $customer->email)" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" :value="__('Phone')" />
                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $customer->phone)" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="website" :value="__('Website')" />
                <x-text-input id="website" class="block mt-1 w-full" type="url" name="website" :value="old('website', $customer->website)" placeholder="https://" />
                <x-input-error :messages="$errors->get('website')" class="mt-2" />
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Address & Tax') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <x-input-label for="address_line_1" :value="__('Address Line 1')" />
                <x-text-input id="address_line_1" class="block mt-1 w-full" type="text" name="address_line_1" :value="old('address_line_1', $customer->address_line_1)" />
                <x-input-error :messages="$errors->get('address_line_1')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="address_line_2" :value="__('Address Line 2')" />
                <x-text-input id="address_line_2" class="block mt-1 w-full" type="text" name="address_line_2" :value="old('address_line_2', $customer->address_line_2)" />
                <x-input-error :messages="$errors->get('address_line_2')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="city" :value="__('City')" />
                <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" :value="old('city', $customer->city)" />
                <x-input-error :messages="$errors->get('city')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="state" :value="__('State / Province')" />
                <x-text-input id="state" class="block mt-1 w-full" type="text" name="state" :value="old('state', $customer->state)" />
                <x-input-error :messages="$errors->get('state')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="postal_code" :value="__('Postal Code')" />
                <x-text-input id="postal_code" class="block mt-1 w-full" type="text" name="postal_code" :value="old('postal_code', $customer->postal_code)" />
                <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="country" :value="__('Country')" />
                <x-text-input id="country" class="block mt-1 w-full" type="text" name="country" :value="old('country', $customer->country)" />
                <x-input-error :messages="$errors->get('country')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="tax_number" :value="__('Tax Number')" />
                <x-text-input id="tax_number" class="block mt-1 w-full" type="text" name="tax_number" :value="old('tax_number', $customer->tax_number)" />
                <x-input-error :messages="$errors->get('tax_number')" class="mt-2" />
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Account') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach (config('customers.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $customer->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="assigned_to" :value="__('Account Manager')" />
                <select id="assigned_to" name="assigned_to" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('Unassigned') }}</option>
                    @foreach ($assignees as $member)
                        <option value="{{ $member->id }}" @selected(old('assigned_to', $customer->assigned_to) == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="tags" :value="__('Tags')" />
                <x-text-input id="tags" class="block mt-1 w-full" type="text" name="tags" :value="$tagsValue" placeholder="vip, enterprise, recurring" />
                <p class="mt-1 text-xs text-slate-500">{{ __('Comma-separated tags') }}</p>
                <x-input-error :messages="$errors->get('tags')" class="mt-2" />
            </div>
        </div>
    </div>
</div>

@include('metadata-fields._runtime_form', [
    'metadataFields' => $metadataFields ?? collect(),
    'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
    'record' => $customer,
])
