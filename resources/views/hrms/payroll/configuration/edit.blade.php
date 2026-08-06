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
            <x-forms.field :label="__('Salary Mode')" name="salary_mode" required>
                <x-forms.select name="salary_mode" required>
                    @foreach ($salaryModes as $value => $label)
                        <option value="{{ $value }}" @selected(old('salary_mode', $configuration->salary_mode ?? 'calendar') === $value)>{{ __($label) }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <x-forms.field :label="__('Salary Credit Day')" name="salary_credit_day">
                <x-forms.input name="salary_credit_day" type="number" min="1" max="28" :value="old('salary_credit_day', $configuration->salary_credit_day)" />
            </x-forms.field>
            <x-forms.field :label="__('Auto Generate Periods')" name="auto_generate">
                <label class="flex items-center gap-2 text-sm text-ink-heading">
                    <input type="hidden" name="auto_generate" value="0" />
                    <input type="checkbox" name="auto_generate" value="1" @checked(old('auto_generate', $configuration->auto_generate)) class="rounded border-line" />
                    {{ __('Enable optional auto generation') }}
                </label>
            </x-forms.field>
            <x-forms.field :label="__('Reminder Days Before Credit')" name="reminder_days_before_credit">
                <x-forms.input name="reminder_days_before_credit" type="number" min="0" max="30" :value="old('reminder_days_before_credit', $configuration->reminder_days_before_credit)" />
            </x-forms.field>
            @can('update', $configuration)
                <x-forms.footer :cancel-href="route('hrms.payroll.index')" :submit-label="__('Save Configuration')" />
            @endcan
        </form>
    </x-layouts.edit>
</x-app-layout>
