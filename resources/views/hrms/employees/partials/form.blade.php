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

    <x-entity.section :title="__('Emergency contacts')">
        @php
            $contacts = old('emergency_contacts', $employee->emergencyContacts->map(fn ($c) => [
                'name' => $c->name,
                'relationship' => $c->relationship,
                'phone' => $c->phone,
                'alternate_mobile' => $c->alternate_mobile,
                'email' => $c->email,
                'address' => $c->address,
                'is_primary' => $c->is_primary,
            ])->values()->all() ?: [['name' => '', 'relationship' => '', 'phone' => '', 'alternate_mobile' => '', 'email' => '', 'address' => '', 'is_primary' => true]]);
        @endphp
        @foreach ($contacts as $i => $contact)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 {{ $i > 0 ? 'mt-4 border-t border-line pt-4' : '' }}">
                <x-forms.field :label="__('Name')" :name="'emergency_contacts.'.$i.'.name'">
                    <x-forms.input :name="'emergency_contacts['.$i.'][name]'" :value="$contact['name'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Relationship')" :name="'emergency_contacts.'.$i.'.relationship'">
                    <x-forms.input :name="'emergency_contacts['.$i.'][relationship]'" :value="$contact['relationship'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Mobile')" :name="'emergency_contacts.'.$i.'.phone'">
                    <x-forms.input :name="'emergency_contacts['.$i.'][phone]'" :value="$contact['phone'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Alternate mobile')" :name="'emergency_contacts.'.$i.'.alternate_mobile'">
                    <x-forms.input :name="'emergency_contacts['.$i.'][alternate_mobile]'" :value="$contact['alternate_mobile'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Email')" :name="'emergency_contacts.'.$i.'.email'">
                    <x-forms.input type="email" :name="'emergency_contacts['.$i.'][email]'" :value="$contact['email'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Address')" :name="'emergency_contacts.'.$i.'.address'">
                    <x-forms.input :name="'emergency_contacts['.$i.'][address]'" :value="$contact['address'] ?? ''" />
                </x-forms.field>
                <label class="inline-flex items-center gap-2 text-sm md:col-span-3">
                    <input type="checkbox" name="emergency_contacts[{{ $i }}][is_primary]" value="1" @checked(! empty($contact['is_primary'])) class="rounded border-line text-primary-600">
                    {{ __('Primary contact') }}
                </label>
            </div>
        @endforeach
    </x-entity.section>

    <x-entity.section :title="__('Skills')">
        @php
            $skills = old('skills', $employee->skills->map(fn ($s) => [
                'skill' => $s->skill,
                'proficiency' => $s->proficiency,
                'years_of_experience' => $s->years_of_experience,
                'last_used' => optional($s->last_used)->toDateString(),
                'notes' => $s->notes,
            ])->values()->all() ?: [['skill' => '', 'proficiency' => 'intermediate', 'years_of_experience' => '', 'last_used' => '', 'notes' => '']]);
        @endphp
        @foreach ($skills as $i => $skill)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 {{ $i > 0 ? 'mt-4 border-t border-line pt-4' : '' }}">
                <x-forms.field :label="__('Skill')" :name="'skills.'.$i.'.skill'">
                    <x-forms.input :name="'skills['.$i.'][skill]'" :value="$skill['skill'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Proficiency')" :name="'skills.'.$i.'.proficiency'">
                    <x-forms.select :name="'skills['.$i.'][proficiency]'">
                        @foreach (config('hrms.skill_proficiencies') as $key => $label)
                            <option value="{{ $key }}" @selected(($skill['proficiency'] ?? 'intermediate') === $key)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Years of experience')" :name="'skills.'.$i.'.years_of_experience'">
                    <x-forms.input type="number" :name="'skills['.$i.'][years_of_experience]'" :value="$skill['years_of_experience'] ?? ''" min="0" max="60" />
                </x-forms.field>
                <x-forms.field :label="__('Last used')" :name="'skills.'.$i.'.last_used'">
                    <x-forms.input type="date" :name="'skills['.$i.'][last_used]'" :value="$skill['last_used'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Notes')" :name="'skills.'.$i.'.notes'" class="md:col-span-2">
                    <x-forms.input :name="'skills['.$i.'][notes]'" :value="$skill['notes'] ?? ''" />
                </x-forms.field>
            </div>
        @endforeach
    </x-entity.section>

    <x-entity.section :title="__('Certifications')">
        @php
            $certs = old('certifications', $employee->certifications->map(fn ($c) => [
                'name' => $c->name,
                'issuing_organization' => $c->issuing_organization,
                'credential_number' => $c->credential_number,
                'issue_date' => optional($c->issue_date)->toDateString(),
                'expiry_date' => optional($c->expiry_date)->toDateString(),
                'credential_url' => $c->credential_url,
                'status' => $c->status,
            ])->values()->all() ?: [['name' => '', 'issuing_organization' => '', 'credential_number' => '', 'issue_date' => '', 'expiry_date' => '', 'credential_url' => '', 'status' => 'active']]);
        @endphp
        @foreach ($certs as $i => $cert)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 {{ $i > 0 ? 'mt-4 border-t border-line pt-4' : '' }}">
                <x-forms.field :label="__('Certification name')" :name="'certifications.'.$i.'.name'">
                    <x-forms.input :name="'certifications['.$i.'][name]'" :value="$cert['name'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Issuing organization')" :name="'certifications.'.$i.'.issuing_organization'">
                    <x-forms.input :name="'certifications['.$i.'][issuing_organization]'" :value="$cert['issuing_organization'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Credential number')" :name="'certifications.'.$i.'.credential_number'">
                    <x-forms.input :name="'certifications['.$i.'][credential_number]'" :value="$cert['credential_number'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Issue date')" :name="'certifications.'.$i.'.issue_date'">
                    <x-forms.input type="date" :name="'certifications['.$i.'][issue_date]'" :value="$cert['issue_date'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Expiry date')" :name="'certifications.'.$i.'.expiry_date'">
                    <x-forms.input type="date" :name="'certifications['.$i.'][expiry_date]'" :value="$cert['expiry_date'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Credential URL')" :name="'certifications.'.$i.'.credential_url'">
                    <x-forms.input type="url" :name="'certifications['.$i.'][credential_url]'" :value="$cert['credential_url'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Status')" :name="'certifications.'.$i.'.status'">
                    <x-forms.select :name="'certifications['.$i.'][status]'">
                        @foreach (config('hrms.certification_statuses') as $key => $label)
                            <option value="{{ $key }}" @selected(($cert['status'] ?? 'active') === $key)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
            </div>
        @endforeach
    </x-entity.section>

    <x-entity.section :title="__('Education')">
        @php
            $educations = old('educations', $employee->educations->map(fn ($e) => [
                'degree' => $e->degree,
                'institution' => $e->institution,
                'field_of_study' => $e->field_of_study,
                'start_date' => optional($e->start_date)->toDateString(),
                'end_date' => optional($e->end_date)->toDateString(),
                'start_year' => $e->start_year,
                'end_year' => $e->end_year,
                'grade' => $e->grade,
                'description' => $e->description,
            ])->values()->all() ?: [['degree' => '', 'institution' => '', 'field_of_study' => '', 'start_date' => '', 'end_date' => '', 'end_year' => '', 'grade' => '', 'description' => '']]);
        @endphp
        @foreach ($educations as $i => $education)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 {{ $i > 0 ? 'mt-4 border-t border-line pt-4' : '' }}">
                <x-forms.field :label="__('Degree')" :name="'educations.'.$i.'.degree'">
                    <x-forms.input :name="'educations['.$i.'][degree]'" :value="$education['degree'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Institution')" :name="'educations.'.$i.'.institution'">
                    <x-forms.input :name="'educations['.$i.'][institution]'" :value="$education['institution'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Specialization')" :name="'educations.'.$i.'.field_of_study'">
                    <x-forms.input :name="'educations['.$i.'][field_of_study]'" :value="$education['field_of_study'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Grade')" :name="'educations.'.$i.'.grade'">
                    <x-forms.input :name="'educations['.$i.'][grade]'" :value="$education['grade'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Start date')" :name="'educations.'.$i.'.start_date'">
                    <x-forms.input type="date" :name="'educations['.$i.'][start_date]'" :value="$education['start_date'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('End date')" :name="'educations.'.$i.'.end_date'">
                    <x-forms.input type="date" :name="'educations['.$i.'][end_date]'" :value="$education['end_date'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Description')" :name="'educations.'.$i.'.description'" class="md:col-span-2">
                    <x-forms.textarea :name="'educations['.$i.'][description]'" rows="2">{{ $education['description'] ?? '' }}</x-forms.textarea>
                </x-forms.field>
            </div>
        @endforeach
    </x-entity.section>

    <x-entity.section :title="__('Experience')">
        @php
            $experiences = old('experiences', $employee->experiences->map(fn ($e) => [
                'company' => $e->company,
                'title' => $e->title,
                'employment_type' => $e->employment_type,
                'start_date' => optional($e->start_date)->toDateString(),
                'end_date' => optional($e->end_date)->toDateString(),
                'technologies' => $e->technologies,
                'description' => $e->description,
            ])->values()->all() ?: [['company' => '', 'title' => '', 'employment_type' => '', 'start_date' => '', 'end_date' => '', 'technologies' => '', 'description' => '']]);
        @endphp
        @foreach ($experiences as $i => $experience)
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 {{ $i > 0 ? 'mt-4 border-t border-line pt-4' : '' }}">
                <x-forms.field :label="__('Company')" :name="'experiences.'.$i.'.company'">
                    <x-forms.input :name="'experiences['.$i.'][company]'" :value="$experience['company'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Designation')" :name="'experiences.'.$i.'.title'">
                    <x-forms.input :name="'experiences['.$i.'][title]'" :value="$experience['title'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Employment type')" :name="'experiences.'.$i.'.employment_type'">
                    <x-forms.select :name="'experiences['.$i.'][employment_type]'">
                        <option value="">{{ __('Select') }}</option>
                        @foreach (config('hrms.employment_types') as $key => $label)
                            <option value="{{ $key }}" @selected(($experience['employment_type'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Technologies')" :name="'experiences.'.$i.'.technologies'">
                    <x-forms.input :name="'experiences['.$i.'][technologies]'" :value="$experience['technologies'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Start date')" :name="'experiences.'.$i.'.start_date'">
                    <x-forms.input type="date" :name="'experiences['.$i.'][start_date]'" :value="$experience['start_date'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('End date')" :name="'experiences.'.$i.'.end_date'">
                    <x-forms.input type="date" :name="'experiences['.$i.'][end_date]'" :value="$experience['end_date'] ?? ''" />
                </x-forms.field>
                <x-forms.field :label="__('Responsibilities')" :name="'experiences.'.$i.'.description'" class="md:col-span-2">
                    <x-forms.textarea :name="'experiences['.$i.'][description]'" rows="3">{{ $experience['description'] ?? '' }}</x-forms.textarea>
                </x-forms.field>
            </div>
        @endforeach
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
