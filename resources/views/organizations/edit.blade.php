<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Organization Settings') }}</h1>
            <p class="text-sm text-slate-500">{{ $organization->name }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    @php
        $activeTab = 'general';
        if ($errors->hasAny(['logo', 'remove_logo'])) {
            $activeTab = 'brand';
        } elseif ($errors->hasAny(['address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country'])) {
            $activeTab = 'address';
        } elseif ($errors->hasAny(['tax_name', 'tax_number', 'timezone', 'currency'])) {
            $activeTab = 'preferences';
        } elseif ($errors->hasAny(['industry_type', 'terminology', 'terminology.*'])) {
            $activeTab = 'terminology';
        } elseif ($errors->hasAny(['custom_fields', 'custom_fields.*'])) {
            $activeTab = 'custom_fields';
        } elseif ($errors->hasAny(['mail_enabled', 'mail_driver', 'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name'])) {
            $activeTab = 'email';
        } elseif ($errors->hasAny(['name', 'email', 'phone', 'website', 'description'])) {
            $activeTab = 'general';
        }
    @endphp

    <div
        x-data="{
            tab: '{{ $activeTab }}',
            industry: @js(old('industry_type', $organization->settings['industry_type'] ?? 'general')),
            terms: @js(collect($terminologyKeys)->mapWithKeys(fn ($key) => [$key => old('terminology.'.$key, $currentTerms[$key] ?? '')])->all()),
            presets: @js($industryPresets),
            applyIndustryPreset() {
                const preset = this.presets[this.industry] || {};
                for (const key of Object.keys(this.terms)) {
                    if (preset[key]) {
                        this.terms[key] = preset[key];
                    }
                }
            }
        }"
        class="max-w-4xl"
    >
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            {{-- Tabs --}}
            <div class="border-b border-slate-200 bg-slate-50/80">
                <nav class="flex overflow-x-auto" aria-label="{{ __('Settings sections') }}">
                    @foreach ([
                        'general' => __('General'),
                        'custom_fields' => __('Custom Fields'),
                        'brand' => __('Brand'),
                        'address' => __('Address'),
                        'preferences' => __('Tax & Preferences'),
                        'terminology' => __('Terminology'),
                        'email' => __('Email'),
                        'roles' => __('Roles & Permissions'),
                    ] as $key => $label)
                        <button
                            type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}'
                                ? 'border-indigo-600 text-indigo-600 bg-white'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                            class="shrink-0 px-5 py-4 text-sm font-medium border-b-2 transition whitespace-nowrap"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </nav>
            </div>

            <form
                method="POST"
                action="{{ route('organization.update') }}"
                enctype="multipart/form-data"
                x-show="tab !== 'roles'"
            >
                @csrf
                @method('PATCH')

                <div class="p-6 sm:p-8">
                {{-- General --}}
                <div x-show="tab === 'general'" x-cloak class="space-y-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ __('General Information') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Basic details about your organization.') }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <x-input-label for="name" :value="__('Organization Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $organization->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Business Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $organization->email)" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $organization->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="website" :value="__('Website')" />
                            <x-text-input id="website" class="block mt-1 w-full" type="url" name="website" :value="old('website', $organization->website)" placeholder="https://" />
                            <x-input-error :messages="$errors->get('website')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $organization->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Custom Fields --}}
                <div x-show="tab === 'custom_fields'" x-cloak class="space-y-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ __('Custom Fields') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Organization-level metadata configured for your workspace.') }}</p>
                    </div>

                    @include('metadata-fields._runtime_form', [
                        'metadataFields' => $metadataFields ?? collect(),
                        'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
                        'record' => $organization,
                        'showMetadataHeader' => false,
                    ])
                </div>

                {{-- Brand --}}
                <div x-show="tab === 'brand'" x-cloak class="space-y-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ __('Brand Identity') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Upload a logo shown in the sidebar, dashboard, and across your workspace.') }}</p>
                    </div>

                    <div class="max-w-md">
                        <x-logo-upload :current-url="$organization->logo_url" :removable="true" />
                    </div>
                </div>

                {{-- Address --}}
                <div x-show="tab === 'address'" x-cloak class="space-y-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ __('Business Address') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Used on quotations, invoices, and official documents.') }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <x-input-label for="address_line_1" :value="__('Address Line 1')" />
                            <x-text-input id="address_line_1" class="block mt-1 w-full" type="text" name="address_line_1" :value="old('address_line_1', $organization->address_line_1)" />
                            <x-input-error :messages="$errors->get('address_line_1')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="address_line_2" :value="__('Address Line 2')" />
                            <x-text-input id="address_line_2" class="block mt-1 w-full" type="text" name="address_line_2" :value="old('address_line_2', $organization->address_line_2)" />
                            <x-input-error :messages="$errors->get('address_line_2')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="city" :value="__('City')" />
                            <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" :value="old('city', $organization->city)" />
                            <x-input-error :messages="$errors->get('city')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="state" :value="__('State / Province')" />
                            <x-text-input id="state" class="block mt-1 w-full" type="text" name="state" :value="old('state', $organization->state)" />
                            <x-input-error :messages="$errors->get('state')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="postal_code" :value="__('Postal Code')" />
                            <x-text-input id="postal_code" class="block mt-1 w-full" type="text" name="postal_code" :value="old('postal_code', $organization->postal_code)" />
                            <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="country" :value="__('Country')" />
                            <x-text-input id="country" class="block mt-1 w-full" type="text" name="country" :value="old('country', $organization->country)" />
                            <x-input-error :messages="$errors->get('country')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Tax & Preferences --}}
                <div x-show="tab === 'preferences'" x-cloak class="space-y-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ __('Tax & Preferences') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Tax details and regional settings for your organization.') }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="tax_name" :value="__('Tax Label (GST/VAT)')" />
                            <x-text-input id="tax_name" class="block mt-1 w-full" type="text" name="tax_name" :value="old('tax_name', $organization->tax_name)" placeholder="GST" />
                            <x-input-error :messages="$errors->get('tax_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="tax_number" :value="__('Tax Number / GSTIN')" />
                            <x-text-input id="tax_number" class="block mt-1 w-full" type="text" name="tax_number" :value="old('tax_number', $organization->tax_number)" />
                            <x-input-error :messages="$errors->get('tax_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="gst_state_code" :value="__('GST state')" />
                            <select id="gst_state_code" name="gst_state_code" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">{{ __('Not set') }}</option>
                                @foreach (config('tax.states') ?? [] as $code => $state)
                                    <option value="{{ $code }}" @selected(old('gst_state_code', $organization->gst_state_code) === $code)>{{ $code }} — {{ $state['name'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('gst_state_code')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="timezone" :value="__('Timezone')" />
                            <select id="timezone" name="timezone" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                @foreach ($timezones as $timezone)
                                    <option value="{{ $timezone }}" @selected(old('timezone', $organization->timezone) === $timezone)>
                                        {{ $timezone }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
                        </div>

                                                <div>
                            <x-input-label for="currency" :value="__('Currency')" />
                            <select id="currency" name="currency" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                @foreach ($currencies as $code => $label)
                                    <option value="{{ $code }}" @selected(old('currency', $organization->currency) === $code)>
                                        {{ $code }} — {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="locale" :value="__('Locale')" />
                            <x-text-input id="locale" class="block mt-1 w-full" type="text" name="locale" :value="old('locale', $regional['locale'] ?? 'en')" placeholder="en" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('BCP 47 language tag, e.g. en, en-IN, hi.') }}</p>
                            <x-input-error :messages="$errors->get('locale')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="fiscal_year_start_month" :value="__('Fiscal year starts')" />
                            <select id="fiscal_year_start_month" name="fiscal_year_start_month" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach (range(1, 12) as $month)
                                    <option value="{{ $month }}" @selected((int) old('fiscal_year_start_month', $regional['fiscal_year_start_month'] ?? 4) === $month)>
                                        {{ \Carbon\Carbon::create()->month($month)->format('F') }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('fiscal_year_start_month')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date_format" :value="__('Date format')" />
                            <select id="date_format" name="date_format" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach (['Y-m-d' => 'YYYY-MM-DD', 'd/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'd-M-Y' => 'DD-Mon-YYYY'] as $fmt => $label)
                                    <option value="{{ $fmt }}" @selected(old('date_format', $regional['date_format'] ?? 'Y-m-d') === $fmt)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('date_format')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="time_format" :value="__('Time format')" />
                            <select id="time_format" name="time_format" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach (['H:i' => '24-hour', 'h:i A' => '12-hour', 'g:i A' => '12-hour compact'] as $fmt => $label)
                                    <option value="{{ $fmt }}" @selected(old('time_format', $regional['time_format'] ?? 'H:i') === $fmt)>{{ __($label) }} ({{ $fmt }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('time_format')" class="mt-2" />
                        </div>
                    </div>
                    <p class="text-sm text-slate-500">
                        <a href="{{ route('organization.settings.working-days.edit') }}" class="text-indigo-600 hover:underline">{{ __('Configure working days') }}</a>
                        {{ __('and business hours in Organization Settings.') }}
                    </p>
                </div>

                {{-- Terminology --}}
                <div x-show="tab === 'terminology'" x-cloak class="space-y-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ __('Industry Terminology') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Choose an industry preset and customize labels used across your CRM workspace.') }}</p>
                    </div>

                    <div class="rounded-lg border border-indigo-100 bg-indigo-50/50 p-4">
                        <x-input-label for="industry_type" :value="__('Industry type')" />
                        <div class="mt-2 flex flex-col sm:flex-row gap-3">
                            <select
                                id="industry_type"
                                name="industry_type"
                                x-model="industry"
                                class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                @foreach ($industries as $key => $industry)
                                    <option value="{{ $key }}">{{ $industry['name'] }}</option>
                                @endforeach
                            </select>
                            <button
                                type="button"
                                @click="applyIndustryPreset()"
                                class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50 transition shrink-0"
                            >
                                {{ __('Apply industry defaults') }}
                            </button>
                        </div>
                        @foreach ($industries as $key => $industry)
                            <p class="mt-2 text-xs text-slate-600" x-show="industry === '{{ $key }}'">{{ $industry['description'] }}</p>
                        @endforeach
                        <x-input-error :messages="$errors->get('industry_type')" class="mt-2" />
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Custom labels') }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($terminologyKeys as $key)
                                <div>
                                    <label for="terminology_{{ $key }}" class="block text-sm font-medium text-slate-700 capitalize">{{ str_replace('_', ' ', $key) }}</label>
                                    @if (! empty($terminologyLabels[$key]))
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $terminologyLabels[$key] }}</p>
                                    @endif
                                    <input
                                        id="terminology_{{ $key }}"
                                        type="text"
                                        name="terminology[{{ $key }}]"
                                        x-model="terms['{{ $key }}']"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    />
                                    <x-input-error :messages="$errors->get('terminology.'.$key)" class="mt-2" />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('Preview') }}</p>
                        <div class="flex flex-wrap gap-2 text-sm">
                            <span class="inline-flex items-center rounded-md bg-white border border-slate-200 px-2.5 py-1" x-text="terms.leads"></span>
                            <span class="inline-flex items-center rounded-md bg-white border border-slate-200 px-2.5 py-1" x-text="terms.customers"></span>
                            <span class="inline-flex items-center rounded-md bg-white border border-slate-200 px-2.5 py-1" x-text="terms.pipeline"></span>
                            <span class="inline-flex items-center rounded-md bg-white border border-slate-200 px-2.5 py-1" x-text="terms.products"></span>
                            <span class="inline-flex items-center rounded-md bg-white border border-slate-200 px-2.5 py-1" x-text="terms.quotations"></span>
                            <span class="inline-flex items-center rounded-md bg-white border border-slate-200 px-2.5 py-1" x-text="terms.invoices"></span>
                            <span class="inline-flex items-center rounded-md bg-white border border-slate-200 px-2.5 py-1" x-text="terms.payments"></span>
                        </div>
                    </div>
                </div>

                {{-- Email --}}
                <div x-show="tab === 'email'" x-cloak class="space-y-5">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ __('Outgoing Email (SMTP)') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Client emails (quotations, invoices, receipts) are sent from this organization\'s own mail account â€” not from global .env settings.') }}</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <input type="hidden" name="mail_enabled" value="0">
                        <label class="inline-flex items-center gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                name="mail_enabled"
                                value="1"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                @checked(old('mail_enabled', $mailSettings['enabled']))
                            />
                            <span class="text-sm font-medium text-slate-900">{{ __('Enable organization email') }}</span>
                        </label>
                        <x-input-error :messages="$errors->get('mail_enabled')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="mail_driver" :value="__('Driver')" />
                            <select id="mail_driver" name="mail_driver" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach ($mailDrivers as $value => $label)
                                    <option value="{{ $value }}" @selected(old('mail_driver', $mailSettings['driver']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('mail_driver')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="mail_encryption" :value="__('Encryption')" />
                            <select id="mail_encryption" name="mail_encryption" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach ($mailEncryptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('mail_encryption', $mailSettings['encryption']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('mail_encryption')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="mail_host" :value="__('SMTP Host')" />
                            <x-text-input id="mail_host" class="block mt-1 w-full" type="text" name="mail_host" :value="old('mail_host', $mailSettings['host'])" placeholder="smtp.example.com" />
                            <x-input-error :messages="$errors->get('mail_host')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="mail_port" :value="__('SMTP Port')" />
                            <x-text-input id="mail_port" class="block mt-1 w-full" type="number" name="mail_port" :value="old('mail_port', $mailSettings['port'])" placeholder="587" />
                            <x-input-error :messages="$errors->get('mail_port')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="mail_username" :value="__('SMTP Username')" />
                            <x-text-input id="mail_username" class="block mt-1 w-full" type="text" name="mail_username" :value="old('mail_username', $mailSettings['username'])" autocomplete="off" />
                            <x-input-error :messages="$errors->get('mail_username')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="mail_password" :value="__('SMTP Password')" />
                            <x-text-input id="mail_password" class="block mt-1 w-full" type="password" name="mail_password" autocomplete="new-password" placeholder="{{ $mailSettings['has_password'] ? 'â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢' : '' }}" />
                            @if ($mailSettings['has_password'])
                                <p class="mt-1 text-xs text-slate-500">{{ __('Leave blank to keep the current password.') }}</p>
                            @endif
                            <x-input-error :messages="$errors->get('mail_password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="mail_from_address" :value="__('From email address')" />
                            <x-text-input id="mail_from_address" class="block mt-1 w-full" type="email" name="mail_from_address" :value="old('mail_from_address', $mailSettings['from_address'] ?: $organization->email)" placeholder="billing@yourcompany.com" />
                            <x-input-error :messages="$errors->get('mail_from_address')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="mail_from_name" :value="__('From name')" />
                            <x-text-input id="mail_from_name" class="block mt-1 w-full" type="text" name="mail_from_name" :value="old('mail_from_name', $mailSettings['from_name'] ?: $organization->name)" />
                            <x-input-error :messages="$errors->get('mail_from_name')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Save --}}
                <div class="px-6 sm:px-8 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
                    <p class="text-sm text-slate-500 hidden sm:block">{{ __('Changes apply to your entire organization workspace.') }}</p>
                    <x-primary-button>
                        {{ __('Save Settings') }}
                    </x-primary-button>
                </div>
            </form>

            {{-- Test email (separate form) --}}
            <div x-show="tab === 'email'" x-cloak class="px-6 sm:px-8 pb-8 border-t border-slate-200 bg-slate-50/30">
                <form method="POST" action="{{ route('organization.test-mail') }}" class="mt-6 flex flex-col sm:flex-row sm:items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="test_email" :value="__('Send test email to')" />
                        <x-text-input id="test_email" class="block mt-1 w-full" type="email" name="test_email" :value="old('test_email', auth()->user()->email)" required />
                        <x-input-error :messages="$errors->get('test_email')" class="mt-2" />
                    </div>
                    <x-secondary-button type="submit">{{ __('Send Test') }}</x-secondary-button>
                </form>
                <p class="mt-2 text-xs text-slate-500">{{ __('Save SMTP settings first, then send a test to verify delivery.') }}</p>
            </div>

            {{-- Roles & Permissions (read-only) --}}
            <div x-show="tab === 'roles'" x-cloak class="p-6 sm:p-8">
                <div class="mb-6">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Roles & Permissions') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('System roles available in your organization.') }}</p>
                    @if (auth()->user()?->hasPermission('rbac.view', $organization))
                        <a href="{{ route('rbac.roles.index') }}" class="mt-3 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            {{ __('Manage Access Control') }} â†’
                        </a>
                    @endif
                </div>

                <div class="space-y-4">
                    @foreach ($roles as $role)
                        <div class="rounded-lg border border-slate-200 overflow-hidden">
                            <div class="px-4 py-3 bg-slate-50 flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">{{ $role->name }}</h4>
                                    @if ($role->description)
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $role->description }}</p>
                                    @endif
                                </div>
                                @if ($role->is_system)
                                    <span class="shrink-0 text-[10px] uppercase tracking-wide bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-medium">{{ __('System') }}</span>
                                @endif
                            </div>
                            <div class="px-4 py-3">
                                @if ($role->slug === 'organization-owner')
                                    <p class="text-sm text-slate-600">{{ __('Full access to all modules and settings.') }}</p>
                                @else
                                    @php
                                        $grouped = $role->permissions->groupBy('module');
                                    @endphp
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($grouped as $module => $permissions)
                                            <span class="inline-flex items-center gap-1 text-xs bg-slate-100 text-slate-700 px-2 py-1 rounded-md">
                                                <span class="font-medium capitalize">{{ $module }}</span>
                                                <span class="text-slate-400">Â·</span>
                                                <span>{{ $permissions->pluck('name')->join(', ') }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
