@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Employee'), __('PF'), __('ESI'), __('PT State'), __('PAN'), __('Regime')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Employee Statutory Profiles')" :subtitle="__('Manage PF, ESI, PT, and tax profile data')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Statutory Compliance'), 'href' => route('hrms.payroll.statutory.index')],
                ['label' => __('Profiles'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\EmployeeStatutoryProfile::class)
            <x-ui.card class="mb-4">
                <h2 class="mb-3 text-sm font-semibold text-ink-heading">{{ __('Upsert Profile') }}</h2>
                <form method="POST" action="{{ route('hrms.payroll.statutory.profiles.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <x-forms.field :label="__('Employee')" name="employee_id">
                        <x-forms.select name="employee_id" required>
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                                    {{ $employee->employee_code }} — {{ $employee->full_name }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('PAN')" name="pan">
                        <x-forms.input type="text" name="pan" :value="old('pan')" maxlength="20" />
                    </x-forms.field>
                    <x-forms.field :label="__('Tax Regime')" name="tax_regime">
                        <x-forms.select name="tax_regime">
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($taxRegimes as $key => $label)
                                <option value="{{ $key }}" @selected(old('tax_regime') === $key)>{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <label class="flex items-center gap-2 text-sm text-ink-heading">
                        <input type="checkbox" name="pf_eligible" value="1" @checked(old('pf_eligible')) class="rounded border-line">
                        {{ __('PF Eligible') }}
                    </label>
                    <x-forms.field :label="__('PF UAN')" name="pf_uan">
                        <x-forms.input type="text" name="pf_uan" :value="old('pf_uan')" maxlength="20" />
                    </x-forms.field>
                    <x-forms.field :label="__('Professional Tax State')" name="professional_tax_state">
                        <x-forms.select name="professional_tax_state">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($ptStates as $code => $label)
                                <option value="{{ $code }}" @selected(old('professional_tax_state') === $code)>{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <label class="flex items-center gap-2 text-sm text-ink-heading">
                        <input type="checkbox" name="esi_eligible" value="1" @checked(old('esi_eligible')) class="rounded border-line">
                        {{ __('ESI Eligible') }}
                    </label>
                    <x-forms.field :label="__('ESI Number')" name="esi_number">
                        <x-forms.input type="text" name="esi_number" :value="old('esi_number')" maxlength="30" />
                    </x-forms.field>
                    <x-forms.field :label="__('Aadhaar (optional)')" name="aadhaar">
                        <x-forms.input type="text" name="aadhaar" :value="old('aadhaar')" maxlength="20" />
                    </x-forms.field>
                    <div class="md:col-span-3">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save Profile') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($profiles->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="payroll" /></x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($profiles as $profile)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">{{ $profile->employee?->employee_code }} — {{ $profile->employee?->full_name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $profile->pf_eligible ? ($profile->pf_uan ?: __('Eligible')) : __('No') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $profile->esi_eligible ? ($profile->esi_number ?: __('Eligible')) : __('No') }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $profile->professional_tax_state ?: '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $profile->pan ?: '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $profile->tax_regime ?: '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
