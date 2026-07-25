<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('My Profile')"
        :subtitle="$employee->employee_code"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('My HR'), 'href' => route('ess.dashboard')],
                ['label' => __('Profile'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @include('ess.partials.nav')

        <x-entity.section :title="__('Employment Details')">
            <p class="text-xs text-ink-muted mb-3">{{ __('Read-only') }}</p>
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Employee Code')">{{ $employee->employee_code }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Department')">{{ $employee->department?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Designation')">{{ $employee->designation?->name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Manager')">{{ $employee->reportingManager?->full_name ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Status')">{{ $employee->status }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Joining Date')">{{ $employee->joining_date?->format('M j, Y') ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-slot:aside>
            <x-ui.card>
                <x-entity.section :title="__('Contact information')">
                    <form method="POST" action="{{ route('ess.profile.update') }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <x-forms.field :label="__('Phone')" name="phone">
                            <x-forms.input name="phone" :value="old('phone', $employee->phone)" placeholder="{{ __('Phone') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Mobile')" name="mobile">
                            <x-forms.input name="mobile" :value="old('mobile', $employee->mobile)" placeholder="{{ __('Mobile') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Personal Email')" name="personal_email">
                            <x-forms.input name="personal_email" type="email" :value="old('personal_email', $employee->personal_email)" placeholder="{{ __('Personal Email') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Address Line 1')" name="address_line_1">
                            <x-forms.input name="address_line_1" :value="old('address_line_1', $employee->address_line_1)" placeholder="{{ __('Address Line 1') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('City')" name="city">
                            <x-forms.input name="city" :value="old('city', $employee->city)" placeholder="{{ __('City') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('State')" name="state">
                            <x-forms.input name="state" :value="old('state', $employee->state)" placeholder="{{ __('State') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Postal Code')" name="postal_code">
                            <x-forms.input name="postal_code" :value="old('postal_code', $employee->postal_code)" placeholder="{{ __('Postal Code') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Country')" name="country">
                            <x-forms.input name="country" :value="old('country', $employee->country)" placeholder="{{ __('Country') }}" />
                        </x-forms.field>
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save Profile') }}</x-ui.button>
                    </form>
                </x-entity.section>
            </x-ui.card>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
