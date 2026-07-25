<x-platform-layout>
    <x-layouts.create
        :title="__('New Organization')"
        :subtitle="__('Provision a new tenant organization')"
        max-width="5xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Organizations'), 'href' => route('platform.organizations.index')],
                ['label' => __('New Organization'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('platform.organizations.store') }}" class="space-y-6">
            @csrf

            <x-forms.section :title="__('Organization Details')">
                <x-forms.field :label="__('Name')" name="name" required class="sm:col-span-1">
                    <x-forms.input name="name" value="{{ old('name') }}" required />
                </x-forms.field>
                <x-forms.field :label="__('Slug')" name="slug" :hint="__('Leave blank to auto-generate')">
                    <x-forms.input name="slug" value="{{ old('slug') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Email')" name="email">
                    <x-forms.input type="email" name="email" value="{{ old('email') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Phone')" name="phone">
                    <x-forms.input name="phone" value="{{ old('phone') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Website')" name="website" class="sm:col-span-2">
                    <x-forms.input type="url" name="website" value="{{ old('website') }}" />
                </x-forms.field>
            </x-forms.section>

            <x-forms.section :title="__('Plan & Defaults')">
                <x-forms.field :label="__('Plan')" name="plan" required>
                    <x-forms.select name="plan" required>
                        @foreach ($plans as $value => $label)
                            <option value="{{ $value }}" @selected(old('plan', 'starter') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="status" required>
                    <x-forms.select name="status" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Timezone')" name="timezone">
                    <x-forms.select name="timezone">
                        <option value="">{{ __('Use template/system default') }}</option>
                        @foreach ($timezones as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone') === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Currency')" name="currency">
                    <x-forms.select name="currency">
                        <option value="">{{ __('Use template/system default') }}</option>
                        @foreach ($currencies as $code => $label)
                            <option value="{{ $code }}" @selected(old('currency') === $code)>{{ $code }} — {{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Tax Name')" name="tax_name" class="sm:col-span-2">
                    <x-forms.input name="tax_name" value="{{ old('tax_name') }}" />
                </x-forms.field>
            </x-forms.section>

            <x-forms.section :title="__('Industry Template')" :subtitle="__('Template settings are copied during creation. The organization remains independent afterwards.')">
                <x-forms.field name="template_version_id" class="sm:col-span-2">
                    <x-forms.select name="template_version_id">
                        <option value="">{{ __('No template') }}</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->currentVersion->id }}" @selected((int) old('template_version_id') === $template->currentVersion->id)>
                                {{ $template->name }} · {{ __('Version :version', ['version' => $template->currentVersion->version]) }}
                            </option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
            </x-forms.section>

            <x-forms.section :title="__('Initial Owner')" :subtitle="__('Optional. Existing emails are attached as owner; password is ignored for existing users.')">
                <x-forms.field :label="__('Owner Name')" name="owner_name">
                    <x-forms.input name="owner_name" value="{{ old('owner_name') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Owner Email')" name="owner_email">
                    <x-forms.input type="email" name="owner_email" value="{{ old('owner_email') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Temporary Password')" name="owner_password" class="sm:col-span-2">
                    <x-forms.input type="password" name="owner_password" autocomplete="new-password" />
                </x-forms.field>
            </x-forms.section>

            <x-forms.footer :cancel-href="route('platform.organizations.index')" :submit-label="__('Create Organization')" />
        </form>
    </x-layouts.create>
</x-platform-layout>
