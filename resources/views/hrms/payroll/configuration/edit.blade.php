<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Payroll Configuration')"
        :subtitle="__('Organization payroll settings')"
        max-width="2xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Configuration'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('hrms.payroll.configuration.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-forms.field :label="__('Payroll Frequency')" name="payroll_frequency" required>
                <x-forms.select name="payroll_frequency" required>
                    @foreach ($frequencies as $value => $label)
                        <option value="{{ $value }}" @selected(old('payroll_frequency', $configuration->payroll_frequency) === $value)>{{ __($label) }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <x-forms.field :label="__('Currency')" name="currency" required>
                <x-forms.input name="currency" :value="old('currency', $configuration->currency)" required />
            </x-forms.field>
            <x-forms.field :label="__('Working Days Per Month')" name="working_days_per_month">
                <x-forms.input name="working_days_per_month" type="number" :value="old('working_days_per_month', $configuration->working_days_per_month)" />
            </x-forms.field>
            <x-forms.field :label="__('Week Off Days')" name="week_off_days">
                <div class="flex flex-wrap gap-3 text-sm">
                    @foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                        <label class="flex items-center gap-2 text-ink-heading">
                            <input type="checkbox" name="week_off_days[]" value="{{ $day }}" @checked(in_array($day, old('week_off_days', $configuration->week_off_days ?? []), true)) class="rounded border-line" />
                            {{ __(ucfirst($day)) }}
                        </label>
                    @endforeach
                </div>
            </x-forms.field>
            <x-forms.field :label="__('Overtime Handling')" name="overtime_handling" required>
                <x-forms.select name="overtime_handling" required>
                    @foreach ($overtimeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('overtime_handling', $configuration->overtime_handling) === $value)>{{ __($label) }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <x-forms.field :label="__('Rounding Policy')" name="rounding_policy" required>
                <x-forms.select name="rounding_policy" required>
                    @foreach ($roundingPolicies as $value => $label)
                        <option value="{{ $value }}" @selected(old('rounding_policy', $configuration->rounding_policy) === $value)>{{ __($label) }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            @can('update', $configuration)
                <x-forms.footer :cancel-href="route('hrms.payroll.index')" :submit-label="__('Save Configuration')" />
            @endcan
        </form>
    </x-layouts.edit>
</x-app-layout>
