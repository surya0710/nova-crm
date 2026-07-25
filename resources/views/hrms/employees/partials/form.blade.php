<div class="space-y-6">
    <x-entity.section :title="__('Personal information')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-forms.field :label="__('First name')" name="first_name" required>
                <x-forms.input name="first_name" :value="old('first_name', $employee->first_name)" required />
            </x-forms.field>
            <x-forms.field :label="__('Last name')" name="last_name" required>
                <x-forms.input name="last_name" :value="old('last_name', $employee->last_name)" required />
            </x-forms.field>
            <x-forms.field :label="__('Email')" name="email">
                <x-forms.input type="email" name="email" :value="old('email', $employee->email)" />
            </x-forms.field>
            <x-forms.field :label="__('Mobile')" name="mobile">
                <x-forms.input name="mobile" :value="old('mobile', $employee->mobile)" />
            </x-forms.field>
        </div>
    </x-entity.section>

    <x-entity.section :title="__('Employment information')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-forms.field :label="__('Employment type')" name="employment_type">
                <x-forms.select name="employment_type">
                    @foreach (config('hrms.employment_types') as $key => $label)
                        <option value="{{ $key }}" @selected(old('employment_type', $employee->employment_type) === $key)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <x-forms.field :label="__('Status')" name="status">
                <x-forms.select name="status">
                    @foreach (config('hrms.employment_statuses') as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $employee->status) === $key)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <x-forms.field :label="__('Joining date')" name="joining_date">
                <x-forms.input type="date" name="joining_date" :value="old('joining_date', optional($employee->joining_date)->toDateString())" />
            </x-forms.field>
            <x-forms.field :label="__('Probation end date')" name="probation_end_date">
                <x-forms.input type="date" name="probation_end_date" :value="old('probation_end_date', optional($employee->probation_end_date)->toDateString())" />
            </x-forms.field>
        </div>
    </x-entity.section>

    <x-entity.section :title="__('Organization assignment')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-forms.field :label="__('Branch')" name="branch_id">
                <x-forms.select name="branch_id">
                    <option value="">{{ __('Branch') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $employee->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <x-forms.field :label="__('Department')" name="department_id">
                <x-forms.select name="department_id">
                    <option value="">{{ __('Department') }}</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id', $employee->department_id) === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <x-forms.field :label="__('Designation')" name="designation_id">
                <x-forms.select name="designation_id">
                    <option value="">{{ __('Designation') }}</option>
                    @foreach ($designations as $designation)
                        <option value="{{ $designation->id }}" @selected((string) old('designation_id', $employee->designation_id) === (string) $designation->id)>{{ $designation->name }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <x-forms.field :label="__('Reporting manager')" name="reporting_manager_id">
                <x-forms.select name="reporting_manager_id">
                    <option value="">{{ __('Reporting Manager') }}</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}" @selected((string) old('reporting_manager_id', $employee->reporting_manager_id) === (string) $manager->id)>{{ $manager->full_name }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
        </div>
    </x-entity.section>

    <x-entity.section :title="__('Emergency contact')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-forms.field :label="__('Name')" name="emergency_contacts.0.name">
                <x-forms.input name="emergency_contacts[0][name]" :value="old('emergency_contacts.0.name', $employee->emergencyContacts[0]->name ?? '')" />
            </x-forms.field>
            <x-forms.field :label="__('Relationship')" name="emergency_contacts.0.relationship">
                <x-forms.input name="emergency_contacts[0][relationship]" :value="old('emergency_contacts.0.relationship', $employee->emergencyContacts[0]->relationship ?? '')" />
            </x-forms.field>
            <x-forms.field :label="__('Phone')" name="emergency_contacts.0.phone">
                <x-forms.input name="emergency_contacts[0][phone]" :value="old('emergency_contacts.0.phone', $employee->emergencyContacts[0]->phone ?? '')" />
            </x-forms.field>
        </div>
    </x-entity.section>

    @if (! $employee->exists || ! $employee->user_id)
        <x-entity.section :title="__('Login account')">
            <div class="space-y-4" x-data="{ createLogin: {{ old('create_user', false) ? 'true' : 'false' }} }">
                <label class="inline-flex items-center gap-2 text-sm text-ink-heading">
                    <input
                        type="checkbox"
                        name="create_user"
                        value="1"
                        x-model="createLogin"
                        @checked(old('create_user'))
                        class="rounded border-line text-primary-600 focus:ring-primary-500"
                    >
                    {{ __('Create Login Account') }}
                </label>
                <p class="text-xs text-ink-muted">{{ __('The employee will receive an invitation to set their own password. Administrators never assign passwords.') }}</p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2" x-show="createLogin" x-cloak>
                    <x-forms.field :label="__('Work email')" name="user_email" required>
                        <x-forms.input type="email" name="user_email" :value="old('user_email', $employee->email)" />
                    </x-forms.field>
                    <x-forms.field :label="__('Role')" name="role">
                        <x-forms.select name="role">
                            @foreach (collect(config('rbac.roles', []))->except('organization-owner') as $slug => $role)
                                <option value="{{ $slug }}" @selected(old('role', config('identity.default_employee_role', 'employee')) === $slug)>
                                    {{ $role['name'] ?? $slug }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <label class="inline-flex items-center gap-2 text-sm text-ink-heading">
                        <input type="checkbox" name="send_invitation" value="1" @checked(old('send_invitation', true)) class="rounded border-line text-primary-600 focus:ring-primary-500">
                        {{ __('Send Invitation') }}
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-ink-heading">
                        <input type="checkbox" name="portal_access" value="1" @checked(old('portal_access', true)) class="rounded border-line text-primary-600 focus:ring-primary-500">
                        {{ __('Portal Access (Employee Workspace)') }}
                    </label>
                </div>
            </div>
        </x-entity.section>
    @endif
</div>
